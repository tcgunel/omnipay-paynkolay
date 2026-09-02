<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

/**
 * Wraps Paynkolay's 3DS postback POST and exposes it through Omnipay's
 * standard NotificationInterface. Consumers should:
 *
 *   1. Construct via `Gateway::acceptNotification($request->all())`.
 *   2. Call `verifyHash($merchantSecretKey)` — REJECT on false.
 *   3. Call `isSuccessful()` — only call `completePurchase()` when true.
 *
 * The field to verify is `hashDataV2` — SHA-512 + base64 over the
 * pipe-separated string documented at
 * https://paynkolay.com.tr/entegrasyon/05-hash-response.php. The `hashData`
 * field posted alongside it is a legacy SHA-1 value with no published
 * specification; do not verify against it. See
 * `PayNKolayHelper::verifyPostbackHash()`.
 */
class Notification implements NotificationInterface
{
    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isSuccessful(): bool
    {
        return $this->responseCodeOk() && $this->authCodeOk();
    }

    public function getTransactionStatus(): string
    {
        return $this->isSuccessful()
            ? NotificationInterface::STATUS_COMPLETED
            : NotificationInterface::STATUS_FAILED;
    }

    public function getMessage(): ?string
    {
        return isset($this->data['RESPONSE_DATA'])
            ? (string) $this->data['RESPONSE_DATA']
            : null;
    }

    public function getTransactionReference(): ?string
    {
        return isset($this->data['REFERENCE_CODE'])
            ? (string) $this->data['REFERENCE_CODE']
            : null;
    }

    public function getTransactionId(): ?string
    {
        return isset($this->data['CLIENT_REFERENCE_CODE'])
            ? (string) $this->data['CLIENT_REFERENCE_CODE']
            : null;
    }

    public function getCode(): ?string
    {
        return isset($this->data['RESPONSE_CODE'])
            ? (string) $this->data['RESPONSE_CODE']
            : null;
    }

    /**
     * Verify the inbound `hashDataV2` against the merchant's secret key.
     */
    public function verifyHash(string $merchantSecretKey): bool
    {
        return PayNKolayHelper::verifyPostbackHash($this->data, $merchantSecretKey);
    }

    private function responseCodeOk(): bool
    {
        return isset($this->data['RESPONSE_CODE'])
            && (int) $this->data['RESPONSE_CODE'] === 2;
    }

    private function authCodeOk(): bool
    {
        $auth = (string) ($this->data['AUTH_CODE'] ?? '');

        return $auth !== '' && $auth !== '0';
    }
}
