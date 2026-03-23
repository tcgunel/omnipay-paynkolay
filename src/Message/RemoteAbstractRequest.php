<?php

namespace Omnipay\PayNKolay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\PayNKolay\Traits\GettersSettersTrait;

abstract class RemoteAbstractRequest extends AbstractRequest
{
	use GettersSettersTrait;

	protected $endpointTest = 'https://paynkolaytest.nkolayislem.com.tr';

	protected $endpointLive = 'https://paynkolay.nkolayislem.com.tr';

	protected $endpoint = '';

	/**
	 * @throws InvalidRequestException
	 */
	protected function validateSettings(): void
	{
		$this->validate('merchantId', 'merchantStorekey');
	}

	protected function getBaseUrl(): string
	{
		return $this->getTestMode() ? $this->endpointTest : $this->endpointLive;
	}

	protected function sendFormRequest(string $url, array $data): \Psr\Http\Message\ResponseInterface
	{
		return $this->httpClient->request(
			'POST',
			$url,
			[
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept' => 'application/json',
			],
			http_build_query($data, '', '&')
		);
	}

	protected function get_card($key)
	{
		return $this->getCard() ? $this->getCard()->$key() : null;
	}

	abstract protected function createResponse($data);
}
