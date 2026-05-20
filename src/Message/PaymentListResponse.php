<?php

namespace Omnipay\PayNKolay\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decoded response from `/Vpos/Payment/PaymentList`.
 *
 * The exact response shape isn't formally documented and the Postman
 * collection ships no saved example, so this class makes minimal
 * assumptions: it decodes the JSON, exposes `getCode()` and
 * `getData()`, and discovers the transaction list adaptively so
 * consumers can introspect actual production responses without
 * needing a package patch.
 */
class PaymentListResponse extends AbstractResponse
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

    public function isSuccessful(): bool
    {
        return isset($this->data['RESPONSE_CODE'])
            && (int) $this->data['RESPONSE_CODE'] === 2;
    }

    public function getMessage(): ?string
    {
        return $this->data['RESPONSE_DATA'] ?? null;
    }

    public function getCode(): ?string
    {
        return isset($this->data['RESPONSE_CODE']) ? (string) $this->data['RESPONSE_CODE'] : null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Best-effort: find the array of transactions in the decoded response.
     * Picks the first top-level value that is a list of associative arrays.
     */
    public function getTransactions(): array
    {
        if (! is_array($this->data)) {
            return [];
        }

        foreach ($this->data as $value) {
            if (! is_array($value) || $value === []) {
                continue;
            }

            $first = reset($value);
            if (is_array($first)) {
                return $value;
            }
        }

        return [];
    }

    /**
     * Look up a single transaction by our-side reference code. Tries the
     * documented field name first (`CLIENT_REFERENCE_CODE`) then a few
     * camelCase / lowercase fallbacks since Paynkolay's response casing
     * isn't consistent across endpoints.
     */
    public function findByClientReferenceCode(string $code): ?array
    {
        $keys = ['CLIENT_REFERENCE_CODE', 'clientReferenceCode', 'CLIENTREFCODE', 'clientRefCode'];

        foreach ($this->getTransactions() as $transaction) {
            foreach ($keys as $key) {
                if (isset($transaction[$key]) && (string) $transaction[$key] === $code) {
                    return $transaction;
                }
            }
        }

        return null;
    }
}
