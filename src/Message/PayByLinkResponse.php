<?php

namespace Omnipay\PayNKolay\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PayByLinkResponse extends AbstractResponse implements RedirectResponseInterface
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
        // Pay By Link returns a URL — success is the redirect, not a code.
        return false;
    }

    public function isRedirect(): bool
    {
        return $this->getRedirectUrl() !== '';
    }

    public function getRedirectUrl(): string
    {
        $candidates = ['LINK_URL', 'link_url', 'URL', 'url', 'RESPONSE_DATA'];

        foreach ($candidates as $key) {
            if (isset($this->data[$key]) && is_string($this->data[$key]) && str_starts_with($this->data[$key], 'http')) {
                return $this->data[$key];
            }
        }

        return '';
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return null;
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
}
