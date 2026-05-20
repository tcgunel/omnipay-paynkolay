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
 * The wire format quirk to remember: the postback `hashData` field uses
 * SHA-1 + hex-decode + base64 over raw concatenation (no separator), NOT
 * the SHA-512 + pipe-separated scheme the outgoing requests use. See
 * `PayNKolayHelper::generatePostbackHash()`.
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
     * Verify the inbound `hashData` against the merchant's secret key.
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
