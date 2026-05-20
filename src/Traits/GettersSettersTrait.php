<?php

namespace Omnipay\PayNKolay\Traits;

trait GettersSettersTrait
{
    public function getSxToken()
    {
        return $this->getParameter('sxToken');
    }

    public function setSxToken($value)
    {
        return $this->setParameter('sxToken', $value);
    }

    public function getSxListToken()
    {
        return $this->getParameter('sxListToken');
    }

    public function setSxListToken($value)
    {
        return $this->setParameter('sxListToken', $value);
    }

    public function getSxCancelToken()
    {
        return $this->getParameter('sxCancelToken');
    }

    public function setSxCancelToken($value)
    {
        return $this->setParameter('sxCancelToken', $value);
    }

    public function getMerchantSecretKey()
    {
        return $this->getParameter('merchantSecretKey');
    }

    public function setMerchantSecretKey($value)
    {
        return $this->setParameter('merchantSecretKey', $value);
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

    public function getTrxDate()
    {
        return $this->getParameter('trxDate');
    }

    public function setTrxDate($value)
    {
        return $this->setParameter('trxDate', $value);
    }

    public function getStartDate()
    {
        return $this->getParameter('startDate');
    }

    public function setStartDate($value)
    {
        return $this->setParameter('startDate', $value);
    }

    public function getEndDate()
    {
        return $this->getParameter('endDate');
    }

    public function setEndDate($value)
    {
        return $this->setParameter('endDate', $value);
    }

    public function getClientRefCode()
    {
        return $this->getParameter('clientRefCode');
    }

    public function setClientRefCode($value)
    {
        return $this->setParameter('clientRefCode', $value);
    }

    public function getTckn()
    {
        return $this->getParameter('tckn');
    }

    public function setTckn($value)
    {
        return $this->setParameter('tckn', $value);
    }

    public function getMerchantCustomerNo()
    {
        return $this->getParameter('merchantCustomerNo');
    }

    public function setMerchantCustomerNo($value)
    {
        return $this->setParameter('merchantCustomerNo', $value);
    }

    public function getDescription()
    {
        return $this->getParameter('description');
    }

    public function setDescription($value)
    {
        return $this->setParameter('description', $value);
    }

    public function getLinkRef()
    {
        return $this->getParameter('linkRef');
    }

    public function setLinkRef($value)
    {
        return $this->setParameter('linkRef', $value);
    }

    public function getLinkExpirationTime()
    {
        return $this->getParameter('linkExpirationTime');
    }

    public function setLinkExpirationTime($value)
    {
        return $this->setParameter('linkExpirationTime', $value);
    }

    public function getLinkAmountFixingType()
    {
        return $this->getParameter('linkAmountFixingType');
    }

    public function setLinkAmountFixingType($value)
    {
        return $this->setParameter('linkAmountFixingType', $value);
    }

    public function getFullName()
    {
        return $this->getParameter('fullName');
    }

    public function setFullName($value)
    {
        return $this->setParameter('fullName', $value);
    }

    public function getGsm()
    {
        return $this->getParameter('gsm');
    }

    public function setGsm($value)
    {
        return $this->setParameter('gsm', $value);
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
