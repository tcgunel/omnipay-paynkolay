<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

/**
 * Paginated transaction listing — uses the dedicated `sx-list` token,
 * not the regular payment token. Both `startDate` and `endDate` are in
 * DD.MM.YYYY format (note: different from Cancel/Refund which use
 * YYYY.MM.DD).
 */
class PaymentListRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/Payment/PaymentList';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $data = [
            'sx' => $this->getSxListToken(),
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
            'clientRefCode' => (string) ($this->getClientRefCode() ?? ''),
        ];

        $data['hashDatav2'] = PayNKolayHelper::hash(implode('|', [
            $data['sx'],
            $data['startDate'],
            $data['endDate'],
            $data['clientRefCode'],
            $this->getMerchantSecretKey(),
        ]));

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validate('sxListToken', 'merchantSecretKey', 'startDate', 'endDate');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PaymentListResponse
    {
        return $this->response = new PaymentListResponse($this, $data);
    }
}
