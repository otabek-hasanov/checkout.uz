<?php

namespace Stoyishi\CheckoutUz\Api;

class PaymentStatus
{
    private int    $id;
    private float  $amount;
    private string $status;
    private string $createdAt;
    private ?string $paidAt;

    public function __construct(array $data)
    {
        $this->id        = (int)   $data['id'];
        $this->amount    = (float) $data['amount'];
        $this->status    = (string)$data['status'];
        $this->createdAt = (string)$data['created_at'];
        $this->paidAt    = $data['paid_at'] ?? null;
    }

    public function getId(): int        { return $this->id; }
    public function getAmount(): float  { return $this->amount; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getPaidAt(): ?string   { return $this->paidAt; }

    public function isPaid(): bool    { return $this->status === 'paid'; }
    public function isPending(): bool { return $this->status === 'pending'; }
}