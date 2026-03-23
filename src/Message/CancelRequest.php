<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

class CancelRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/v1/CancelRefundPayment';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $data = [
            'sx' => $this->getMerchantPassword(),
            'referenceCode' => $this->getReferenceCode(),
            'type' => 'cancel',
            'amount' => '',
            'trxDate' => '',
        ];

        $hash = PayNKolayHelper::generateCancelRefundHash(
            $data['sx'],
            $data['referenceCode'],
            $data['type'],
            $data['amount'],
            $data['trxDate'],
            $this->getMerchantStorekey()
        );

        $data['hashDatav2'] = $hash;

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validate('merchantPassword', 'merchantStorekey', 'referenceCode');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): CancelResponse
    {
        return $this->response = new CancelResponse($this, $data);
    }
}
