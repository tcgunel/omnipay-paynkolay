<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\PurchaseRequest;
use Omnipay\PayNKolay\Message\PurchaseResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_purchase_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('testMerchantId', $data['sx']);
        self::assertEquals('ORDER-123456', $data['clientRefCode']);
        self::assertEquals('100.00', $data['amount']);
        self::assertEquals('1', $data['installmentNo']);
        self::assertEquals('Example User', $data['cardHolderName']);
        self::assertEquals('4155650100416111', $data['cardNumber']);
        self::assertEquals('123', $data['cvv']);
        self::assertEquals('SALES', $data['transactionType']);
        self::assertEquals('API', $data['environment']);
        self::assertEquals('949', $data['currencyNumber']);
        self::assertEquals('false', $data['use3D']);
        self::assertEquals('', $data['successUrl']);
        self::assertEquals('', $data['failUrl']);
        self::assertArrayHasKey('hashDatav2', $data);
        self::assertNotEmpty($data['hashDatav2']);
        self::assertArrayHasKey('rnd', $data);
    }

    public function test_purchase_request_3d_secure()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
        $options['secure'] = true;
        $options['returnUrl'] = 'https://example.com/success';
        $options['cancelUrl'] = 'https://example.com/fail';

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertEquals('true', $data['use3D']);
        self::assertEquals('https://example.com/success', $data['successUrl']);
        self::assertEquals('https://example.com/fail', $data['failUrl']);
    }

    public function test_purchase_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_purchase_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseSuccess.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('PNK-REF-123456', $response->getTransactionReference());
        $this->assertEquals('2', $response->getCode());
    }

    public function test_purchase_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseApiError.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('Kart numarasi hatali', $response->getMessage());
    }

    public function test_purchase_response_3d_redirect()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponse3D.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNotNull($response->getRedirectHtml());
        $this->assertStringContainsString('3D Secure Redirect', $response->getRedirectHtml());
    }
}
