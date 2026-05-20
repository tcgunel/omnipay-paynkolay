<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\PaymentListRequest;
use Omnipay\PayNKolay\Message\PaymentListResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class PaymentListTest extends TestCase
{
    public function test_payment_list_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PaymentListRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PaymentListRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('test-sx-list-token', $data['sx']);
        self::assertEquals('10.08.2025', $data['startDate']);
        self::assertEquals('15.08.2025', $data['endDate']);
        self::assertEquals('ORDER-12345', $data['clientRefCode']);
        self::assertArrayHasKey('hashDatav2', $data);
        self::assertNotEmpty($data['hashDatav2']);
    }

    public function test_payment_list_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PaymentListRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PaymentListRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_payment_list_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('PaymentListResponseSuccess.txt');

        $response = new PaymentListResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('2', $response->getCode());

        $transactions = $response->getTransactions();
        $this->assertCount(2, $transactions);
        $this->assertEquals('IKSIRPF450511', $transactions[0]['REFERENCE_CODE']);
    }

    public function test_payment_list_find_by_client_reference_code()
    {
        $httpResponse = $this->getMockHttpResponse('PaymentListResponseSuccess.txt');

        $response = new PaymentListResponse($this->getMockRequest(), $httpResponse);

        $found = $response->findByClientReferenceCode('ORDER-12345');

        $this->assertNotNull($found);
        $this->assertEquals('IKSIRPF450511', $found['REFERENCE_CODE']);
        $this->assertNull($response->findByClientReferenceCode('NOPE'));
    }

    public function test_payment_list_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('PaymentListResponseApiError.txt');

        $response = new PaymentListResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Tarih araliginda islem bulunamadi', $response->getMessage());
        $this->assertEquals([], $response->getTransactions());
    }
}
