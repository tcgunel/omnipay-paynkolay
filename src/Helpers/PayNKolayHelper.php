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
