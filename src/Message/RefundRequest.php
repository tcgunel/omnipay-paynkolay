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

        $amount = PayNKolayHelper::formatAmount((float) $this->getAmount());

        $data = [
            'sx' => $this->getMerchantPassword(),
            'referenceCode' => $this->getReferenceCode(),
            'type' => 'refund',
            'amount' => $amount,
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
        $this->validate('merchantPassword', 'merchantStorekey', 'referenceCode', 'amount');
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
