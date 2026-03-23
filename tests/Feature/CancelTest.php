<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\CancelRequest;
use Omnipay\PayNKolay\Message\CancelResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class CancelTest extends TestCase
{
    public function test_cancel_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/CancelRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new CancelRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('testPassword', $data['sx']);
        self::assertEquals('PNK-REF-123456', $data['referenceCode']);
        self::assertEquals('cancel', $data['type']);
        self::assertEquals('', $data['amount']);
        self::assertEquals('', $data['trxDate']);
        self::assertArrayHasKey('hashDatav2', $data);
        self::assertNotEmpty($data['hashDatav2']);
    }

    public function test_cancel_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/CancelRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new CancelRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_cancel_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('CancelResponseSuccess.txt');

        $response = new CancelResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('2', $response->getCode());
    }

    public function test_cancel_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('CancelResponseApiError.txt');

        $response = new CancelResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Islem iptal edilemedi', $response->getMessage());
    }
}
