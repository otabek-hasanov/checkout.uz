<?php

namespace Stoyishi\CheckoutUz\Api;

class Balance
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get balance by currency code (e.g. 'uzs', 'usd').
     */
    public function get(string $currency): float
    {
        $key = strtolower($currency);
        return isset($this->data[$key]) ? (float)$this->data[$key] : 0.0;
    }

    public function getUzs(): float { return $this->get('uzs'); }
    public function getUsd(): float { return $this->get('usd'); }

    public function all(): array { return $this->data; }
}