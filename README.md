# Checkout.uz PHP SDK

PHP 7.4+ SDK for [checkout.uz](https://checkout.uz) payment gateway.

Qo'llab-quvvatlaydi **API v1** va **API v2** (ikkisi ham bitta paket ichida,
alohida klasslar bilan) + webhook qabul qilish.

---

## Yuklab olish

```bash
composer require stoyishi/checkout-uz
```

---

## Kerakli PHP versiya

- PHP >= 7.4
- ext-curl
- ext-json

---

## v1 yoki v2 - qaysi birini ishlatish kerak?

| | v1 | v2 |
|---|---|---|
| Klass | `V1\CheckoutClientV1` | `V2\CheckoutClientV2` |
| Asos URL | `https://checkout.uz/api/v1` | `https://checkout.uz/api/v2` |
| To'lov yaratishda `expire_time`/`webhook_url`/`return_url` | yo'q | bor |
| `idempotency_key` (takroriy so'rovdan himoya) | yo'q | bor |
| `refund_payment` / `cancel_payment` | yo'q | bor |
| `status_payment` javobida `provider`/`description` | yo'q | bor |
| Profil darajasidagi endpointlar (`get_profile`, `get_shops`) | yo'q | bor |

Yangi integratsiyalar uchun **v2** tavsiya etiladi. Eski `Api\CheckoutClient`
klassi (v1) hamon ishlaydi — orqaga moslik uchun saqlangan.

---

## v1 - Tezkor boshlash

```php
<?php

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\V1\CheckoutClientV1;

$client = new CheckoutClientV1('YOUR_API_KEY');
```

### To'lov yaratish

```php
$payment = $client->createPayment(50000, 'Order #12345');

echo $payment->getId();     // 152
echo $payment->getUuid();   // 550e8400-e29b-41d4-a716-446655440000
echo $payment->getUrl();    // https://checkout.uz/pay/...
echo $payment->getAmount(); // 50000
echo $payment->getStatus(); // pending

// foydalanuvchini to'lov sahifasiga yuborish
header('Location: ' . $payment->getUrl());
```

### To'lov holatini tekshirish

```php
// ID orqali
$status = $client->getStatusById(152);

// UUID orqali
$status = $client->getStatusByUuid('550e8400-e29b-41d4-a716-446655440000');

echo $status->getStatus();    // paid
echo $status->getCreatedAt(); // 2026-01-31 10:00:00
echo $status->getPaidAt();    // 2026-01-31 10:05:22

if ($status->isPaid()) {
    // buyurtma to'langan
}
```

### Balansni tekshirish

checkout.uz hozircha faqat **UZS** (so'm) bilan ishlaydi.

```php
$balance = $client->getBalance();

echo $balance->getUzs(); // 2500000 (UZS)

// Barcha valyutalar (xom javob)
print_r($balance->all()); // ["UZS" => 2500000] (yoki ["uzs" => ...], API versiyasiga qarab)
```

### Tranzaksiyalar tarixi

```php
$history = $client->getHistory(20); // limit = 20

foreach ($history as $tx) {
    echo $tx['id'] . ' — ' . $tx['amount'];
}
```

### Statistikani olish

```php
$stats = $client->getStats();

echo $stats->getTotalOrders(); // 450
echo $stats->getTotalAmount(); // 12500000.5 (UZS)
```

### Karta orqali to'g'ridan-to'g'ri to'lov

```php
// 1-qadam: SMS kod yuborish
$resp = $client->payViaCard($orderId, '8600123412341234', '12/28');
$paymentToken = $resp['payment_token'];

// 2-qadam: SMS kod bilan tasdiqlash
$client->confirmCardPayment($paymentToken, '123456');
```

### Fiskal (soliq) chek

```php
$fiscal = $client->getFiscal($orderId); // Payme, Click, Plum orqali to'langan buyurtmalar uchun
```

---

## v2 - Tezkor boshlash

```php
<?php

require 'vendor/autoload.php';

use Stoyishi\CheckoutUz\V2\CheckoutClientV2;

$client = new CheckoutClientV2('YOUR_SHOP_API_KEY');
```

### To'lov yaratish (idempotency bilan)

```php
$payment = $client->createPayment(50000, 'Order #12345', [
    'expire_time'     => 86400,                     // ixtiyoriy, sekundda (standart 3600)
    'webhook_url'     => 'https://site.com/webhook', // ixtiyoriy, qo'shimcha webhook
    'return_url'      => 'https://site.com/thanks',  // ixtiyoriy
    'idempotency_key' => 'order-12345',              // ixtiyoriy, lekin TAVSIYA ETILADI
]);

echo $payment->getId();
echo $payment->getUrl();
print_r($payment->getPayVia()); // ['payme' => '...', 'click' => '...']

// Agar shu idempotency_key bilan so'nggi 24 soatda allaqachon invoice
// yaratilgan bo'lsa - yangisi ochilmaydi, ESKISI qaytariladi:
if ($payment->isIdempotentReplay()) {
    // ...
}
```

`idempotency_key` berilsa, tarmoq xatosi yoki qayta urinish tufayli so'rov
ikki marta yuborilib qolsa ham, bitta buyurtma uchun ikki marta invoys
ochilmaydi — bu kalit JSON tanasida emas, `Idempotency-Key` HTTP sarlavhasi
orqali yuboriladi (SDK buni avtomatik qiladi).

### To'lov holatini tekshirish

```php
$status = $client->getStatusById(152);
// yoki: $client->getStatusByUuid('550e8400-...');

echo $status->getStatus();      // paid
echo $status->getProvider();    // click (qaysi tizim orqali to'langani)
echo $status->getDescription(); // create_payment'da berilgan izoh
```

### Qaytarish va bekor qilish (YANGI, faqat v2'da)

```php
$client->cancelPayment($orderId); // hali to'lanmagan invoysni bekor qilish
$client->refundPayment($orderId); // to'langan to'lovni qaytarish (Plum/Payme/Click, 22 soat ichida)
```

### Balans, tarix, statistika

```php
echo $client->getBalance()->getUzs();

$history = $client->getHistory(20, 0, null, '2026-10-01', '2026-10-31');
$stats   = $client->getStats('2026-10-01', '2026-10-31');
$methods = $client->getPaymentMethods();
```

### Pul yechish

```php
$methods = $client->getWithdrawalMethods('UZS');

// Profil darajasida (profile_api_key bilan alohida klient):
$profileClient = new CheckoutClientV2('YOUR_PROFILE_API_KEY');
$profileClient->verifyCard('8600123412341234');

$profileClient->createWithdrawalRequest(
    $shopId,
    $shopApiKey, // shu kassaning o'z api_key'i, qo'shimcha isbot sifatida
    $methodId,
    50000,
    'UZS',
    ['card_number' => '8600123412341234']
);

$history = $client->getWithdrawals(10); // shop-darajasida
```

### Fiskal chek

```php
$fiscal = $client->getFiscal($orderId);
```

### Profil darajasidagi endpointlar

```php
$profileClient = new CheckoutClientV2('YOUR_PROFILE_API_KEY');

$profile = $profileClient->getProfile();
$shops   = $profileClient->getShops();
$stats   = $profileClient->getProfileStats('2026-10-01', '2026-10-31');
```

---

## Webhook Handling

checkout.uz sizga webhook ma'lumot yuborganida uni ushbu ko'rinishda qabul
qilib olasiz (v1 va v2 uchun bir xil):

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

        // provider_details dagi ma'lumotlarni olish
        $details = $payload->getProviderDetails();

        echo $details->service_id;       // 78828
        echo $details->click_trans_id;   // 3529269357
        echo $details->sign_string;      // 7826a6940de7...
        echo $details->error;            // 0
        echo $details->error_note;       // Success
        echo $details->amount;           // 5000
        echo $details->action;           // 1

        // bu yerda sizning logikangiz bo'ladi, masalan:
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

Barcha metodlar xatolikda `Stoyishi\CheckoutUz\Exceptions\ApiException`
tashlaydi (v1 va v2 uchun bir xil).

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
