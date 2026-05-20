<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

class RefundRequest extends RemoteAbstractRequest
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
            'type' => 'refund',
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

    protected function createResponse($data): RefundResponse
    {
        return $this->response = new RefundResponse($this, $data);
    }
}
