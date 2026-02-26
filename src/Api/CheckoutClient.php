<?php

namespace Stoyishi\CheckoutUz\Api;

use Stoyishi\CheckoutUz\Exceptions\ApiException;

class CheckoutClient
{
    private const BASE_URL = 'https://checkout.uz/api/v1';

    private HttpClient $http;

    public function __construct(string $apiKey, int $timeout = 30)
    {
        $this->http = new HttpClient(self::BASE_URL, $apiKey, $timeout);
    }

    /**
     * Create a new payment invoice.
     *
     * @throws ApiException
     */
    public function createPayment(float $amount, string $description = ''): Payment
    {
        $body = ['amount' => $amount];

        if ($description !== '') {
            $body['description'] = $description;
        }

        $response = $this->http->post('/create_payment', $body);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to create payment: ' . json_encode($response));
        }

        return new Payment($response['payment']);
    }

    /**
     * Check payment status by ID.
     *
     * @throws ApiException
     */
    public function getStatusById(int $id): PaymentStatus
    {
        return $this->fetchStatus(['id' => $id]);
    }

    /**
     * Check payment status by UUID.
     *
     * @throws ApiException
     */
    public function getStatusByUuid(string $uuid): PaymentStatus
    {
        return $this->fetchStatus(['uuid' => $uuid]);
    }

    /**
     * Get merchant balance.
     *
     * @throws ApiException
     */
    public function getBalance(): Balance
    {
        $response = $this->http->post('/get_balance');

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get balance: ' . json_encode($response));
        }

        return new Balance($response['balance']);
    }

    /**
     * Get transaction history.
     *
     * @return array Raw transactions array
     * @throws ApiException
     */
    public function getHistory(int $limit = 10): array
    {
        $response = $this->http->post('/get_history', ['limit' => $limit]);

        return $response['data'] ?? $response;
    }

    /**
     * Get merchant statistics.
     *
     * @throws ApiException
     */
    public function getStats(): Stats
    {
        $response = $this->http->post('/get_stats');

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get stats: ' . json_encode($response));
        }

        return new Stats($response['stats']);
    }

    /**
     * @throws ApiException
     */
    private function fetchStatus(array $body): PaymentStatus
    {
        $response = $this->http->post('/status_payment', $body);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get payment status: ' . json_encode($response));
        }

        return new PaymentStatus($response['data']);
    }
}