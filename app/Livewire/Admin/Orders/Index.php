<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Purchase;
use App\Models\Subscription;
use Livewire\Component;

class Index extends Component
{
    public string $filter = 'all'; // all, purchases, subscriptions

    public function render()
    {
        $purchases = collect();
        $subscriptions = collect();

        if ($this->filter !== 'subscriptions') {
            $purchases = Purchase::with('user', 'publication.season')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($p) => (object) [
                    'type' => 'Purchase',
                    'user_name' => $p->user->name,
                    'user_email' => $p->user->email,
                    'item' => $p->publication->title,
                    'season' => $p->publication->season->name,
                    'amount' => $p->amount_paid,
                    'is_free' => $p->is_free,
                    'payment_id' => $p->square_payment_id,
                    'discount' => $p->discount_code_used,
                    'delivery_required' => $p->delivery_required,
                    'delivery_name' => $p->delivery_name,
                    'delivery_address' => $this->formatDeliveryAddress($p),
                    'delivery_phone' => $p->delivery_phone,
                    'date' => $p->created_at,
                ]);
        }

        if ($this->filter !== 'purchases') {
            $subscriptions = Subscription::with('user', 'season')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($s) => (object) [
                    'type' => 'Subscription',
                    'user_name' => $s->user->name,
                    'user_email' => $s->user->email,
                    'item' => $s->season->name.' ('.$s->season->year.')',
                    'season' => $s->season->name,
                    'amount' => $s->amount_paid,
                    'is_free' => $s->is_free,
                    'payment_id' => $s->square_payment_id,
                    'discount' => null,
                    'delivery_required' => $s->delivery_required,
                    'delivery_name' => $s->delivery_name,
                    'delivery_address' => $this->formatDeliveryAddress($s),
                    'delivery_phone' => $s->delivery_phone,
                    'date' => $s->created_at,
                ]);
        }

        $orders = $purchases->merge($subscriptions)->sortByDesc('date');

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
        ])->layout('layouts.admin', ['title' => 'Orders']);
    }

    private function formatDeliveryAddress(Purchase|Subscription $order): string
    {
        return collect([
            $order->delivery_address_line_1,
            $order->delivery_address_line_2,
            $order->delivery_city,
            $order->delivery_county,
            $order->delivery_postcode,
            $order->delivery_country,
        ])->filter()->implode(', ');
    }
}
