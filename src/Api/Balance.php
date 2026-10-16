<?php

namespace Stoyishi\CheckoutUz\Api;

class Balance
{
    private array $data;

    /** @var array<string, mixed> Same values as $data, keyed by lowercase currency code for case-insensitive lookup (v1 returns lowercase keys like "uzs", v2 returns uppercase keys like "UZS"). */
    private array $lowerKeyed;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->lowerKeyed = array_change_key_case($data, CASE_LOWER);
    }

    /**
     * Get balance by currency code - case-insensitive. checkout.uz currently
     * only operates in UZS; use getUzs() for that.
     */
    public function get(string $currency): float
    {
        $key = strtolower($currency);
        return isset($this->lowerKeyed[$key]) ? (float)$this->lowerKeyed[$key] : 0.0;
    }

    /** Balance in UZS (so'm) - the only currency checkout.uz currently supports. */
    public function getUzs(): float { return $this->get('uzs'); }

    public function all(): array { return $this->data; }
}
