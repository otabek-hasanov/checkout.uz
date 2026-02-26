# Checkout.uz PHP SDK

PHP 7.4+ SDK for [checkout.uz](https://checkout.uz) payment gateway.

---

## Yuklab olishh

```bash
composer require stoyishi/checkout-uz
```

---

## Kerakli PHP versiya 

- PHP >= 7.4
- ext-curl
- ext-json

---

## Tezkor boshlash

```php
<?php

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\Api\CheckoutClient;

$client = new CheckoutClient('YOUR_API_KEY');
```

---

## API Usage

### To`lov ytaratish

```php
$payment = $client->createPayment(50000, 'Order #12345');

echo $payment->getId();     // 152
echo $payment->getUuid();   // 550e8400-e29b-41d4-a716-446655440000
echo $payment->getUrl();    // https://checkout.uz/pay/...
echo $payment->getAmount(); // 50000
echo $payment->getStatus(); // pending

// foydalanuvchini to`lov saxifasiga yuborish
header('Location: ' . $payment->getUrl());
```

### Check Payment Status

```php
// ID orqali
$status = $client->getStatusById(152);

// UUID orqali
$status = $client->getStatusByUuid('550e8400-e29b-41d4-a716-446655440000');

echo $status->getStatus();    // paid
echo $status->getCreatedAt(); // 2026-01-31 10:00:00
echo $status->getPaidAt();    // 2026-01-31 10:05:22

if ($status->isPaid()) {
    // buyurtma to`langan
}
```

### Balansni tekshirish

```php
$balance = $client->getBalance();

echo $balance->getUzs(); // 2500000
echo $balance->getUsd(); // 120

// yoki valyuta kodi orqali
echo $balance->get('uzs');

// Barcha valyutalar
print_r($balance->all());
```

### Tranzaksiyalar tarixi

```php
$history = $client->getHistory(20); // limit = 20

foreach ($history as $tx) {
    echo $tx['id'] . ' — ' . $tx['amount'];
}
```

### statistikani olish

```php
$stats = $client->getStats();

echo $stats->getTotalOrders(); // 450
echo $stats->getTotalAmount(); // 12500000.5
```

---

## Webhook Handling

checkout.uz sizga webhook malumot yuborganida uni ushbu ko'rinishda qabul qilib olasiz

### Webhook endpoint (`webhook.php`)

```php
<?php

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\Webhook\WebhookHandler;
use Stoyishi\CheckoutUz\Exceptions\WebhookException;

$handler = new WebhookHandler();

try {
    $payload = $handler->capture();

    if ($payload->isSuccess() && $payload->isPaymentConfirmed()) {

        $orderId  = $payload->getOrderId();   // 3
        $amount   = $payload->getAmount();    // 5000
        $currency = $payload->getCurrency();  // UZS
        $system   = $payload->getPaymentSystem(); // click

        // provider_details dagi malumotlarni olish
        $details = $payload->getProviderDetails();

        echo $details->service_id;       // 78828
        echo $details->click_trans_id;   // 3529269357
        echo $details->sign_string;      // 7826a6940de7...
        echo $details->error;            // 0
        echo $details->error_note;       // Success
        echo $details->amount;           // 5000
        echo $details->action;           // 1

        //buyerda sizning logikangiz bo`ladi misol uchun:

        // markOrderAsPaid($orderId, $amount);
    }

    $handler->respond(); // Send HTTP 200 back to checkout.uz

} catch (WebhookException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## Error Handling

All methods throw `Stoyishi\CheckoutUz\Exceptions\ApiException` on failure.

```php
use Stoyishi\CheckoutUz\Exceptions\ApiException;
use Stoyishi\CheckoutUz\Exceptions\WebhookException;

try {
    $payment = $client->createPayment(50000);
} catch (ApiException $e) {
    echo $e->getMessage();
}
```

---

## Author

**Hasanov Otabek** — [t.me/stoyishi](https://t.me/stoyishi) — otabek@hasanov.uz
