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
            'sx' => $this->getSxCancelToken(),
            'referenceCode' => $this->getReferenceCode(),
            'type' => 'cancel',
            'amount' => PayNKolayHelper::formatAmount((float) $this->getAmount()),
            'trxDate' => $this->getTrxDate(),
        ];

        $data['hashDatav2'] = PayNKolayHelper::generateCancelRefundHash(
            $data['sx'],
            $data['referenceCode'],
            $data['type'],
            $data['amount'],
            $data['trxDate'],
            $this->getMerchantSecretKey()
        );

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validate('sxCancelToken', 'merchantSecretKey', 'referenceCode', 'amount', 'trxDate');
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
