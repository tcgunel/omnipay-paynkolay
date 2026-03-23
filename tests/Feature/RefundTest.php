<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\RefundRequest;
use Omnipay\PayNKolay\Message\RefundResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class RefundTest extends TestCase
{
    public function test_refund_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('testPassword', $data['sx']);
        self::assertEquals('PNK-REF-123456', $data['referenceCode']);
        self::assertEquals('refund', $data['type']);
        self::assertEquals('50.00', $data['amount']);
        self::assertEquals('', $data['trxDate']);
        self::assertArrayHasKey('hashDatav2', $data);
        self::assertNotEmpty($data['hashDatav2']);
    }

    public function test_refund_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_refund_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('RefundResponseSuccess.txt');

        $response = new RefundResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('2', $response->getCode());
    }

    public function test_refund_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('RefundResponseApiError.txt');

        $response = new RefundResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Iade islemi basarisiz', $response->getMessage());
    }
}
