<?php

namespace Stoyishi\CheckoutUz\Webhook;

/**
 * Dynamic wrapper for provider_details.
 * Access any field via property syntax: $details->service_id, $details->sign_string, etc.
 */
class ProviderDetails
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Example: $details->service_id, $details->click_trans_id
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Get all raw data.
     */
    public function all(): array
    {
        return $this->data;
    }
}