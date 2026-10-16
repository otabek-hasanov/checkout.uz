<?php

namespace Stoyishi\CheckoutUz\Api;

use Stoyishi\CheckoutUz\Exceptions\ApiException;

class HttpClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(string $baseUrl, string $apiKey, int $timeout = 30)
    {
        $this->baseUrl  = rtrim($baseUrl, '/');
        $this->apiKey   = $apiKey;
        $this->timeout  = $timeout;
    }

    /**
     * @param array<string, string> $extraHeaders Additional raw headers, e.g. ['Idempotency-Key' => 'order-123']
     * @throws ApiException
     */
    public function post(string $endpoint, array $body = [], array $extraHeaders = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        foreach ($extraHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new ApiException('cURL error: ' . $error);
        }

        if ($httpCode === 403) {
            throw new ApiException('Access denied: IP not whitelisted or invalid API key.');
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response from API.');
        }

        return $decoded;
    }
}
