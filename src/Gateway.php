<?php

namespace Omnipay\PayNKolay;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\PayNKolay\Message\BinInstallmentRequest;
use Omnipay\PayNKolay\Message\CancelRequest;
use Omnipay\PayNKolay\Message\CompletePurchaseRequest;
use Omnipay\PayNKolay\Message\MerchantInfoRequest;
use Omnipay\PayNKolay\Message\PurchaseRequest;
use Omnipay\PayNKolay\Message\RefundRequest;
use Omnipay\PayNKolay\Traits\GettersSettersTrait;

/**
 * PayNKolay Gateway
 * (c) Tolga Can Gunel
 * 2015, mobius.studio
 * http://www.github.com/tcgunel/omnipay-paynkolay
 * @method \Omnipay\Common\Message\NotificationInterface acceptNotification(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = [])
 */
class Gateway extends AbstractGateway
{
	use GettersSettersTrait;

	public function getName(): string
	{
		return 'PayNKolay';
	}

	public function getDefaultParameters()
	{
		return [
			'clientIp' => '127.0.0.1',

			'merchantId' => '',
			'merchantPassword' => '',
			'merchantStorekey' => '',

			'installment' => 1,
		];
	}

	public function purchase(array $options = []): AbstractRequest
	{
		return $this->createRequest(PurchaseRequest::class, $options);
	}

	public function completePurchase(array $options = []): AbstractRequest
	{
		return $this->createRequest(CompletePurchaseRequest::class, $options);
	}

	public function cancel(array $options = []): AbstractRequest
	{
		return $this->createRequest(CancelRequest::class, $options);
	}

	public function refund(array $options = []): AbstractRequest
	{
		return $this->createRequest(RefundRequest::class, $options);
	}

	public function binInstallment(array $options = []): AbstractRequest
	{
		return $this->createRequest(BinInstallmentRequest::class, $options);
	}

	public function merchantInfo(array $options = []): AbstractRequest
	{
		return $this->createRequest(MerchantInfoRequest::class, $options);
	}
}
