<?php

/**
 * Example: Webhook qabul qilish endpointi
 *
 * Bu faylni checkout.uz dashboard'ida webhook URL sifatida ko'rsating:
 * https://yoursite.com/webhook.php
 */

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\Webhook\WebhookHandler;
use Stoyishi\CheckoutUz\Exceptions\WebhookException;

$handler = new WebhookHandler();

try {
    $payload = $handler->capture();

    // Faqat muvaffaqiyatli to'lovlarni qayta ishlash
    if (!$payload->isSuccess() || !$payload->isPaymentConfirmed()) {
        $handler->respond();
        exit;
    }

    $orderId  = $payload->getOrderId();
    $amount   = $payload->getAmount();
    $currency = $payload->getCurrency();
    $system   = $payload->getPaymentSystem(); // click, payme, uzcard ...

    // provider_details — to'lov tizimi yuborgan har qanday field
    $details = $payload->getProviderDetails();

    // Click uchun misol (boshqa tizimlar o'z fieldlarini yuboradi)
    $transId    = $details->click_trans_id;
    $serviceId  = $details->service_id;
    $signString = $details->sign_string;
    $error      = $details->error;
    $errorNote  = $details->error_note;

    // Log yozish (real loyihada DB ga saqlash kerak)
    $log = sprintf(
        "[%s] Order #%d | %s %s | System: %s | Trans: %s | Error: %s (%s)\n",
        date('Y-m-d H:i:s'),
        $orderId,
        $amount,
        $currency,
        $system,
        $transId,
        $error,
        $errorNote
    );

    file_put_contents(__DIR__ . '/webhook.log', $log, FILE_APPEND);

    // Buyurtmani tasdiqlash (o'z logikangiz)
    // DB::table('orders')->where('id', $orderId)->update(['status' => 'paid']);

    // checkout.uz ga 200 OK qaytarish — majburiy!
    $handler->respond();

} catch (WebhookException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}