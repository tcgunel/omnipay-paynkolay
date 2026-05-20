<?php

namespace Omnipay\PayNKolay\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PayByLinkSendResponse extends AbstractResponse
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
     * The hosted-link URL the customer should pay through, when included
     * in the response. Field name varies by environment, so we probe.
     */
    public function getLinkUrl(): ?string
    {
        foreach (['LINK_URL', 'link_url', 'URL', 'url'] as $key) {
            if (isset($this->data[$key]) && is_string($this->data[$key])) {
                return $this->data[$key];
            }
        }

        return null;
    }
}
