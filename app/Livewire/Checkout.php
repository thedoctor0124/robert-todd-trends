<?php

namespace App\Livewire;

use App\Models\AppSetting;
use App\Models\DiscountCode;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use App\Mail\OrderPlaced;
use App\Services\AccessService;
use App\Services\SquarePaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Checkout extends Component
{
    public string $type; // 'publication' or 'subscription'

    public int $itemId;

    public $item;

    public string $discountCode = '';

    public ?float $discountAmount = null;

    public ?string $discountError = null;

    public ?DiscountCode $appliedDiscount = null;

    public ?string $paymentError = null;

    public bool $processing = false;

    public string $delivery_name = '';

    public string $delivery_address_line_1 = '';

    public string $delivery_address_line_2 = '';

    public string $delivery_city = '';

    public string $delivery_county = '';

    public string $delivery_postcode = '';

    public string $delivery_country = 'United Kingdom';

    public string $delivery_phone = '';

    public bool $terms_accepted = false;

    public string $auth_mode = 'login';

    public string $login_email = '';

    public string $login_password = '';

    public bool $login_remember = true;

    public string $register_name = '';

    public string $register_email = '';

    public string $register_password = '';

    public string $register_password_confirmation = '';

    public function mount(string $type, int $id)
    {
        abort_unless(in_array($type, ['publication', 'subscription'], true), 404);

        $this->type = $type;
        $this->itemId = $id;
        $this->delivery_name = auth()->user()?->name ?? '';

        if ($type === 'publication') {
            $this->item = Publication::findOrFail($id);
            if (auth()->check() && auth()->user()->hasPurchased($this->item)) {
                return redirect()->route('publications.viewer', $this->item->slug);
            }
        } else {
            $this->item = Season::findOrFail($id);

            if (! AppSetting::subscriptionAccessEnabled()) {
                return redirect()
                    ->route('seasons.show', $this->item->slug)
                    ->with('error', 'Season subscriptions are currently unavailable.');
            }

            if (auth()->check() && auth()->user()->hasSubscription($this->item)) {
                return redirect()->route('seasons.show', $this->item->slug);
            }
        }
    }

    public function checkoutLogin(): void
    {
        $this->validate([
            'login_email' => 'required|email',
            'login_password' => 'required|string',
        ], [], [
            'login_email' => 'email',
            'login_password' => 'password',
        ]);

        if (! Auth::attempt(['email' => $this->login_email, 'password' => $this->login_password], $this->login_remember)) {
            $this->addError('login_email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();
        $this->delivery_name = auth()->user()->name;
        $this->resetValidation();
    }

    public function checkoutRegister(): void
    {
        $this->validate([
            'register_name' => 'required|string|max:255',
            'register_email' => 'required|email|unique:users,email',
            'register_password' => 'required|min:8|confirmed',
        ], [], [
            'register_name' => 'name',
            'register_email' => 'email',
            'register_password' => 'password',
        ]);

        $user = User::create([
            'name' => $this->register_name,
            'email' => $this->register_email,
            'password' => Hash::make($this->register_password),
            'is_admin' => str_ends_with($this->register_email, '@roberttodds.com'),
        ]);

        Auth::login($user, true);
        session()->regenerate();
        $this->delivery_name = $user->name;
        $this->resetValidation();
    }

    public function applyDiscount()
    {
        $this->discountError = null;
        $this->discountAmount = null;
        $this->appliedDiscount = null;

        if (empty($this->discountCode)) {
            return;
        }

        $code = DiscountCode::where('code', strtoupper($this->discountCode))->first();

        if (! $code || ! $code->isValid()) {
            $this->discountError = 'Invalid or expired discount code.';

            return;
        }

        if ($this->type === 'publication' && $code->publication_id && $code->publication_id !== $this->itemId) {
            $this->discountError = 'This code is not valid for this publication.';

            return;
        }

        if ($this->type === 'subscription' && $code->season_id && $code->season_id !== $this->itemId) {
            $this->discountError = 'This code is not valid for this season.';

            return;
        }

        $this->appliedDiscount = $code;
        $this->discountAmount = $code->calculateDiscount($this->getOriginalPrice());
    }

    public function removeDiscount()
    {
        $this->discountCode = '';
        $this->discountAmount = null;
        $this->discountError = null;
        $this->appliedDiscount = null;
    }

    public function processPayment(string $nonce)
    {
        $this->paymentError = null;

        if (! auth()->check()) {
            $this->paymentError = 'Please sign in or create an account before payment.';

            return;
        }

        $this->validateTermsAcceptance();
        $this->validateDeliveryAddress();
        $this->processing = true;

        if ($this->type === 'subscription' && ! AppSetting::subscriptionAccessEnabled()) {
            $this->paymentError = 'Season subscriptions are currently unavailable.';
            $this->processing = false;

            return;
        }

        $finalPrice = $this->getFinalPrice();
        $amountCents = (int) round($finalPrice * 100);

        if ($amountCents <= 0) {
            $this->completePurchase(null, 0);

            return;
        }

        try {
            $service = new SquarePaymentService;
            $note = $this->type === 'publication'
                ? "Publication: {$this->item->title}"
                : "Season Subscription: {$this->item->name}";

            $result = $service->charge($nonce, $amountCents, 'GBP', $note);
            $this->completePurchase($result['id'], $finalPrice);
        } catch (\Exception $e) {
            \Log::error('Square payment error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->paymentError = 'Payment failed: '.$e->getMessage();
            $this->processing = false;
        }
    }

    protected function completePurchase(?string $paymentId, float $amountPaid): void
    {
        $accessService = new AccessService;

        if ($this->appliedDiscount) {
            $this->appliedDiscount->increment('times_used');
        }

        if ($this->type === 'publication') {
            $order = $accessService->grantPublicationAccess(
                auth()->user(),
                $this->item,
                grantedBy: auth()->user()->email,
                isFree: false,
                squarePaymentId: $paymentId,
                amountPaid: $amountPaid,
                discountCode: $this->appliedDiscount?->code,
                deliveryAddress: $this->deliveryAddressPayload(),
            );
            $redirectUrl = route('publications.viewer', $this->item->slug);
            $this->notifyOrderPlaced($order, 'purchase');
        } else {
            $order = $accessService->grantSeasonSubscription(
                auth()->user(),
                $this->item,
                grantedBy: auth()->user()->email,
                isFree: false,
                squarePaymentId: $paymentId,
                amountPaid: $amountPaid,
                deliveryAddress: $this->deliveryAddressPayload(),
            );
            $redirectUrl = route('seasons.show', $this->item->slug);
            $this->notifyOrderPlaced($order, 'subscription');
        }

        $this->dispatch('payment-complete', url: $redirectUrl);
    }

    private function notifyOrderPlaced($order, string $type): void
    {
        try {
            $order->load($type === 'purchase' ? ['user', 'publication.season'] : ['user', 'season']);
            Mail::to(AppSetting::orderNotificationEmail())->send(new OrderPlaced($order, $type));
        } catch (\Throwable $e) {
            Log::error('Order notification email failed', [
                'order_type' => $type,
                'order_id' => $order->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getOriginalPrice(): float
    {
        return $this->type === 'publication' ? (float) $this->item->price : (float) $this->item->subscription_price;
    }

    /**
     * Whether the current order would include at least one printed magazine.
     *
     * - Publication checkout: true when the publication is not flagged as digital only.
     * - Subscription checkout: true when the season still has any print + digital titles published.
     */
    public function checkoutIncludesPrintedCopy(): bool
    {
        if (! $this->item) {
            return false;
        }

        if ($this->type === 'publication') {
            return $this->item instanceof Publication && $this->item->offersPrintedCopy();
        }

        return $this->item instanceof Season && $this->item->hasPublishedPrintTitles();
    }

    protected function validateDeliveryAddress(): void
    {
        if (! $this->checkoutIncludesPrintedCopy()) {
            return;
        }

        $this->validate([
            'delivery_name' => 'required|string|max:255',
            'delivery_address_line_1' => 'required|string|max:255',
            'delivery_address_line_2' => 'nullable|string|max:255',
            'delivery_city' => 'required|string|max:255',
            'delivery_county' => 'nullable|string|max:255',
            'delivery_postcode' => 'required|string|max:20',
            'delivery_country' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                $normalised = strtolower(trim((string) $value));
                $allowed = ['united kingdom', 'uk', 'great britain', 'gb', 'england', 'scotland', 'wales', 'northern ireland'];

                if (! in_array($normalised, $allowed, true)) {
                    $fail('Printed copies are available for UK delivery only.');
                }
            }],
            'delivery_phone' => 'nullable|string|max:50',
        ], [], [
            'delivery_name' => 'delivery name',
            'delivery_address_line_1' => 'address line 1',
            'delivery_address_line_2' => 'address line 2',
            'delivery_city' => 'town / city',
            'delivery_county' => 'county',
            'delivery_postcode' => 'postcode',
            'delivery_country' => 'country',
            'delivery_phone' => 'phone number',
        ]);
    }

    protected function validateTermsAcceptance(): void
    {
        $this->validate([
            'terms_accepted' => 'accepted',
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms & Conditions before payment.',
        ]);
    }

    protected function deliveryAddressPayload(): ?array
    {
        if (! $this->checkoutIncludesPrintedCopy()) {
            return null;
        }

        return [
            'name' => trim($this->delivery_name),
            'address_line_1' => trim($this->delivery_address_line_1),
            'address_line_2' => trim($this->delivery_address_line_2) ?: null,
            'city' => trim($this->delivery_city),
            'county' => trim($this->delivery_county) ?: null,
            'postcode' => strtoupper(trim($this->delivery_postcode)),
            'country' => trim($this->delivery_country),
            'phone' => trim($this->delivery_phone) ?: null,
        ];
    }

    public function getFinalPrice(): float
    {
        $price = $this->getOriginalPrice();
        if ($this->discountAmount) {
            $price = max(0, $price - $this->discountAmount);
        }

        return round($price, 2);
    }

    public function render()
    {
        return view('livewire.checkout')->layout('layouts.app', ['title' => 'Checkout']);
    }
}
