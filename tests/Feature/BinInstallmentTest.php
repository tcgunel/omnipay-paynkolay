<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\BinInstallmentRequest;
use Omnipay\PayNKolay\Message\BinInstallmentResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class BinInstallmentTest extends TestCase
{
	public function test_bin_installment_request()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/BinInstallmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new BinInstallmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		self::assertIsArray($data);
		self::assertEquals('testMerchantId', $data['sx']);
		self::assertEquals('100.00', $data['amount']);
		self::assertEquals('415565', $data['cardNumber']);
		self::assertEquals('false', $data['iscardvalid']);
	}

	public function test_bin_installment_request_validation_error()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/BinInstallmentRequest-ValidationError.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new BinInstallmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	public function test_bin_installment_response_success()
	{
		$httpResponse = $this->getMockHttpResponse('BinInstallmentResponseSuccess.txt');

		$response = new BinInstallmentResponse($this->getMockRequest(), $httpResponse);

		$this->assertTrue($response->isSuccessful());

		$installments = $response->getInstallments();
		$this->assertCount(3, $installments);
		$this->assertEquals(2, $installments[0]['INSTALLMENT']);
		$this->assertEquals(3, $installments[1]['INSTALLMENT']);
		$this->assertEquals(6, $installments[2]['INSTALLMENT']);
	}

	public function test_bin_installment_response_api_error()
	{
		$httpResponse = $this->getMockHttpResponse('BinInstallmentResponseApiError.txt');

		$response = new BinInstallmentResponse($this->getMockRequest(), $httpResponse);

		$this->assertFalse($response->isSuccessful());
		$this->assertEquals('Bin numarasi bulunamadi', $response->getMessage());
	}
}
