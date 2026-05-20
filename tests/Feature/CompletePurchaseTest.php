<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayNKolay\Message\CompletePurchaseRequest;
use Omnipay\PayNKolay\Message\CompletePurchaseResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class CompletePurchaseTest extends TestCase
{
    public function test_complete_purchase_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/CompletePurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('test-sx-token', $data['sx']);
        self::assertEquals('PNK-REF-123456', $data['referenceCode']);
    }

    public function test_complete_purchase_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/CompletePurchaseRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_complete_purchase_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('CompletePurchaseResponseSuccess.txt');

        $response = new CompletePurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('PNK-REF-123456', $response->getTransactionReference());
        $this->assertEquals('2', $response->getCode());
    }

    public function test_complete_purchase_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('CompletePurchaseResponseApiError.txt');

        $response = new CompletePurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('3D dogrulama basarisiz', $response->getMessage());
    }

    public function test_get_message_falls_back_to_bank_code_when_response_data_empty()
    {
        // Gateway sometimes returns the bank decline code with an empty
        // RESPONSE_DATA — the ErrorCodes table should fill in the text.
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'RESPONSE_CODE' => 51,
            'RESPONSE_DATA' => '',
        ]);

        $this->assertEquals('Yetersiz Bakiye / Yetersiz Kart Limiti', $response->getMessage());
    }
}
