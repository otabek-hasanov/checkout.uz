<?php

/**
 * Example: API v2 - to'lov yaratish (idempotency bilan), holatini
 * tekshirish, qaytarish va profil darajasidagi endpointlar.
 */

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\V2\CheckoutClientV2;
use Stoyishi\CheckoutUz\Exceptions\ApiException;

$client = new CheckoutClientV2('YOUR_SHOP_API_KEY');

// --- To'lov yaratish (idempotency_key bilan - tarmoq xatosi bo'lsa
//     qayta yuborilganda ikki marta invoice ochilmaydi) ---
try {
    $payment = $client->createPayment(50000, 'Buyurtma #12345', [
        'expire_time'     => 86400, // 24 soat
        'return_url'      => 'https://yoursite.com/thank-you',
        'idempotency_key' => 'order-12345', // o'zingizning buyurtma ID'ingiz
    ]);

    echo "To'lov yaratildi:\n";
    echo "  ID      : " . $payment->getId() . "\n";
    echo "  URL     : " . $payment->getUrl() . "\n";
    echo "  Pay via : " . json_encode($payment->getPayVia()) . "\n";
    echo "  Replay  : " . ($payment->isIdempotentReplay() ? "ha (eski invoice qaytdi)" : "yo'q (yangi yaratildi)") . "\n\n";

} catch (ApiException $e) {
    echo "Xato: " . $e->getMessage() . "\n";
    exit(1);
}

// --- Holatini tekshirish (v2'da provider + description ham qaytadi) ---
try {
    $status = $client->getStatusById($payment->getId());

    echo "Holat     : " . $status->getStatus() . "\n";
    echo "Provider  : " . ($status->getProvider() ?? '-') . "\n";
    echo "Izoh      : " . $status->getDescription() . "\n";

} catch (ApiException $e) {
    echo "Xato: " . $e->getMessage() . "\n";
}

// --- To'langan buyurtmani bekor qilish / qaytarish (YANGI, faqat v2'da) ---
// $client->cancelPayment($payment->getId());   // hali to'lanmagan bo'lsa
// $client->refundPayment($payment->getId());   // to'langan bo'lsa (22 soat ichida)

// --- Profil darajasidagi endpointlar (profile_api_key talab qiladi) ---
$profileClient = new CheckoutClientV2('YOUR_PROFILE_API_KEY');

try {
    $shops = $profileClient->getShops();
    echo "\nKassalaringiz: " . json_encode($shops) . "\n";
} catch (ApiException $e) {
    echo "Xato: " . $e->getMessage() . "\n";
}
