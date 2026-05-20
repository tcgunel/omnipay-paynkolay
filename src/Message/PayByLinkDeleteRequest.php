<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

/**
 * Invalidate a previously created Pay By Link URL.
 *
 * Endpoint: /Vpos/by-link-url-remove
 *
 * Hash format: sx|linkRef|merchantSecretKey
 *
 * `linkRef` is the `q` value from the original link URL (e.g.
 * `by6353337820250825153001`).
 */
class PayByLinkDeleteRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/by-link-url-remove';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $data = [
            'sx' => $this->getSxToken(),
            'q' => (string) $this->getLinkRef(),
            'cardHolderIP' => $this->getClientIp() ?? '',
        ];

        $data['hashDatav2'] = PayNKolayHelper::hash(implode('|', [
            $data['sx'],
            $data['q'],
            $this->getMerchantSecretKey(),
        ]));

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('linkRef');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PayByLinkDeleteResponse
    {
        return $this->response = new PayByLinkDeleteResponse($this, $data);
    }
}
