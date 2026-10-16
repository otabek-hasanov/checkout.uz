<?php

namespace Stoyishi\CheckoutUz\V2;

use Stoyishi\CheckoutUz\Api\Balance;
use Stoyishi\CheckoutUz\Api\HttpClient;
use Stoyishi\CheckoutUz\Exceptions\ApiException;

/**
 * Client for checkout.uz API v2 (https://checkout.uz/api/v2).
 *
 * v2 adds: optional expire_time/webhook_url/return_url/idempotency_key on
 * create_payment, refund_payment/cancel_payment, richer status_payment
 * (provider/description), and account-level ("profile") endpoints.
 *
 * Pass your SHOP api_key for shop-scoped methods (payments, balance, history,
 * stats, withdrawals history, fiscal receipts) and your ACCOUNT-level
 * profile_api_key for profile-scoped methods (verifyCard,
 * createWithdrawalRequest, getProfile, getShops, getProfileStats) - see the
 * docblock on each method below.
 */
class CheckoutClientV2
{
    private const BASE_URL = 'https://checkout.uz/api/v2';

    private HttpClient $http;

    public function __construct(string $apiKey, int $timeout = 30)
    {
        $this->http = new HttpClient(self::BASE_URL, $apiKey, $timeout);
    }

    // ==================== To'lovlar ====================

    /**
     * Create a new payment invoice. Auth: shop.
     *
     * $options supports:
     *  - expire_time (int, seconds, 60-432000, default 3600)
     *  - webhook_url (string)
     *  - return_url (string)
     *  - idempotency_key (string) - sent as an "Idempotency-Key" header, NOT
     *    in the body. If the same key was used for this shop within the last
     *    24 hours, the previously created invoice is returned instead of a
     *    new one (see PaymentV2::isIdempotentReplay()).
     *
     * @throws ApiException
     */
    public function createPayment(float $amount, string $description = '', array $options = []): PaymentV2
    {
        $body = ['amount' => $amount];

        if ($description !== '') {
            $body['description'] = $description;
        }
        foreach (['expire_time', 'webhook_url', 'return_url'] as $key) {
            if (array_key_exists($key, $options)) {
                $body[$key] = $options[$key];
            }
        }

        $headers = [];
        if (!empty($options['idempotency_key'])) {
            $headers['Idempotency-Key'] = (string) $options['idempotency_key'];
        }

        $response = $this->http->post('/create_payment', $body, $headers);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to create payment: ' . json_encode($response));
        }

        return new PaymentV2($response['payment'], (bool) ($response['idempotent_replay'] ?? false));
    }

    /** Check payment status by ID. Auth: shop. @throws ApiException */
    public function getStatusById(int $id): PaymentStatusV2
    {
        return $this->fetchStatus(['id' => $id]);
    }

    /** Check payment status by UUID. Auth: shop. @throws ApiException */
    public function getStatusByUuid(string $uuid): PaymentStatusV2
    {
        return $this->fetchStatus(['uuid' => $uuid]);
    }

    /**
     * Start a direct card payment (step 1: sends SMS code to the card holder).
     * Auth: shop. @throws ApiException
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
     * Auth: shop. @throws ApiException
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

    // ==================== Qaytarish va bekor qilish ====================

    /**
     * Refund a paid order (Plum/Payme/Click only, within 22h of payment).
     * Auth: shop. @throws ApiException
     */
    public function refundPayment(int $orderId): array
    {
        $response = $this->http->post('/refund_payment', ['order_id' => $orderId]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to refund payment: ' . json_encode($response));
        }

        return $response;
    }

    /**
     * Cancel a pending (unpaid) invoice. Auth: shop. @throws ApiException
     */
    public function cancelPayment(int $orderId): array
    {
        $response = $this->http->post('/cancel_payment', ['order_id' => $orderId]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to cancel payment: ' . json_encode($response));
        }

        return $response;
    }

    // ==================== Hisobot ====================

    /** Get merchant balance. Auth: shop. @throws ApiException */
    public function getBalance(): Balance
    {
        $response = $this->http->post('/get_balance');

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get balance: ' . json_encode($response));
        }

        return new Balance($response['balance']);
    }

    /**
     * Get transaction history. Auth: shop.
     *
     * @return array Raw response (see /api-docs/v2 for shape)
     * @throws ApiException
     */
    public function getHistory(int $limit = 10, int $offset = 0, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->http->post('/get_history', array_filter([
            'limit'     => $limit,
            'offset'    => $offset,
            'status'    => $status,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], static fn ($v) => $v !== null));
    }

    /**
     * Get merchant statistics. Auth: shop.
     *
     * @return array Raw response (see /api-docs/v2 for shape)
     * @throws ApiException
     */
    public function getStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->http->post('/get_stats', array_filter([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], static fn ($v) => $v !== null));
    }

    /** Get active payment methods for this shop. Auth: shop. @throws ApiException */
    public function getPaymentMethods(): array
    {
        return $this->http->post('/get_payment_methods');
    }

    // ==================== Pul yechish ====================

    /** Get available withdrawal methods. Auth: shop or profile. @throws ApiException */
    public function getWithdrawalMethods(?string $currency = null): array
    {
        $body = $currency !== null ? ['currency' => $currency] : [];
        return $this->http->post('/get_withdrawal_methods', $body);
    }

    /**
     * Verify that a card belongs to the authenticated account owner before
     * withdrawing to it. Auth: profile. @throws ApiException
     */
    public function verifyCard(string $cardNumber): array
    {
        return $this->http->post('/verify_card', ['card_number' => $cardNumber]);
    }

    /**
     * Create a withdrawal request. Auth: profile (dual-key: profile_api_key
     * via Authorization header + the target shop's own api_key as
     * $shopToken, to prove you also control that specific shop).
     *
     * $fields must match the withdrawal method's required field names
     * (see getWithdrawalMethods()), e.g. ['card_number' => '8600...'].
     *
     * @throws ApiException
     */
    public function createWithdrawalRequest(int $shopId, string $shopToken, int $methodId, float $amount, string $currency, array $fields): array
    {
        $response = $this->http->post('/create_withdrawal_request', [
            'shop_id'    => $shopId,
            'shop_token' => $shopToken,
            'method_id'  => $methodId,
            'amount'     => $amount,
            'currency'   => $currency,
            'fields'     => $fields,
        ]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to create withdrawal request: ' . json_encode($response));
        }

        return $response;
    }

    /**
     * Get withdrawal request history for this shop. Auth: shop.
     *
     * @throws ApiException
     */
    public function getWithdrawals(int $limit = 10, int $offset = 0, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->http->post('/get_withdrawals', array_filter([
            'limit'     => $limit,
            'offset'    => $offset,
            'status'    => $status,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], static fn ($v) => $v !== null));
    }

    // ==================== Fiskal chek ====================

    /** Fetch the fiscal (tax) receipt for a paid order. Auth: shop. @throws ApiException */
    public function getFiscal(int $orderId): array
    {
        $response = $this->http->post('/get_fiscal', ['order_id' => $orderId]);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get fiscal receipt: ' . json_encode($response));
        }

        return $response;
    }

    // ==================== Profil (hisob darajasida) ====================

    /** Get the authenticated account's profile. Auth: profile. @throws ApiException */
    public function getProfile(): array
    {
        return $this->http->post('/get_profile');
    }

    /** List all shops owned by the authenticated account. Auth: profile. @throws ApiException */
    public function getShops(): array
    {
        return $this->http->post('/get_shops');
    }

    /** Aggregated statistics across all of the account's shops. Auth: profile. @throws ApiException */
    public function getProfileStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->http->post('/get_profile_stats', array_filter([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], static fn ($v) => $v !== null));
    }

    /** @throws ApiException */
    private function fetchStatus(array $body): PaymentStatusV2
    {
        $response = $this->http->post('/status_payment', $body);

        if (($response['status'] ?? '') !== 'success') {
            throw new ApiException('Failed to get payment status: ' . json_encode($response));
        }

        return new PaymentStatusV2($response['data']);
    }
}
