<?php

namespace Omnipay\PayNKolay\Helpers;

class PayNKolayHelper
{
    /**
     * SHA-512 over the input, base64-encoded. Used by every outgoing
     * `hashDatav2` field.
     */
    public static function hash(string $data): string
    {
        return base64_encode(hash('sha512', $data, true));
    }

    /**
     * Hash for /Vpos/v1/Payment (Purchase v1) and /Vpos/by-link-create
     * (Pay By Link).
     *
     * Format: sx|clientRefCode|amount|successUrl|failUrl|rnd|customerKey|merchantSecretKey
     */
    public static function generateSaleHash(
        string $sx,
        string $clientRefCode,
        string $amount,
        string $successUrl,
        string $failUrl,
        string $rnd,
        string $customerKey,
        string $merchantSecretKey
    ): string {
        return self::hash(implode('|', [
            $sx,
            $clientRefCode,
            $amount,
            $successUrl,
            $failUrl,
            $rnd,
            $customerKey,
            $merchantSecretKey,
        ]));
    }

    /**
     * Hash for /Vpos/v1/CancelRefundPayment.
     *
     * Format: sx|referenceCode|type|amount|trxDate|merchantSecretKey
     * trxDate format: YYYY.MM.DD
     */
    public static function generateCancelRefundHash(
        string $sx,
        string $referenceCode,
        string $type,
        string $amount,
        string $trxDate,
        string $merchantSecretKey
    ): string {
        return self::hash(implode('|', [
            $sx,
            $referenceCode,
            $type,
            $amount,
            $trxDate,
            $merchantSecretKey,
        ]));
    }

    /**
     * Hash for /Vpos/Payment/GetMerchandInformation.
     *
     * Format: sx|date|merchantSecretKey
     * date format: DD.MM.YYYY
     */
    public static function generateMerchantInfoHash(
        string $sx,
        string $date,
        string $merchantSecretKey
    ): string {
        return self::hash(implode('|', [
            $sx,
            $date,
            $merchantSecretKey,
        ]));
    }

    /**
     * Hash for the inbound 3DS postback. Uses a completely different algorithm
     * than the outgoing `hashDatav2` fields:
     *
     *   - Raw concatenation, NO `|` separator
     *   - SHA-1 (not SHA-512)
     *   - Hex-decode → base64-encode
     *
     * Field order matches the WooCommerce reference plugin (the only
     * working implementation we could verify against production):
     *
     *   MERCHANT_NO + REFERENCE_CODE + AUTH_CODE + RESPONSE_CODE
     *     + USE_3D + RND + INSTALLMENT + AUTHORIZATION_AMOUNT
     *     + MERCHANT_SECRET_KEY
     */
    public static function generatePostbackHash(
        string $merchantNo,
        string $referenceCode,
        string $authCode,
        string $responseCode,
        string $use3D,
        string $rnd,
        string $installment,
        string $authorizationAmount,
        string $merchantSecretKey
    ): string {
        $hashString = $merchantNo
            . $referenceCode
            . $authCode
            . $responseCode
            . $use3D
            . $rnd
            . $installment
            . $authorizationAmount
            . $merchantSecretKey;

        return base64_encode(pack('H*', sha1($hashString)));
    }

    /**
     * Verify an inbound 3DS postback. Reads the documented fields out of
     * `$postback`, recomputes the postback hash and compares it (in
     * constant time) to the `hashData` field Paynkolay sent.
     *
     * Returns false on any missing field or hash mismatch.
     */
    public static function verifyPostbackHash(array $postback, string $merchantSecretKey): bool
    {
        if ($merchantSecretKey === '') {
            return false;
        }

        $supplied = (string) ($postback['hashData'] ?? '');

        if ($supplied === '') {
            return false;
        }

        $computed = self::generatePostbackHash(
            (string) ($postback['MERCHANT_NO'] ?? ''),
            (string) ($postback['REFERENCE_CODE'] ?? ''),
            (string) ($postback['AUTH_CODE'] ?? ''),
            (string) ($postback['RESPONSE_CODE'] ?? ''),
            (string) ($postback['USE_3D'] ?? ''),
            (string) ($postback['RND'] ?? ''),
            (string) ($postback['INSTALLMENT'] ?? ''),
            (string) ($postback['AUTHORIZATION_AMOUNT'] ?? ''),
            $merchantSecretKey,
        );

        return hash_equals($computed, $supplied);
    }

    /**
     * Format amount with two decimals and dot separator (e.g. 100.5 -> "100.50").
     */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Strip escaped newlines/quotes the gateway emits inside HTML payloads.
     */
    public static function cleanHtml(?string $input): ?string
    {
        if (empty($input)) {
            return $input;
        }

        return trim(str_replace(
            ['\\r', '\\n', "\r", "\n", '\\"'],
            ['', '', '', '', '"'],
            $input
        ));
    }
}
