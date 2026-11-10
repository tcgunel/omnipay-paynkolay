<?php

namespace Omnipay\PayNKolay\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayNKolay\Constants\ErrorCodes;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;
use Psr\Http\Message\ResponseInterface;

class CompletePurchaseResponse extends AbstractResponse
{
    protected $response;

    protected $request;

    protected $data;

    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);

        $this->request = $request;
        $this->response = $data;

        if ($data instanceof ResponseInterface) {
            $body = (string) $data->getBody();

            try {
                $this->data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $this->data = [
                    'RESPONSE_CODE' => 0,
                    'RESPONSE_DATA' => $body,
                ];
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        }
    }

    /**
     * Successful when the gateway approved the completion and, if the
     * caller supplied expectations on completePurchase(), they all hold:
     *
     *  - `postback` given: its hashDataV2 must verify against the merchant
     *    secret key (missing hash or empty key fails) and its signed
     *    REFERENCE_CODE must equal the `referenceCode` being completed.
     *  - `transactionId` given: the CLIENT_REFERENCE_CODE (from the gateway
     *    response, else from the postback) must equal it exactly.
     *  - `amount` given: the amount (signed postback AUTHORIZATION_AMOUNT,
     *    else the gateway response's AUTHORIZATION_AMOUNT) must equal it in
     *    integer minor units.
     *
     * CLIENT_REFERENCE_CODE is NOT covered by Paynkolay's postback hash, so
     * a match on it alone does not prove the payment belongs to your order;
     * confirm server-side with paymentList() (see README).
     *
     * Without expectations the legacy behaviour is unchanged.
     */
    public function isSuccessful(): bool
    {
        $approved = isset($this->data['RESPONSE_CODE'])
            && (int) $this->data['RESPONSE_CODE'] === 2
            && isset($this->data['AUTH_CODE'])
            && $this->data['AUTH_CODE'] !== ''
            && $this->data['AUTH_CODE'] !== '0';

        return $approved && $this->expectationsHold();
    }

    /**
     * Paynkolay's own transaction reference (REFERENCE_CODE). Falls back to
     * the signed postback when the gateway response omits it.
     */
    public function getProviderReference(): ?string
    {
        $value = $this->data['REFERENCE_CODE'] ?? $this->verifiedPostback()['REFERENCE_CODE'] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * The merchant-side order reference (CLIENT_REFERENCE_CODE) as reported
     * by the gateway response, else by the verified postback. Not signed.
     */
    public function getClientReference(): ?string
    {
        $value = $this->data['CLIENT_REFERENCE_CODE'] ?? $this->verifiedPostback()['CLIENT_REFERENCE_CODE'] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * AUTHORIZATION_AMOUNT in integer minor units: from the hash-verified
     * postback when one was supplied, else from the gateway response.
     * Null when absent or unparseable.
     */
    public function getSignedAmount(): ?int
    {
        $postback = $this->verifiedPostback();

        $raw = $postback !== null && array_key_exists('AUTHORIZATION_AMOUNT', $postback)
            ? $postback['AUTHORIZATION_AMOUNT']
            : ($this->data['AUTHORIZATION_AMOUNT'] ?? null);

        return PayNKolayHelper::amountToMinorUnits($raw);
    }

    private function expectationsHold(): bool
    {
        $request = $this->request;

        if (! $request instanceof CompletePurchaseRequest) {
            return true;
        }

        $postback = $request->getPostback();

        if ($postback !== null) {
            if (! is_array($postback) || $this->verifiedPostback() === null) {
                return false;
            }

            if ((string) ($postback['REFERENCE_CODE'] ?? '') !== (string) $request->getReferenceCode()) {
                return false;
            }
        }

        $expectedRef = $request->getTransactionId();

        if ($expectedRef !== null && $expectedRef !== '') {
            $actual = $this->getClientReference();

            if ($actual === null || ! hash_equals((string) $expectedRef, $actual)) {
                return false;
            }
        }

        $expectedAmount = $request->getExpectedAmountInteger();

        if ($expectedAmount !== null && $this->getSignedAmount() !== $expectedAmount) {
            return false;
        }

        return true;
    }

    /**
     * The postback passed to completePurchase(), only if its hash verifies.
     *
     * @return array<string, mixed>|null
     */
    private function verifiedPostback(): ?array
    {
        $request = $this->request;

        if (! $request instanceof CompletePurchaseRequest) {
            return null;
        }

        $postback = $request->getPostback();

        if (! is_array($postback)) {
            return null;
        }

        $secret = (string) ($request->getMerchantSecretKey() ?? '');

        return PayNKolayHelper::verifyPostbackHash($postback, $secret) ? $postback : null;
    }

    public function getMessage(): ?string
    {
        $raw = $this->data['RESPONSE_DATA'] ?? null;

        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        // Gateway sometimes ships an empty RESPONSE_DATA on bank declines —
        // fall back to the documented bank code message when we have one.
        return ErrorCodes::message($this->getCode());
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['REFERENCE_CODE'] ?? null;
    }

    public function getCode(): ?string
    {
        return isset($this->data['RESPONSE_CODE']) ? (string) $this->data['RESPONSE_CODE'] : null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getRedirectData()
    {
        return null;
    }

    public function getRedirectUrl(): string
    {
        return '';
    }
}
