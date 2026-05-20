<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\PayByLinkDeleteRequest;
use Omnipay\PayNKolay\Message\PayByLinkDeleteResponse;
use Omnipay\PayNKolay\Message\PayByLinkRequest;
use Omnipay\PayNKolay\Message\PayByLinkResponse;
use Omnipay\PayNKolay\Message\PayByLinkSendRequest;
use Omnipay\PayNKolay\Message\PayByLinkSendResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class PayByLinkTest extends TestCase
{
    public function test_pay_by_link_request_uses_currency_code_field()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/PayByLinkRequest.json'), true, 512, JSON_THROW_ON_ERROR);

        $request = new PayByLinkRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        self::assertEquals('test-sx-token', $data['sx']);
        self::assertEquals('ORDER-123', $data['clientRefCode']);
        self::assertEquals('100.00', $data['amount']);
        self::assertEquals('https://example.com/success', $data['successUrl']);
        self::assertEquals('https://example.com/fail', $data['failUrl']);
        self::assertEquals('true', $data['use3D']);
        self::assertArrayHasKey('currencyCode', $data);
        self::assertArrayNotHasKey('currencyNumber', $data);
        self::assertArrayHasKey('hashDatav2', $data);
        self::assertNotEmpty($data['hashDatav2']);
    }

    public function test_pay_by_link_response_extracts_redirect_url()
    {
        $httpResponse = $this->getMockHttpResponse('PayByLinkResponseSuccess.txt');

        $response = new PayByLinkResponse($this->getMockRequest(), $httpResponse);

        self::assertFalse($response->isSuccessful());
        self::assertTrue($response->isRedirect());
        self::assertEquals('GET', $response->getRedirectMethod());
        self::assertStringStartsWith('https://', $response->getRedirectUrl());
    }

    public function test_pay_by_link_send_uses_token_field_and_screaming_case()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/PayByLinkSendRequest.json'), true, 512, JSON_THROW_ON_ERROR);

        $request = new PayByLinkSendRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        self::assertEquals('test-sx-token', $data['TOKEN']);
        self::assertArrayNotHasKey('sx', $data);
        self::assertEquals('Customer Name', $data['FULL_NAME']);
        self::assertEquals('customer@example.com', $data['EMAIL']);
        self::assertEquals('5555555555', $data['GSM']);
        self::assertEquals('250.00', $data['AMOUNT']);
        self::assertEquals('2026-12-30', $data['LINK_EXPIRATION_TIME']);
        self::assertEquals('ORDER-456', $data['CLIENT_REFERENCE_CODE']);
        self::assertEquals('3', $data['INSTALLMENT']);
        self::assertEquals('Invoice payment', $data['EXPLANATION']);
        self::assertArrayHasKey('hashDatav2', $data);
    }

    public function test_pay_by_link_send_validation_error()
    {
        $request = new PayByLinkSendRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize([
            'sxToken' => 'test-sx-token',
            'merchantSecretKey' => 'test-merchant-secret-key',
            'testMode' => true,
        ]);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_pay_by_link_delete_request_minimal_payload()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/PayByLinkDeleteRequest.json'), true, 512, JSON_THROW_ON_ERROR);

        $request = new PayByLinkDeleteRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        self::assertEquals('test-sx-token', $data['sx']);
        self::assertEquals('by6353337820250825153001', $data['q']);
        self::assertArrayHasKey('hashDatav2', $data);
    }

    public function test_pay_by_link_delete_validation_error()
    {
        $request = new PayByLinkDeleteRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize([
            'sxToken' => 'test-sx-token',
            'merchantSecretKey' => 'test-merchant-secret-key',
            'testMode' => true,
        ]);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_pay_by_link_response_redirect_is_false_without_url()
    {
        $response = new PayByLinkResponse(
            $this->getMockRequest(),
            ['RESPONSE_CODE' => 0, 'RESPONSE_DATA' => 'Error message']
        );

        self::assertFalse($response->isRedirect());
        self::assertEquals('', $response->getRedirectUrl());
        self::assertEquals('Error message', $response->getMessage());
    }

    public function test_pay_by_link_delete_response_success()
    {
        $response = new PayByLinkDeleteResponse(
            $this->getMockRequest(),
            ['RESPONSE_CODE' => 2, 'RESPONSE_DATA' => 'Link deleted']
        );

        self::assertTrue($response->isSuccessful());
        self::assertEquals('2', $response->getCode());
    }

    public function test_pay_by_link_send_response_exposes_link_url()
    {
        $response = new PayByLinkSendResponse(
            $this->getMockRequest(),
            ['RESPONSE_CODE' => 2, 'LINK_URL' => 'https://example.com/link/abc']
        );

        self::assertTrue($response->isSuccessful());
        self::assertEquals('https://example.com/link/abc', $response->getLinkUrl());
    }
}
