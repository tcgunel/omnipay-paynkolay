<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Constants\Currency;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

class PurchaseRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/v1/Payment';

    /**
     * @throws InvalidRequestException
     * @throws InvalidCreditCardException
     */
    public function getData(): array
    {
        $this->validateAll();

        $installment = $this->getInstallment();
        $installment = $installment > 1 ? (int) $installment : 1;

        $amount = PayNKolayHelper::formatAmount((float) $this->getAmount());
        $rnd = date('d.m.Y H:i:s');
        $successUrl = '';
        $failUrl = '';
        $customerKey = '';
        $use3D = 'false';

        if ($this->getSecure()) {
            $use3D = 'true';
            $successUrl = $this->getReturnUrl() ?? '';
            $failUrl = $this->getCancelUrl() ?? $this->getReturnUrl() ?? '';
        }

        $data = [
            'sx' => $this->getSxToken(),
            'clientRefCode' => $this->getTransactionId(),
            'amount' => $amount,
            'installmentNo' => (string) $installment,
            'cardHolderName' => $this->get_card('getName'),
            'month' => $this->get_card('getExpiryMonth'),
            'year' => $this->get_card('getExpiryYear'),
            'cvv' => $this->get_card('getCvv'),
            'cardNumber' => $this->get_card('getNumber'),
            'transactionType' => 'SALES',
            'rnd' => $rnd,
            'environment' => 'API',
            'currencyNumber' => (string) ($this->getCurrencyNumber() ?? Currency::TRY),
            'cardHolderIP' => $this->getClientIp() ?? '127.0.0.1',
            'successUrl' => $successUrl,
            'failUrl' => $failUrl,
            'customerKey' => $customerKey,
            'use3D' => $use3D,
        ];

        $data['hashDatav2'] = PayNKolayHelper::generateSaleHash(
            $data['sx'],
            $data['clientRefCode'],
            $data['amount'],
            $data['successUrl'],
            $data['failUrl'],
            $data['rnd'],
            $data['customerKey'],
            $this->getMerchantSecretKey()
        );

        // Optional buyer/billing metadata. Only sent when supplied so the
        // request payload stays minimal for callers that don't care.
        foreach ($this->buyerFields() as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Optional buyer/billing fields the gateway will log against the
     * transaction. Pulled from the Card object first, falling back to
     * explicit request parameters so callers can override per-request.
     *
     * @return array<string, ?string>
     */
    private function buyerFields(): array
    {
        $namesurname = $this->getFullName() ?: $this->get_card('getName');
        $email = $this->get_card('getEmail');
        $phone = $this->get_card('getBillingPhone') ?: $this->get_card('getPhone');
        $address = $this->get_card('getBillingAddress1');

        return [
            'namesurname' => $namesurname ?: null,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'adress' => $address ?: null,
            'tckn' => $this->getTckn(),
            'description' => $this->getDescription(),
            'MerchantCustomerNo' => $this->getMerchantCustomerNo(),
        ];
    }

    /**
     * @throws InvalidRequestException
     * @throws InvalidCreditCardException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('amount', 'transactionId', 'card');

        $this->getCard()->validate();
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PurchaseResponse
    {
        return $this->response = new PurchaseResponse($this, $data);
    }
}
