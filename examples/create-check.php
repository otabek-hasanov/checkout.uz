<?php

/**
 * Example: To'lov yaratish va holatini tekshirish
 */

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\Api\CheckoutClient;
use Stoyishi\CheckoutUz\Exceptions\ApiException;

$client = new CheckoutClient('YOUR_API_KEY');

// --- To'lov yaratish ---
try {
    $payment = $client->createPayment(50000, 'Buyurtma #12345');

    echo "To'lov yaratildi:\n";
    echo "  ID     : " . $payment->getId() . "\n";
    echo "  UUID   : " . $payment->getUuid() . "\n";
    echo "  URL    : " . $payment->getUrl() . "\n";
    echo "  Summa  : " . $payment->getAmount() . " UZS\n";
    echo "  Status : " . $payment->getStatus() . "\n\n";

    // Foydalanuvchini to'lov sahifasiga yo'naltirish
    // header('Location: ' . $payment->getUrl());

} catch (ApiException $e) {
    echo "Xato: " . $e->getMessage() . "\n";
    exit(1);
}

// --- To'lov holatini tekshirish (ID orqali) ---
try {
    $status = $client->getStatusById($payment->getId());

    echo "To'lov holati:\n";
    echo "  Status     : " . $status->getStatus() . "\n";
    echo "  Yaratilgan : " . $status->getCreatedAt() . "\n";

    if ($status->isPaid()) {
        echo "  To'langan  : " . $status->getPaidAt() . "\n";
        echo "  >>> Buyurtma tasdiqlandi!\n";
    } else {
        echo "  >>> Hali to'lanmagan.\n";
    }

} catch (ApiException $e) {
    echo "Xato: " . $e->getMessage() . "\n";
}

// --- Balans va statistika ---
try {
    $balance = $client->getBalance();

    echo "\nBalans:\n";
    echo "  UZS : " . $balance->getUzs() . "\n";
    echo "  USD : " . $balance->getUsd() . "\n";
    echo "  TON : " . $balance->getTon() . "\n";

    $stats = $client->getStats();

    echo "\nStatistika:\n";
    echo "  Jami buyurtmalar : " . $stats->getTotalOrders() . "\n";
    echo "  Jami summa       : " . $stats->getTotalAmount() . " UZS\n";

} catch (ApiException $e) {
    echo "Xato: " . $e->getMessage() . "\n";
}