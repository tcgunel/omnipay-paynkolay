<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;

class CompletePurchaseRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/v1/CompletePayment';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        return [
            'sx' => $this->getSxToken(),
            'referenceCode' => $this->getReferenceCode(),
        ];
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('referenceCode');
    }

    /**
     * Optional: the raw 3D result postback (the array handed to
     * acceptNotification()). When supplied, its hashDataV2 is verified and
     * its signed REFERENCE_CODE / AUTHORIZATION_AMOUNT are bound to this
     * completion. See CompletePurchaseResponse::isSuccessful().
     *
     * @param array<string, mixed>|null $value
     */
    public function setPostback($value)
    {
        return $this->setParameter('postback', $value);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPostback()
    {
        return $this->getParameter('postback');
    }

    /**
     * Expected amount in integer minor units, or null when the caller did
     * not pass `amount`. Never throws.
     */
    public function getExpectedAmountInteger(): ?int
    {
        if ($this->getParameter('amount') === null || $this->getParameter('amount') === '') {
            return null;
        }

        try {
            return $this->getAmountInteger();
        } catch (InvalidRequestException $e) {
            // An unparseable expectation must never relax the check.
            return -1;
        }
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): CompletePurchaseResponse
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
