<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

/**
 * "Send Link" — create a Pay By Link and deliver it to the customer
 * by SMS and/or email after the order already exists. Distinct from
 * `PayByLinkRequest`: that one is the redirect-at-checkout flow; this
 * one is fire-and-forget, used when staff issues a payment link
 * post-order (e.g. invoice).
 *
 * Endpoint: /Vpos/pay-by-link-create
 *
 * Wire-format quirks:
 *   - The `sx` field is named `TOKEN` here.
 *   - All other fields are SCREAMING_SNAKE_CASE.
 *
 * Hash format:
 *   sx|FULL_NAME|EMAIL|GSM|AMOUNT|LINK_EXPIRATION_TIME|merchantSecretKey
 */
class PayByLinkSendRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/Vpos/pay-by-link-create';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $installment = $this->getInstallment();
        $installment = $installment > 1 ? (int) $installment : 1;

        $amount = PayNKolayHelper::formatAmount((float) $this->getAmount());
        $fullName = (string) ($this->getFullName() ?? $this->get_card('getName') ?? '');
        $email = (string) ($this->get_card('getEmail') ?? '');
        $gsm = (string) ($this->getGsm() ?? $this->get_card('getBillingPhone') ?? '');
        $expiration = (string) ($this->getLinkExpirationTime() ?? '');

        $data = [
            'TOKEN' => $this->getSxToken(),
            'FULL_NAME' => $fullName,
            'EMAIL' => $email,
            'GSM' => $gsm,
            'LINK_AMOUNT_FIXING_TYPE' => (string) ($this->getLinkAmountFixingType() ?? 'FIXED'),
            'AMOUNT' => $amount,
            'LINK_EXPIRATION_TIME' => $expiration,
            'IS_3D_MUST' => 'true',
            'CLIENT_REFERENCE_CODE' => (string) $this->getTransactionId(),
            'INSTALLMENT' => (string) $installment,
            'cardHolderIP' => $this->getClientIp(),
            'SEND_SMS' => $this->getParameter('sendSms') === false ? 'false' : 'true',
            'SEND_EMAIL' => $this->getParameter('sendEmail') === false ? 'false' : 'true',
        ];

        // Hash is computed from the *original* sx token, not the renamed TOKEN field.
        $data['hashDatav2'] = PayNKolayHelper::hash(implode('|', [
            $data['TOKEN'],
            $fullName,
            $email,
            $gsm,
            $amount,
            $expiration,
            $this->getMerchantSecretKey(),
        ]));

        $optional = [
            'PAYMENT_SUBJECT' => $this->getParameter('paymentSubject'),
            'EXPLANATION' => $this->getDescription(),
            'CALLBACK_URL' => $this->getReturnUrl() ?: $this->getNotifyUrl(),
            'IMAGE_URL' => $this->getParameter('imageUrl'),
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

        $this->validate('amount', 'transactionId', 'linkExpirationTime');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendFormRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PayByLinkSendResponse
    {
        return $this->response = new PayByLinkSendResponse($this, $data);
    }
}
