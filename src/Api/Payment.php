<?php

namespace Stoyishi\CheckoutUz\Api;

class Payment
{
    private int    $id;
    private string $uuid;
    private string $url;
    private float  $amount;
    private string $status;
    private array  $lifetime;

    public function __construct(array $data)
    {
        $this->id       = (int)   $data['_id'];
        $this->uuid     = (string)$data['_uuid'];
        $this->url      = (string)$data['_url'];
        $this->amount   = (float) $data['_amount'];
        $this->status   = (string)$data['_status'];
        $this->lifetime = $data['_lifteme'] ?? [];
    }

    public function getId(): int      { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getUrl(): string  { return $this->url; }
    public function getAmount(): float{ return $this->amount; }
    public function getStatus(): string { return $this->status; }
    public function getLifetime(): array { return $this->lifetime; }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isPaid(): bool    { return $this->status === 'paid'; }
}