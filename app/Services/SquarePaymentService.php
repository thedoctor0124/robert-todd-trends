<?php

namespace App\Services;

use Square\Environments;
use Square\Exceptions\SquareApiException;
use Square\Payments\Requests\CreatePaymentRequest;
use Square\SquareClient;
use Square\Types\Money;

class SquarePaymentService
{
    protected SquareClient $client;

    public function __construct()
    {
        $environment = config('services.square.environment') === 'production'
            ? Environments::Production
            : Environments::Sandbox;

        $this->client = new SquareClient(
            token: config('services.square.access_token'),
            options: [
                'baseUrl' => $environment->value,
            ],
        );
    }

    public function charge(string $sourceId, int $amountCents, string $currency = 'GBP', ?string $note = null): array
    {
        $money = new Money([
            'amount' => $amountCents,
            'currency' => $currency,
        ]);

        $request = new CreatePaymentRequest([
            'sourceId' => $sourceId,
            'idempotencyKey' => uniqid('lt_', true),
            'amountMoney' => $money,
            'locationId' => config('services.square.location_id'),
            'autocomplete' => true,
            'note' => $note,
        ]);

        try {
            $response = $this->client->payments->create($request);
            $payment = $response->getPayment();

            return [
                'id' => $payment->getId(),
                'status' => $payment->getStatus(),
                'amount' => $payment->getAmountMoney()->getAmount(),
            ];
        } catch (SquareApiException $e) {
            throw new \Exception('Square payment failed: '.$e->getMessage());
        }
    }
}
