<?php

namespace Omnipay\PayNKolay\Message;

use JsonException;
use Omnipay\Common\Exception\RuntimeException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
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
        if ($this->isRedirect()) {
            return false;
        }

        return isset($this->data['RESPONSE_CODE'])
            && (int) $this->data['RESPONSE_CODE'] === 2
            && isset($this->data['USE_3D'])
            && $this->data['USE_3D'] === 'false'
            && isset($this->data['AUTH_CODE'])
            && $this->data['AUTH_CODE'] !== ''
            && $this->data['AUTH_CODE'] !== '0';
    }

    public function isRedirect(): bool
    {
        return isset($this->data['RESPONSE_CODE'])
            && (int) $this->data['RESPONSE_CODE'] === 2
            && isset($this->data['USE_3D'])
            && $this->data['USE_3D'] === 'true';
    }

    public function getRedirectUrl()
    {
        return null;
    }

    public function getRedirectMethod(): string
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        if ($this->isRedirect()) {
            return $this->data;
        }

        return null;
    }

    /**
     * Get the cleaned HTML content for 3D redirect.
     */
    public function getRedirectHtml(): ?string
    {
        if (isset($this->data['BANK_REQUEST_MESSAGE'])) {
            return PayNKolayHelper::cleanHtml($this->data['BANK_REQUEST_MESSAGE']);
        }

        return null;
    }

    /**
     * Paynkolay's direct API returns the bank's 3DS challenge as inline
     * HTML (`BANK_REQUEST_MESSAGE`), not a URL to redirect to. Omnipay's
     * default `getRedirectResponse()` runs `validateRedirect()`, which
     * hard-requires a non-empty `getRedirectUrl()` — so the default throws
     * "The given redirectUrl cannot be empty." before the consumer ever
     * sees the HTML. Override to emit the bank-side HTML directly.
     *
     * The HTML is an auto-submit `<form>` pointing at the bank's 3DS
     * challenge page. Bank ACS pages set `X-Frame-Options: DENY`, so a
     * consumer that renders this inside an iframe hits a refused-connection
     * once the bank tries to load. Inject `target="_top"` into every form
     * that doesn't already have one so the auto-submit navigates the
     * top-level window and breaks out of any iframe.
     */
    public function getRedirectResponse()
    {
        if (! $this->isRedirect()) {
            throw new RuntimeException('This response does not support redirection.');
        }

        return new HttpResponse($this->breakOutOfFrames((string) $this->getRedirectHtml()));
    }

    /**
     * Inject `target="_top"` into every `<form>` tag that doesn't already
     * declare a target, so the bank's auto-submit form escapes any iframe.
     */
    protected function breakOutOfFrames(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $patched = preg_replace(
            '/<form\b(?![^>]*\btarget=)/i',
            '<form target="_top"',
            $html,
        );

        return is_string($patched) ? $patched : $html;
    }

    public function getMessage(): ?string
    {
        return $this->data['RESPONSE_DATA'] ?? null;
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
}
