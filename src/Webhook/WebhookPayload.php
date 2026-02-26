<?php

namespace Stoyishi\CheckoutUz\Webhook;

/**
 * Represents the parsed webhook payload sent by checkout.uz.
 */
class WebhookPayload
{
    private string $webhookType;
    private string $status;
    private string $event;
    private string $paymentSystem;
    private int    $shopId;
    private array  $rawData;
    private int    $timestamp;

    // Order data
    private int    $orderId;
    private float  $amount;
    private string $currency;
    private string $paymentStatus;
    private int    $merchantPrepare;
    private int    $performTime;

    private ProviderDetails $providerDetails;

    public function __construct(array $payload)
    {
        $this->webhookType   = (string)($payload['webhook_type']   ?? '');
        $this->status        = (string)($payload['status']         ?? '');
        $this->event         = (string)($payload['event']          ?? '');
        $this->paymentSystem = (string)($payload['payment_system'] ?? '');
        $this->shopId        = (int)   ($payload['shop_id']        ?? 0);
        $this->timestamp     = (int)   ($payload['timestamp']      ?? 0);

        $data = $payload['data'] ?? [];
        $this->rawData         = $data;
        $this->orderId         = (int)  ($data['order_id']        ?? 0);
        $this->amount          = (float)($data['amount']          ?? 0);
        $this->currency        = (string)($data['currency']       ?? '');
        $this->paymentStatus   = (string)($data['status']         ?? '');

        $this->providerDetails = new ProviderDetails($data['provider_details'] ?? []);
    }

    public function getWebhookType(): string   { return $this->webhookType; }
    public function getStatus(): string        { return $this->status; }
    public function getEvent(): string         { return $this->event; }
    public function getPaymentSystem(): string { return $this->paymentSystem; }
    public function getShopId(): int           { return $this->shopId; }
    public function getTimestamp(): int        { return $this->timestamp; }

    public function getOrderId(): int          { return $this->orderId; }
    public function getAmount(): float         { return $this->amount; }
    public function getCurrency(): string      { return $this->currency; }
    public function getPaymentStatus(): string { return $this->paymentStatus; }

    public function getProviderDetails(): ProviderDetails { return $this->providerDetails; }

    public function isSuccess(): bool          { return $this->status === 'success'; }
    public function isPaid(): bool             { return $this->paymentStatus === 'paid'; }
    public function isPaymentConfirmed(): bool { return $this->event === 'payment_confirmed'; }

    /** Raw data array from 'data' key */
    public function getRawData(): array { return $this->rawData; }
}