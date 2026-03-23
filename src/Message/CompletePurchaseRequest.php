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
			'sx' => $this->getMerchantId(),
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
