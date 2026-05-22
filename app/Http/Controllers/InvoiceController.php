<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function purchase(Purchase $purchase): Response
    {
        $purchase->load('user', 'publication.season');
        $this->authorizeOrderAccess($purchase->user_id);

        return $this->download($this->invoiceData(
            type: 'purchase',
            id: $purchase->id,
            date: $purchase->created_at,
            customerName: $purchase->user->name,
            customerEmail: $purchase->user->email,
            item: $purchase->publication->title,
            description: $purchase->publication->is_digital_only ? 'Digital publication access' : 'Print + digital publication access',
            transactionId: $purchase->square_payment_id,
            amountPaid: (float) $purchase->amount_paid,
            discountCode: $purchase->discount_code_used,
            deliveryRequired: (bool) $purchase->delivery_required,
            deliveryAddress: $this->deliveryAddress($purchase),
        ));
    }

    public function subscription(Subscription $subscription): Response
    {
        $subscription->load('user', 'season');
        $this->authorizeOrderAccess($subscription->user_id);

        return $this->download($this->invoiceData(
            type: 'subscription',
            id: $subscription->id,
            date: $subscription->created_at,
            customerName: $subscription->user->name,
            customerEmail: $subscription->user->email,
            item: $subscription->season->name.' '.$subscription->season->year,
            description: 'Season subscription access',
            transactionId: $subscription->square_payment_id,
            amountPaid: (float) $subscription->amount_paid,
            discountCode: null,
            deliveryRequired: (bool) $subscription->delivery_required,
            deliveryAddress: $this->deliveryAddress($subscription),
        ));
    }

    private function authorizeOrderAccess(int $userId): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->id() === $userId || auth()->user()->is_admin, 403);
    }

    private function download(array $data): Response
    {
        $pdf = Pdf::loadView('invoices.vat-invoice', $data)->setPaper('a4');

        return $pdf->download($data['invoiceNumber'].'.pdf');
    }

    private function invoiceData(
        string $type,
        int $id,
        $date,
        string $customerName,
        string $customerEmail,
        string $item,
        string $description,
        ?string $transactionId,
        float $amountPaid,
        ?string $discountCode,
        bool $deliveryRequired,
        string $deliveryAddress,
    ): array {
        $vatRate = (float) config('company.vat_rate', 20);
        $gross = round($amountPaid, 2);
        $net = $vatRate > 0 ? round($gross / (1 + ($vatRate / 100)), 2) : $gross;
        $vat = round($gross - $net, 2);
        $prefix = $type === 'purchase' ? 'P' : 'S';

        return [
            'invoiceNumber' => 'RTT-'.$date->format('Y').'-'.$prefix.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'invoiceDate' => $date,
            'company' => config('company'),
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'item' => $item,
            'description' => $description,
            'transactionId' => $transactionId,
            'discountCode' => $discountCode,
            'deliveryRequired' => $deliveryRequired,
            'deliveryAddress' => $deliveryAddress,
            'vatRate' => $vatRate,
            'net' => $net,
            'vat' => $vat,
            'gross' => $gross,
        ];
    }

    private function deliveryAddress(Purchase|Subscription $order): string
    {
        return collect([
            $order->delivery_name,
            $order->delivery_address_line_1,
            $order->delivery_address_line_2,
            $order->delivery_city,
            $order->delivery_county,
            $order->delivery_postcode,
            $order->delivery_country,
        ])->filter()->implode("\n");
    }
}
