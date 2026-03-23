<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\PayNKolay\Message\MerchantInfoRequest;
use Omnipay\PayNKolay\Message\MerchantInfoResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class MerchantInfoTest extends TestCase
{
    public function test_merchant_info_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/MerchantInfoRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new MerchantInfoRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('testMerchantId', $data['sx']);
        self::assertArrayHasKey('date', $data);
        self::assertArrayHasKey('hashDatav2', $data);
        self::assertNotEmpty($data['hashDatav2']);
    }

    public function test_merchant_info_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('MerchantInfoResponseSuccess.txt');

        $response = new MerchantInfoResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());

        $commissionList = $response->getCommissionList();
        $this->assertCount(2, $commissionList);
        $this->assertEquals('001', $commissionList[0]['KEY']);
        $this->assertCount(2, $commissionList[0]['DATA']);
    }

    public function test_merchant_info_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('MerchantInfoResponseApiError.txt');

        $response = new MerchantInfoResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Magaza bilgisi alinamadi', $response->getMessage());
    }
}
