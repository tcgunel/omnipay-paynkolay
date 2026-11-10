<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\PayNKolay\Helpers\PayNKolayHelper;
use Omnipay\PayNKolay\Message\CompletePurchaseRequest;
use Omnipay\PayNKolay\Message\CompletePurchaseResponse;
use Omnipay\PayNKolay\Message\PaymentListResponse;
use Omnipay\PayNKolay\Tests\TestCase;

class CompletePurchaseBindingTest extends TestCase
{
    private const SECRET = 'test-merchant-secret-key';

    /**
     * @return array<string, mixed>
     */
    private function genuinePostback(string $clientRef = 'ORDER-1', string $amount = '100.00'): array
    {
        $postback = [
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'PNK-REF-1',
            'CLIENT_REFERENCE_CODE' => $clientRef,
            'AUTH_CODE' => '654321',
            'RESPONSE_CODE' => '2',
            'USE_3D' => true,
            'RND' => 'gateway-rnd',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => $amount,
            'CURRENCY_CODE' => '949',
        ];

        $postback['hashDataV2'] = PayNKolayHelper::generateResponseHashV2(
            '273',
            'PNK-REF-1',
            '654321',
            '2',
            'true',
            'gateway-rnd',
            '1',
            $amount,
            '949',
            self::SECRET
        );

        return $postback;
    }

    /**
     * Gateway CompletePayment answer: approved, but without CLIENT_REFERENCE_CODE
     * or amount, so the binding has to come from the signed postback.
     *
     * @param array<string, mixed> $options
     */
    private function complete(array $options, array $gatewayAnswer = []): CompletePurchaseResponse
    {
        $request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize(array_merge([
            'sxToken' => 'test-sx-token',
            'merchantSecretKey' => self::SECRET,
            'referenceCode' => 'PNK-REF-1',
        ], $options));

        return new CompletePurchaseResponse($request, $gatewayAnswer ?: [
            'RESPONSE_CODE' => 2,
            'AUTH_CODE' => '654321',
            'REFERENCE_CODE' => 'PNK-REF-1',
            'RESPONSE_DATA' => '',
        ]);
    }

    public function test_genuine_postback_with_matching_reference_and_amount_is_successful()
    {
        $response = $this->complete([
            'postback' => $this->genuinePostback(),
            'transactionId' => 'ORDER-1',
            'amount' => '100.00',
        ]);

        self::assertTrue($response->isSuccessful());
        self::assertSame('PNK-REF-1', $response->getProviderReference());
        self::assertSame('ORDER-1', $response->getClientReference());
        self::assertSame(10000, $response->getSignedAmount());
    }

    public function test_empty_client_reference_in_the_gateway_answer_falls_back_to_the_postback()
    {
        // Live CompletePayment answers often carry CLIENT_REFERENCE_CODE as an
        // empty string; the signed postback then names the order.
        $response = $this->complete([
            'postback' => $this->genuinePostback(),
            'transactionId' => 'ORDER-1',
        ], [
            'RESPONSE_CODE' => 2,
            'AUTH_CODE' => '654321',
            'REFERENCE_CODE' => 'PNK-REF-1',
            'CLIENT_REFERENCE_CODE' => '',
        ]);

        self::assertTrue($response->isSuccessful());
        self::assertSame('ORDER-1', $response->getClientReference());
    }

    public function test_replayed_postback_against_another_order_is_not_successful()
    {
        $response = $this->complete([
            'postback' => $this->genuinePostback('ORDER-1'),
            'transactionId' => 'ORDER-2',
            'amount' => '100.00',
        ]);

        self::assertFalse($response->isSuccessful());
    }

    public function test_gateway_client_reference_mismatch_is_not_successful()
    {
        $response = $this->complete(['transactionId' => 'ORDER-2'], [
            'RESPONSE_CODE' => 2,
            'AUTH_CODE' => '654321',
            'REFERENCE_CODE' => 'PNK-REF-1',
            'CLIENT_REFERENCE_CODE' => 'ORDER-1',
        ]);

        self::assertFalse($response->isSuccessful());
    }

    public function test_different_expected_amount_is_not_successful()
    {
        foreach (['1000.00', '100.01', '99.99'] as $expected) {
            $response = $this->complete([
                'postback' => $this->genuinePostback(),
                'transactionId' => 'ORDER-1',
                'amount' => $expected,
            ]);

            self::assertFalse($response->isSuccessful(), "amount $expected must fail");
        }
    }

    public function test_expected_amount_without_any_amount_source_fails_closed()
    {
        $response = $this->complete(['amount' => '100.00']);

        self::assertFalse($response->isSuccessful());
    }

    public function test_postback_for_another_provider_reference_is_not_successful()
    {
        $response = $this->complete([
            'referenceCode' => 'PNK-REF-OTHER',
            'postback' => $this->genuinePostback(),
            'transactionId' => 'ORDER-1',
            'amount' => '100.00',
        ]);

        self::assertFalse($response->isSuccessful());
    }

    public function test_missing_hash_is_not_successful()
    {
        $postback = $this->genuinePostback();
        unset($postback['hashDataV2']);

        $response = $this->complete(['postback' => $postback, 'transactionId' => 'ORDER-1', 'amount' => '100.00']);

        self::assertFalse($response->isSuccessful());
        self::assertFalse(PayNKolayHelper::verifyPostbackHash($postback, self::SECRET));
    }

    public function test_tampered_amount_is_not_successful()
    {
        $postback = $this->genuinePostback('ORDER-1', '1.00');
        $postback['AUTHORIZATION_AMOUNT'] = '100.00';

        $response = $this->complete(['postback' => $postback, 'transactionId' => 'ORDER-1', 'amount' => '100.00']);

        self::assertFalse($response->isSuccessful());
    }

    public function test_empty_secret_is_not_successful()
    {
        $postback = $this->genuinePostback();

        $response = $this->complete([
            'merchantSecretKey' => '',
            'postback' => $postback,
            'transactionId' => 'ORDER-1',
            'amount' => '100.00',
        ]);

        self::assertFalse($response->isSuccessful());
        self::assertFalse(PayNKolayHelper::verifyPostbackHash($postback, ''));
    }

    public function test_legacy_call_without_expectations_behaves_as_before()
    {
        self::assertTrue($this->complete([])->isSuccessful());
        self::assertTrue($this->complete(['postback' => $this->genuinePostback()])->isSuccessful());
        self::assertSame('PNK-REF-1', $this->complete([])->getTransactionReference());
    }

    public function test_amount_to_minor_units()
    {
        self::assertSame(10000, PayNKolayHelper::amountToMinorUnits('100.00'));
        self::assertSame(10050, PayNKolayHelper::amountToMinorUnits('100,5'));
        self::assertSame(10000, PayNKolayHelper::amountToMinorUnits(100));
        self::assertSame(10050, PayNKolayHelper::amountToMinorUnits(100.5));
        self::assertNull(PayNKolayHelper::amountToMinorUnits('1.001'));
        self::assertNull(PayNKolayHelper::amountToMinorUnits('-1'));
        self::assertNull(PayNKolayHelper::amountToMinorUnits(null));
    }

    public function test_payment_list_confirms_payment()
    {
        $response = new PaymentListResponse(
            $this->getMockRequest(),
            $this->getMockHttpResponse('PaymentListResponseSuccess.txt')
        );

        self::assertTrue($response->confirmsPayment('IKSIRPF450511', 'ORDER-12345', 10000));
        self::assertFalse($response->confirmsPayment('IKSIRPF450511', 'ORDER-12346', 10000));
        self::assertFalse($response->confirmsPayment('IKSIRPF450511', 'ORDER-12345', 5000));
        self::assertFalse($response->confirmsPayment('IKSIRPF450512', 'ORDER-12345', 10000));
    }
}
