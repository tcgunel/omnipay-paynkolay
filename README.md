# Omnipay: PayNKolay

**Paynkolay payment gateway driver for the Omnipay PHP payment processing library.**

[Omnipay](https://github.com/thephpleague/omnipay) is a framework-agnostic, multi-gateway payment processing library for PHP. This package implements Paynkolay support for Omnipay, including direct (3DS) payment, hosted-page Pay By Link, post-order send-link, cancel/refund, transaction listing, installment lookup, and the postback-verification handshake.

The package is built against Paynkolay's documentation at https://paynkolay.com.tr/entegrasyon/ — but it also absorbs the divergences between the published docs and the actual production payloads (notably which of the two returned signature fields is actually verifiable). See the [Doc-vs-prod quirks](#doc-vs-prod-quirks-the-package-absorbs) section.

## Installation

```bash
composer require tcgunel/omnipay-paynkolay
```

Requires PHP 8.3+ and `omnipay/common ^3.0`.

## Credentials

A merchant needs four values from Paynkolay's portal:

| Paynkolay portal label | Setter | Used as `sx` on |
|---|---|---|
| SX Token | `setSxToken()` | Purchase, CompletePurchase, MerchantInfo, BinInstallment, Pay By Link |
| SX List Token | `setSxListToken()` | PaymentList |
| SX İptal Token | `setSxCancelToken()` | Cancel, Refund |
| Merchant Secret Key | `setMerchantSecretKey()` | HMAC secret — signs every outgoing `hashDatav2` and verifies the inbound postback |

All four values are emitted by Paynkolay during merchant onboarding (`Test Ortamı Bilgileri` for sandbox, `Canlı Ortam Bilgileri` for production). Paynkolay's portal sometimes labels the secret key in older docs as "Store Key" — same thing, just an older name.

## Gateway setup

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('PayNKolay');

$gateway->setSxToken('your-sx-token-here');
$gateway->setSxListToken('your-sx-list-token-here');
$gateway->setSxCancelToken('your-sx-cancel-token-here');
$gateway->setMerchantSecretKey('your-merchant-secret-key-here');
$gateway->setTestMode(true); // sandbox base URL
```

Endpoints:

| | Sandbox | Production |
|---|---|---|
| Base URL | `https://paynkolaytest.nkolayislem.com.tr` | `https://paynkolay.nkolayislem.com.tr` |

## Methods

| Method | Endpoint | Returns |
|---|---|---|
| `purchase()` | `POST /Vpos/v1/Payment` | `PurchaseResponse` (3DS-aware) |
| `completePurchase()` | `POST /Vpos/v1/CompletePayment` | `CompletePurchaseResponse` |
| `cancel()` | `POST /Vpos/v1/CancelRefundPayment` (`type=cancel`) | `CancelResponse` |
| `refund()` | `POST /Vpos/v1/CancelRefundPayment` (`type=refund`) | `RefundResponse` |
| `binInstallment()` | `POST /Vpos/Payment/PaymentInstallments` | `BinInstallmentResponse` |
| `merchantInfo()` | `POST /Vpos/Payment/GetMerchandInformation` | `MerchantInfoResponse` (full installment table) |
| `paymentList()` | `POST /Vpos/Payment/PaymentList` | `PaymentListResponse` (transaction history) |
| `payByLink()` | `POST /Vpos/by-link-create` | `PayByLinkResponse` (redirect URL) |
| `payByLinkSend()` | `POST /Vpos/pay-by-link-create` | `PayByLinkSendResponse` (issue+deliver) |
| `payByLinkDelete()` | `POST /Vpos/by-link-url-remove` | `PayByLinkDeleteResponse` |
| `acceptNotification()` | n/a | `Notification` (parses + verifies the 3DS postback POST) |

## Direct (non-3D) payment

```php
$response = $gateway->purchase([
    'amount' => '100.00',
    'transactionId' => 'ORDER-123',  // becomes Paynkolay `clientRefCode`
    'installment' => 1,
    'card' => [
        'firstName' => 'Ada',
        'lastName' => 'Lovelace',
        'number' => '4155650100416111',
        'expiryMonth' => '01',
        'expiryYear' => '2030',
        'cvv' => '123',
        'email' => 'ada@example.com',
        'billingPhone' => '5550000000',
        'billingAddress1' => 'Address line 1',
    ],
    'clientIp' => '127.0.0.1',
    // optional buyer metadata — passed through when supplied:
    'tckn' => '12345678901',
    'description' => 'Order #123',
    'merchantCustomerNo' => 'M-9999',
])->send();

if ($response->isSuccessful()) {
    echo $response->getTransactionReference();  // REFERENCE_CODE
} else {
    echo $response->getMessage();
}
```

## 3D Secure payment

### Step 1 — initiate

```php
$response = $gateway->purchase([
    'amount' => '100.00',
    'transactionId' => 'ORDER-123',
    'installment' => 1,
    'secure' => true,
    'returnUrl' => 'https://merchant.example/orders/ORDER-123/verify-payment',
    'cancelUrl' => 'https://merchant.example/orders/ORDER-123/payment-failed',
    'card' => [ /* ... */ ],
    'clientIp' => '127.0.0.1',
])->send();

if ($response->isRedirect()) {
    // Paynkolay returns the bank's 3D Secure HTML inline as
    // BANK_REQUEST_MESSAGE (escaped). Decoded HTML is ready to emit:
    echo $response->getRedirectHtml();
}
```

### Step 2 — handle the postback

After the user authenticates with the bank, Paynkolay POSTs to `returnUrl` with the 3DS verification payload. Use `acceptNotification()` to parse it, **verify the hash before trusting any field**, and only then call `completePurchase()`:

```php
$notification = $gateway->acceptNotification($_POST);

if (! $notification->verifyHash($merchantSecretKey)) {
    return failure_view('3DS hash verification failed');
}

if (! $notification->isSuccessful()) {
    return failure_view($notification->getMessage());
}

// 3DS challenge succeeded and the postback is authentic — finalize:
$completion = $gateway->completePurchase([
    'referenceCode' => $notification->getTransactionReference(),
])->send();

if ($completion->isSuccessful()) {
    // Charge captured.
} else {
    echo $completion->getMessage();
}
```

`Notification` exposes:

| Method | What it returns |
|---|---|
| `isSuccessful()` | `true` only when `RESPONSE_CODE === 2` AND `AUTH_CODE` is non-empty and not `"0"` |
| `getTransactionStatus()` | Omnipay constant: `STATUS_COMPLETED` or `STATUS_FAILED` |
| `getMessage()` | `RESPONSE_DATA` |
| `getTransactionId()` | the `CLIENT_REFERENCE_CODE` you sent |
| `getTransactionReference()` | Paynkolay's `REFERENCE_CODE` |
| `getCode()` | raw `RESPONSE_CODE` |
| `verifyHash($merchantSecretKey)` | constant-time check of the postback `hashDataV2` field |
| `getData()` | raw POST array |

> ⚠️ **Always call `verifyHash()` before trusting the rest of the postback.** Without it, anyone who knows the merchant's reference code could POST a fake `RESPONSE_CODE=2` to your callback URL.
>
> Paynkolay's merchant panel has an "AutoComplete" toggle. If it's off, you **must** call `completePurchase()` after verifying. If it's on, Paynkolay finalises the charge itself — `completePurchase()` becomes a no-op but you should still call it for idempotency.

## Cancel (same-day reversal)

`type=cancel`, only valid for transactions made the same day. `amount` and `trxDate` are both required by the gateway.

```php
$gateway->cancel([
    'referenceCode' => 'IKSIRPF450511',
    'amount' => '100.00',
    'trxDate' => '2026-01-15',  // accepts any parseable form; serialised as YYYY.MM.DD
])->send();
```

Note: `trxDate` must be in `YYYY.MM.DD` format on the wire (e.g. `2026.01.15`).

## Refund (subsequent days)

`type=refund`. Same endpoint as cancel, the gateway switches behaviour by `type`.

```php
$gateway->refund([
    'referenceCode' => 'IKSIRPF450511',
    'amount' => '50.00',
    'trxDate' => '2026.01.15',  // original payment date
])->send();
```

## Transaction listing

Uses the dedicated **SX List Token**. Date format is `DD.MM.YYYY` (note: different from Cancel/Refund's `YYYY.MM.DD` — Paynkolay's date formatting is endpoint-specific).

```php
$response = $gateway->paymentList([
    'startDate' => '01.01.2026',
    'endDate' => '31.01.2026',
    'clientRefCode' => 'ORDER-123',  // optional filter
])->send();

if ($response->isSuccessful()) {
    foreach ($response->getTransactions() as $transaction) {
        // …
    }

    // Or look up a single transaction by our-side reference code:
    $found = $response->findByClientReferenceCode('ORDER-123');
}
```

The response shape isn't formally documented and Paynkolay's Postman collection ships no saved example. `PaymentListResponse` therefore discovers the transaction list adaptively — it picks the first top-level value that's a list of associative arrays — so you can wire this up against production without needing a package patch when actual response field names turn out different from your test fixture.

## Pay By Link — checkout redirect

"Ortak Ödeme Sayfası". Creates a hosted-payment URL the customer should be redirected to. Same hash format as direct purchase.

```php
$response = $gateway->payByLink([
    'amount' => '100.00',
    'transactionId' => 'ORDER-123',
    'installment' => 1,
    'returnUrl' => 'https://merchant.example/orders/ORDER-123/success',
    'cancelUrl' => 'https://merchant.example/orders/ORDER-123/fail',
    'clientIp' => '127.0.0.1',
])->send();

if ($response->isRedirect()) {
    header('Location: ' . $response->getRedirectUrl());
    exit;
}
```

After payment, the customer is returned to `returnUrl` and the postback flow is identical to direct 3DS — use `acceptNotification()` + `verifyHash()` + `completePurchase()`.

## Pay By Link — send to customer

For the case where the order already exists and staff issues a payment link by SMS/email (e.g. an invoice). Endpoint and field naming are uniquely inconsistent with the rest of the gateway — see the quirks section.

```php
$gateway->payByLinkSend([
    'amount' => '250.00',
    'transactionId' => 'ORDER-456',
    'fullName' => 'Customer Name',
    'gsm' => '5550000000',
    'linkExpirationTime' => '2026-12-30',
    'description' => 'Invoice payment',
    'sendSms' => true,
    'sendEmail' => true,
    'card' => ['email' => 'customer@example.com'],  // EMAIL is required by the gateway
])->send();
```

To invalidate a previously issued link:

```php
$gateway->payByLinkDelete([
    'linkRef' => 'by6353337820250825153001',  // the `q` value from the link URL
])->send();
```

## BIN installment lookup

```php
$response = $gateway->binInstallment([
    'binNumber' => '415565',
    'amount' => '100.00',
])->send();

if ($response->isSuccessful()) {
    print_r($response->getInstallments());
}
```

## Merchant installment table

```php
$response = $gateway->merchantInfo()->send();

if ($response->isSuccessful()) {
    print_r($response->getCommissionList());
}
```

## Doc-vs-prod quirks the package absorbs

Paynkolay's published docs at https://paynkolay.com.tr/entegrasyon/ lag the gateway's actual behaviour in several places. The package handles all of these — you should not need to special-case them in your application code, but they're documented here so future-you knows what to expect when reading raw payloads.

**The postback signature to verify is `hashDataV2`, not `hashData`.**

Every result postback carries two signature fields. Only `hashDataV2` has a published specification ([Hash Yanıtı](https://paynkolay.com.tr/entegrasyon/05-hash-response.php)); it uses the same SHA-512 → base64 scheme as outgoing requests, over this pipe-separated string:

```
MERCHANT_NO|REFERENCE_CODE|AUTH_CODE|RESPONSE_CODE|USE_3D|RND|INSTALLMENT|AUTHORIZATION_AMOUNT|CURRENCY_CODE|MERCHANT_SECRET_KEY
```

Two things to note:

- **`CURRENCY_CODE` is in the hashed string** but is missing from some postbacks (notably the 3D AutoComplete return). `verifyPostbackHash()` accepts either shape when the field is absent.
- **The `RND` echoed back is generated by Paynkolay** and is *not* the one you sent in the request — always hash the returned value.

`hashData` is a legacy SHA-1 field with no published spec. Earlier releases of this package (≤ v2.1.2) verified against it using a field order reverse-engineered from a WooCommerce plugin; that never matched real production postbacks, so every 3DS payment was rejected after a successful challenge. `PayNKolayHelper::generatePostbackHash()` still exists for BC but is deprecated — do not use it.

**Other wire-format inconsistencies**

| Endpoint | Quirk | Production wants |
|---|---|---|
| `/Vpos/by-link-create` (Pay By Link) | Docs imply `currencyNumber` like the direct API uses | gateway requires `currencyCode` (same numeric value; only the field name differs) |
| `/Vpos/pay-by-link-create` (Send Link) | Docs use `sx` like every other endpoint | gateway requires the field be renamed `TOKEN`; every other field is SCREAMING_SNAKE_CASE; **hash is still computed against the original `sx` token** |
| `/Vpos/Payment/PaymentList` | Documented date format unclear | `DD.MM.YYYY` |
| `/Vpos/v1/CancelRefundPayment` | Documented date format unclear | `YYYY.MM.DD` (different from PaymentList!) |
| `/Vpos/v1/CompletePayment` | docs imply `hashDatav2` is required | gateway accepts a plain `sx` + `referenceCode` (no outgoing hash); postback hash must be verified **before** this call |
| `CompletePurchaseResponse` on bank decline | RESPONSE_DATA always populated | sometimes empty — `getMessage()` falls back to the documented bank-code lookup table |

**Bank decline codes**

`Constants/ErrorCodes::MESSAGES` is a verbatim transcription of https://paynkolay.com.tr/entegrasyon/43-error-codes.php (as of 2026-05-20). `getMessage()` on the relevant responses uses it as fallback when `RESPONSE_DATA` is empty. `ErrorCodes::message($code)` is public if you want to translate codes yourself; it pads single-digit codes to two characters (the gateway emits `"0"` but the table is keyed on `"00"`).

## Testing

```bash
composer test
```

The suite runs against mocked HTTP responses; no network access required.

## License

MIT.
