<?php

namespace Omnipay\PayNKolay\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;
use Psr\Http\Message\ResponseInterface;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
	protected $response;

	protected $request;

	protected $data;

	public function __construct(RequestInterface $request, $data)
	{
		parent::__construct($request, $data);

		$this->request = $request;
		$this->response = $data;

		if ($data instanceof ResponseInterface) {
			$body = (string)$data->getBody();

			try {
				$this->data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				$this->data = [
					'RESPONSE_CODE' => 0,
					'RESPONSE_DATA' => $body,
				];
			}
		} elseif (is_array($data)) {
			$this->data = $data;
		}
	}

	public function isSuccessful(): bool
	{
		if ($this->isRedirect()) {
			return false;
		}

		return isset($this->data['RESPONSE_CODE'])
			&& (int)$this->data['RESPONSE_CODE'] === 2
			&& isset($this->data['USE_3D'])
			&& $this->data['USE_3D'] === 'false'
			&& isset($this->data['AUTH_CODE'])
			&& $this->data['AUTH_CODE'] !== ''
			&& $this->data['AUTH_CODE'] !== '0';
	}

	public function isRedirect(): bool
	{
		return isset($this->data['RESPONSE_CODE'])
			&& (int)$this->data['RESPONSE_CODE'] === 2
			&& isset($this->data['USE_3D'])
			&& $this->data['USE_3D'] === 'true';
	}

	public function getRedirectUrl()
	{
		return null;
	}

	public function getRedirectMethod(): string
	{
		return 'POST';
	}

	public function getRedirectData()
	{
		if ($this->isRedirect()) {
			return $this->data;
		}

		return null;
	}

	/**
	 * Get the cleaned HTML content for 3D redirect.
	 */
	public function getRedirectHtml(): ?string
	{
		if (isset($this->data['BANK_REQUEST_MESSAGE'])) {
			return PayNKolayHelper::cleanHtml($this->data['BANK_REQUEST_MESSAGE']);
		}

		return null;
	}

	public function getMessage(): ?string
	{
		return $this->data['RESPONSE_DATA'] ?? null;
	}

	public function getTransactionReference(): ?string
	{
		return $this->data['REFERENCE_CODE'] ?? null;
	}

	public function getCode(): ?string
	{
		return isset($this->data['RESPONSE_CODE']) ? (string)$this->data['RESPONSE_CODE'] : null;
	}

	public function getData(): ?array
	{
		return $this->data;
	}
}
