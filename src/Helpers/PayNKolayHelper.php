<?php

namespace Omnipay\PayNKolay\Helpers;

class PayNKolayHelper
{
    /**
     * Generate SHA512 hash and base64 encode.
     *
     * @param string $data Pipe-delimited string to hash
     * @return string Base64 encoded SHA512 hash
     */
    public static function hash(string $data): string
    {
        return base64_encode(hash('sha512', $data, true));
    }

    /**
     * Generate hash for sale/purchase requests.
     * Format: sx|clientRefCode|amount|successUrl|failUrl|rnd|customerKey|merchantStorekey
     */
    public static function generateSaleHash(
        string $sx,
        string $clientRefCode,
        string $amount,
        string $successUrl,
        string $failUrl,
        string $rnd,
        string $customerKey,
        string $merchantStorekey
    ): string {
        $hashString = implode('|', [
            $sx,
            $clientRefCode,
            $amount,
            $successUrl,
            $failUrl,
            $rnd,
            $customerKey,
            $merchantStorekey,
        ]);

        return self::hash($hashString);
    }

    /**
     * Generate hash for cancel/refund requests.
     * Format: sx|referenceCode|type|amount|trxDate|merchantStorekey
     */
    public static function generateCancelRefundHash(
        string $sx,
        string $referenceCode,
        string $type,
        string $amount,
        string $trxDate,
        string $merchantStorekey
    ): string {
        $hashString = implode('|', [
            $sx,
            $referenceCode,
            $type,
            $amount,
            $trxDate,
            $merchantStorekey,
        ]);

        return self::hash($hashString);
    }

    /**
     * Generate hash for merchant information requests.
     * Format: sx|date|merchantStorekey
     */
    public static function generateMerchantInfoHash(
        string $sx,
        string $date,
        string $merchantStorekey
    ): string {
        $hashString = implode('|', [
            $sx,
            $date,
            $merchantStorekey,
        ]);

        return self::hash($hashString);
    }

    /**
     * Format amount as Turkish decimal format (e.g. 100.50 -> "100.50").
     * The C# code uses tr-TR culture N2 format then replaces dot and comma.
     */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Clean HTML response from escaped characters.
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
