<?php

namespace Stoyishi\CheckoutUz\Webhook;

use Stoyishi\CheckoutUz\Exceptions\WebhookException;

class WebhookHandler
{
    /**
     * Parse webhook from raw PHP input (php://input).
     *
     * @throws WebhookException
     */
    public function capture(): WebhookPayload
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            throw new WebhookException('Empty webhook payload received.');
        }

        return $this->parse($raw);
    }

    /**
     * Parse webhook from a raw JSON string.
     *
     * @throws WebhookException
     */
    public function parse(string $rawJson): WebhookPayload
    {
        $data = json_decode($rawJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookException('Invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new WebhookException('Webhook payload must be a JSON object.');
        }

        return new WebhookPayload($data);
    }

    /**
     * Respond to checkout.uz with HTTP 200 OK and a JSON success body.
     * Call this after you have successfully processed the webhook.
     */
    public function respond(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(200);
        }

        echo json_encode(['status' => 'ok']);
    }
}