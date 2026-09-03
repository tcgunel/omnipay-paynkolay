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

    public function test_verify_hash_accepts_documented_hash_data_v2()
    {
        $merchantSecretKey = 'placeholder-secret';

        $postback = [
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'IKSIRPF450511',
            'AUTH_CODE' => '123456',
            'RESPONSE_CODE' => '2',
            'USE_3D' => 'true',
            'RND' => '1645700316156',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => '100.00',
            'CURRENCY_CODE' => 'TRY',
            'RESPONSE_DATA' => 'Islem basarili',
            'CLIENT_REFERENCE_CODE' => 'ORDER-7',
        ];

        // Built exactly as the vendor's own PHP sample does, independently of
        // the helper under test.
        $postback['hashDataV2'] = base64_encode(hash('sha512', implode('|', [
            '273',
            'IKSIRPF450511',
            '123456',
            '2',
            'true',
            '1645700316156',
            '1',
            '100.00',
            'TRY',
            $merchantSecretKey,
        ]), true));

        $notification = new Notification($postback);

        self::assertTrue($notification->verifyHash($merchantSecretKey));
        self::assertFalse($notification->verifyHash('wrong-secret'));
    }

    /**
     * Regression: the result postback is JSON, so USE_3D arrives as a real
     * boolean. PHP casts true to "1" while the gateway hashes "true", which
     * made every signature check fail against production.
     *
     * Field values are transcribed from a real (failing) production postback;
     * the secret is a stand-in, so the expected hash is computed here rather
     * than hard-coded.
     */
    public function test_verify_hash_accepts_boolean_use_3d()
    {
        $merchantSecretKey = 'placeholder-secret';

        $postback = [
            'MERCHANT_NO' => '400025093',
            'REFERENCE_CODE' => 'IKSIRPF327290363',
            'AUTH_CODE' => '667127',
            'RESPONSE_CODE' => '2',
            'USE_3D' => true,
            'RND' => '1788418388996',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => '1.00',
            'CURRENCY_CODE' => 'TRY',
            'AUTO_COMPLETE' => true,
        ];

        $postback['hashDataV2'] = base64_encode(hash('sha512', implode('|', [
            '400025093',
            'IKSIRPF327290363',
            '667127',
            '2',
            'true',
            '1788418388996',
            '1',
            '1.00',
            'TRY',
            $merchantSecretKey,
        ]), true));

        self::assertTrue((new Notification($postback))->verifyHash($merchantSecretKey));
    }

    public function test_verify_hash_accepts_boolean_false_use_3d()
    {
        $merchantSecretKey = 'placeholder-secret';

        $postback = [
            'MERCHANT_NO' => '400025093',
            'REFERENCE_CODE' => 'IKSIRPF327290363',
            'AUTH_CODE' => '667127',
            'RESPONSE_CODE' => '2',
            'USE_3D' => false,
            'RND' => '1788418388996',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => '1.00',
            'CURRENCY_CODE' => 'TRY',
        ];

        $postback['hashDataV2'] = base64_encode(hash('sha512', implode('|', [
            '400025093',
            'IKSIRPF327290363',
            '667127',
            '2',
            'false',
            '1788418388996',
            '1',
            '1.00',
            'TRY',
            $merchantSecretKey,
        ]), true));

        self::assertTrue((new Notification($postback))->verifyHash($merchantSecretKey));
    }

    public function test_verify_hash_accepts_postback_without_currency_code()
    {
        $merchantSecretKey = 'placeholder-secret';

        // The 3D AutoComplete return does not carry CURRENCY_CODE.
        $postback = [
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'IKSIRPF450511',
            'AUTH_CODE' => '123456',
            'RESPONSE_CODE' => '2',
            'USE_3D' => 'true',
            'RND' => '1645700316156',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => '100.00',
        ];

        $postback['hashDataV2'] = base64_encode(hash('sha512', implode('|', [
            '273',
            'IKSIRPF450511',
            '123456',
            '2',
            'true',
            '1645700316156',
            '1',
            '100.00',
            $merchantSecretKey,
        ]), true));

        self::assertTrue((new Notification($postback))->verifyHash($merchantSecretKey));
    }

    public function test_verify_hash_rejects_tampered_amount()
    {
        $merchantSecretKey = 'placeholder-secret';

        $postback = [
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'IKSIRPF450511',
            'AUTH_CODE' => '123456',
            'RESPONSE_CODE' => '2',
            'USE_3D' => 'true',
            'RND' => '1645700316156',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => '100.00',
            'CURRENCY_CODE' => 'TRY',
        ];

        $postback['hashDataV2'] = PayNKolayHelper::generateResponseHashV2(
            $postback['MERCHANT_NO'],
            $postback['REFERENCE_CODE'],
            $postback['AUTH_CODE'],
            $postback['RESPONSE_CODE'],
            $postback['USE_3D'],
            $postback['RND'],
            $postback['INSTALLMENT'],
            $postback['AUTHORIZATION_AMOUNT'],
            $postback['CURRENCY_CODE'],
            $merchantSecretKey,
        );

        $postback['AUTHORIZATION_AMOUNT'] = '1.00';

        self::assertFalse((new Notification($postback))->verifyHash($merchantSecretKey));
    }

    public function test_verify_hash_rejects_legacy_hash_data_only_postback()
    {
        // hashData alone is not verifiable: Paynkolay publishes no spec for it.
        $notification = new Notification([
            'MERCHANT_NO' => '273',
            'REFERENCE_CODE' => 'IKSIRPF450511',
            'hashData' => 'NL5R04M8C4sjDNgBUnEZ/RCocEo=',
        ]);

        self::assertFalse($notification->verifyHash('placeholder-secret'));
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
        $notification = new Notification(['hashDataV2' => 'whatever']);

        self::assertFalse($notification->verifyHash(''));
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
