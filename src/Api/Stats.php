<?php

namespace Stoyishi\CheckoutUz\Api;

class Stats
{
    private int   $totalOrders;
    private float $totalAmount;

    public function __construct(array $data)
    {
        $this->totalOrders = (int)  $data['total_orders'];
        $this->totalAmount = (float)$data['total_amount'];
    }

    public function getTotalOrders(): int   { return $this->totalOrders; }
    public function getTotalAmount(): float { return $this->totalAmount; }
}