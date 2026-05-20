<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\PayNKolay\Helpers\PayNKolayHelper;
use Omnipay\PayNKolay\Message\Notification;
use Omnipay\PayNKolay\Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_is_successful_requires_response_code_two_and_auth_code()
    {
        $notification = new Notification([
            'RESPONSE_CODE' => '2',
            'AUTH_CODE' => '123456',
        ]);

        self::assertTrue($notification->isSuccessful());
        self::assertEquals(NotificationInterface::STATUS_COMPLETED, $notification->getTransactionStatus());
    }

    public function test_is_successful_rejects_zero_auth_code()
    {
        $notification = new Notification([
            'RESPONSE_CODE' => '2',
            'AUTH_CODE' => '0',
        ]);

        self::assertFalse($notification->isSuccessful());
        self::assertEquals(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
    }

    public function test_is_successful_rejects_empty_auth_code()
    {
        $notification = new Notification([
            'RESPONSE_CODE' => '2',
            'AUTH_CODE' => '',
        ]);

        self::assertFalse($notification->isSuccessful());
    }

    public function test_is_successful_rejects_non_two_response_code()
    {
        foreach (['0', '1', '3', '99'] as $bad) {
            $notification = new Notification([
                'RESPONSE_CODE' => $bad,
                'AUTH_CODE' => '123456',
            ]);

            self::assertFalse($notification->isSuccessful(), "RESPONSE_CODE=$bad must fail");
        }
    }

    public function test_accessors_return_documented_fields()
    {
        $notification = new Notification([
            'REFERENCE_CODE' => 'IKSIRPF450511',
            'CLIENT_REFERENCE_CODE' => 'ORDER-7',
            'RESPONSE_CODE' => '2',
            'RESPONSE_DATA' => 'Islem basarili',
        ]);

        self::assertEquals('IKSIRPF450511', $notification->getTransactionReference());
        self::assertEquals('ORDER-7', $notification->getTransactionId());
        self::assertEquals('2', $notification->getCode());
        self::assertEquals('Islem basarili', $notification->getMessage());
    }

    public function test_verify_hash_accepts_correct_postback_hash()
    {
        $merchantSecretKey = 'placeholder-secret';

        $postback = [
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'IKSIRPF450511',
            'AUTH_CODE' => '123456',
            'RESPONSE_CODE' => '2',
            'USE_3D' => 'true',
            'RND' => '01.01.2025 12:00:00',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => '100.00',
            'RESPONSE_DATA' => 'Islem basarili',
            'CLIENT_REFERENCE_CODE' => 'ORDER-7',
        ];

        $postback['hashData'] = PayNKolayHelper::generatePostbackHash(
            $postback['MERCHANT_NO'],
            $postback['REFERENCE_CODE'],
            $postback['AUTH_CODE'],
            $postback['RESPONSE_CODE'],
            $postback['USE_3D'],
            $postback['RND'],
            $postback['INSTALLMENT'],
            $postback['AUTHORIZATION_AMOUNT'],
            $merchantSecretKey,
        );

        $notification = new Notification($postback);

        self::assertTrue($notification->verifyHash($merchantSecretKey));
        self::assertFalse($notification->verifyHash('wrong-secret'));
    }

    public function test_verify_hash_rejects_missing_hash_data()
    {
        $notification = new Notification([
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'IKSIRPF450511',
        ]);

        self::assertFalse($notification->verifyHash('placeholder-secret'));
    }

    public function test_verify_hash_rejects_empty_secret()
    {
        $notification = new Notification(['hashData' => 'whatever']);

        self::assertFalse($notification->verifyHash(''));
    }

    public function test_generate_postback_hash_matches_woocommerce_reference_algorithm()
    {
        // Lock the wire format: concat (no separator) + sha1 + hex-decode + base64.
        // Result is 28 characters (SHA-1 yields 20 raw bytes; base64-encoded = 28).
        $hash = PayNKolayHelper::generatePostbackHash(
            '273',
            'IKSIRPF450511',
            '123456',
            '2',
            'true',
            '01.01.2025 12:00:00',
            '1',
            '100.00',
            'placeholder-secret',
        );

        $expected = base64_encode(pack('H*', sha1(
            '273IKSIRPF450511123456' . '2' . 'true' . '01.01.2025 12:00:00' . '1' . '100.00' . 'placeholder-secret'
        )));

        self::assertEquals($expected, $hash);
        self::assertEquals(28, strlen($hash));
    }

    public function test_accept_notification_through_gateway()
    {
        $notification = $this->gateway->acceptNotification([
            'RESPONSE_CODE' => '2',
            'AUTH_CODE' => '123456',
            'REFERENCE_CODE' => 'IKSIRPF450511',
        ]);

        self::assertInstanceOf(Notification::class, $notification);
        self::assertTrue($notification->isSuccessful());
    }
}
