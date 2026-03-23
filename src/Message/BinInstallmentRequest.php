<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

class BinInstallmentRequest extends RemoteAbstractRequest
{
	protected $endpoint = '/Vpos/Payment/PaymentInstallments';

	/**
	 * @throws InvalidRequestException
	 */
	public function getData(): array
	{
		$this->validateAll();

		return [
			'sx' => $this->getMerchantId(),
			'amount' => PayNKolayHelper::formatAmount((float)$this->getAmount()),
			'cardNumber' => $this->getBinNumber(),
			'iscardvalid' => 'false',
		];
	}

	/**
	 * @throws InvalidRequestException
	 */
	protected function validateAll(): void
	{
		$this->validateSettings();

		$this->validate('amount', 'binNumber');
	}

	public function sendData($data)
	{
		$url = $this->getBaseUrl() . $this->endpoint;

		$httpResponse = $this->sendFormRequest($url, $data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): BinInstallmentResponse
	{
		return $this->response = new BinInstallmentResponse($this, $data);
	}
}
