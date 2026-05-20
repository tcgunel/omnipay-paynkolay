<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Constants\Currency;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

/**
 * "Ortak Ödeme Sayfası" — Paynkolay-hosted checkout page.
 *
 * Endpoint: /Vpos/by-link-create
 *
 * Returns a redirect URL the customer should be sent to. After they pay
 * on Paynkolay's page they're returned to `successUrl` / `failUrl`.
 *
 * Wire-format quirk: the currency field is `currencyCode` here (not
 * `currencyNumber` as in Purchase v1). The value is the same ISO-4217
 * numeric code (949 = TRY); only the field name differs.
 *
 * Hash format: same as Purchase v1.
 *   sx|clientRefCode|amount|successUrl|failUrl|rnd|customerKey|merchantSecretKey
 */
class PayByLinkRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/by-link-create';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $installment = $this->getInstallment();
        $installment = $installment > 1 ? (int) $installment : 1;

        $amount = PayNKolayHelper::formatAmount((float) $this->getAmount());
        $rnd = date('YmdHis');
        $customerKey = '';

        $data = [
            'sx' => $this->getSxToken(),
            'clientRefCode' => $this->getTransactionId(),
            'amount' => $amount,
            'successUrl' => $this->getReturnUrl() ?? '',
            'failUrl' => $this->getCancelUrl() ?? $this->getReturnUrl() ?? '',
            'rnd' => $rnd,
            'use3D' => 'true',
            'currencyCode' => (string) ($this->getCurrencyNumber() ?? Currency::TRY),
            'transactionType' => 'SALES',
            'instalments' => (string) $installment,
            'cardHolderIP' => $this->getClientIp() ?? '',
            'detail' => 'true',
        ];

        $data['hashDatav2'] = PayNKolayHelper::generateSaleHash(
            $data['sx'],
            $data['clientRefCode'],
            $data['amount'],
            $data['successUrl'],
            $data['failUrl'],
            $data['rnd'],
            $customerKey,
            $this->getMerchantSecretKey()
        );

        // Optional buyer/billing metadata (prefixed `input*` per Paynkolay's
        // hosted-page form fields). Only sent when supplied.
        $namesurname = $this->getFullName() ?: $this->get_card('getName');
        $email = $this->get_card('getEmail');
        $phone = $this->get_card('getBillingPhone') ?: $this->get_card('getPhone');
        $address = $this->get_card('getBillingAddress1');

        $optional = [
            'inputNamesurname' => $namesurname ?: null,
            'inputEmail' => $email ?: null,
            'inputPhone' => $phone ?: null,
            'inputAddress' => $address ?: null,
            'inputTckn' => $this->getTckn(),
            'inputDescription' => $this->getDescription(),
        ];

        foreach ($optional as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('amount', 'transactionId', 'returnUrl');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PayByLinkResponse
    {
        return $this->response = new PayByLinkResponse($this, $data);
    }
}
