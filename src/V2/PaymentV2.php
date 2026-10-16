<?php

namespace Stoyishi\CheckoutUz\V2;

class PaymentV2
{
    private int     $id;
    private string  $uuid;
    private string  $url;
    private float   $amount;
    private string  $status;
    private array   $payVia;
    private ?string $returnUrl;
    private array   $lifetime;
    private bool    $idempotentReplay;

    public function __construct(array $payment, bool $idempotentReplay = false)
    {
        $this->id               = (int)   $payment['_id'];
        $this->uuid              = (string)$payment['_uuid'];
        $this->url               = (string)$payment['_url'];
        $this->amount            = (float) $payment['_amount'];
        $this->status            = (string)$payment['_status'];
        $this->payVia            = $payment['_pay_via'] ?? [];
        $this->returnUrl         = $payment['_return_url'] ?? null;
        $this->lifetime          = $payment['_lifteme'] ?? [];
        $this->idempotentReplay  = $idempotentReplay;
    }

    public function getId(): int         { return $this->id; }
    public function getUuid(): string    { return $this->uuid; }
    public function getUrl(): string     { return $this->url; }
    public function getAmount(): float   { return $this->amount; }
    public function getStatus(): string  { return $this->status; }
    public function getReturnUrl(): ?string { return $this->returnUrl; }
    public function getLifetime(): array { return $this->lifetime; }

    /** Payment-system-specific checkout URLs, e.g. ['payme' => '...', 'click' => '...'] */
    public function getPayVia(): array   { return $this->payVia; }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isPaid(): bool    { return $this->status === 'paid'; }

    /**
     * True if this response is a replay of a previously created invoice,
     * returned because the same Idempotency-Key was reused.
     */
    public function isIdempotentReplay(): bool { return $this->idempotentReplay; }
}
