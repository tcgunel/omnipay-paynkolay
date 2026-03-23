<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;

class MerchantInfoRequest extends RemoteAbstractRequest
{
	protected $endpoint = '/Vpos/Payment/GetMerchandInformation';

	/**
	 * @throws InvalidRequestException
	 */
	public function getData(): array
	{
		$this->validateAll();

		$date = date('d.m.Y');

		return [
			'sx' => $this->getMerchantId(),
			'date' => $date,
			'hashDatav2' => PayNKolayHelper::generateMerchantInfoHash(
				$this->getMerchantId(),
				$date,
				$this->getMerchantStorekey()
			),
		];
	}

	/**
	 * @throws InvalidRequestException
	 */
	protected function validateAll(): void
	{
		$this->validateSettings();
	}

	public function sendData($data)
	{
		$url = $this->getBaseUrl() . $this->endpoint;

		$httpResponse = $this->sendFormRequest($url, $data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): MerchantInfoResponse
	{
		return $this->response = new MerchantInfoResponse($this, $data);
	}
}
