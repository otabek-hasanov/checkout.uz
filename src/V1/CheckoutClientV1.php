<?php

namespace Stoyishi\CheckoutUz\V1;

use Stoyishi\CheckoutUz\Api\Balance;
use Stoyishi\CheckoutUz\Api\HttpClient;
use Stoyishi\CheckoutUz\Api\Payment;
use Stoyishi\CheckoutUz\Api\PaymentStatus;
use Stoyishi\CheckoutUz\Api\Stats;
use Stoyishi\CheckoutUz\Exceptions\ApiException;

/**
 * Client for checkout.uz API v1 (https://checkout.uz/api/v1).
 */
class CheckoutClientV1
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
     * Start a direct card payment (step 1: sends SMS code to the card holder).
     *
     * @throws ApiException
     */
    public function payViaCard(int $orderId, string $cardNumber, string $cardExpiry): array
    {
        $response = $this->http->post('/pay_via_card', [
            'order_id'    => $orderId,
            'card_number' => $cardNumber,
            'card_expiry' => $cardExpiry,
        ]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to start card payment: ' . json_encode($response));
        }

        return $response;
    }

    /**
     * Confirm a direct card payment (step 2: SMS code).
     *
     * @throws ApiException
     */
    public function confirmCardPayment(string $paymentToken, string $smsCode): array
    {
        $response = $this->http->post('/confirm_card_payment', [
            'payment_token' => $paymentToken,
            'sms_code'      => $smsCode,
        ]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to confirm card payment: ' . json_encode($response));
        }

        return $response;
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
     * Fetch the fiscal (tax) receipt for a paid order (Payme/Click only on v1).
     *
     * @throws ApiException
     */
    public function getFiscal(int $orderId): array
    {
        $response = $this->http->post('/get_fiscal', ['order_id' => $orderId]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get fiscal receipt: ' . json_encode($response));
        }

        return $response;
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
