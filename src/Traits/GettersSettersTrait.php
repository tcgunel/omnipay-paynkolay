<?php

namespace Omnipay\PayNKolay\Traits;

trait GettersSettersTrait
{
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getMerchantPassword()
    {
        return $this->getParameter('merchantPassword');
    }

    public function setMerchantPassword($value)
    {
        return $this->setParameter('merchantPassword', $value);
    }

    public function getMerchantStorekey()
    {
        return $this->getParameter('merchantStorekey');
    }

    public function setMerchantStorekey($value)
    {
        return $this->setParameter('merchantStorekey', $value);
    }

    public function getInstallment()
    {
        return $this->getParameter('installment');
    }

    public function setInstallment($value)
    {
        return $this->setParameter('installment', $value);
    }

    public function getSecure()
    {
        return $this->getParameter('secure');
    }

    public function setSecure($value)
    {
        return $this->setParameter('secure', $value);
    }

    public function getReferenceCode()
    {
        return $this->getParameter('referenceCode');
    }

    public function setReferenceCode($value)
    {
        return $this->setParameter('referenceCode', $value);
    }

    public function getBinNumber()
    {
        return $this->getParameter('binNumber');
    }

    public function setBinNumber($value)
    {
        return $this->setParameter('binNumber', $value);
    }

    public function getCurrencyNumber()
    {
        return $this->getParameter('currencyNumber');
    }

    public function setCurrencyNumber($value)
    {
        return $this->setParameter('currencyNumber', $value);
    }

    public function getClientIp()
    {
        return $this->getParameter('clientIp');
    }

    public function setClientIp($value)
    {
        return $this->setParameter('clientIp', $value);
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }
}
