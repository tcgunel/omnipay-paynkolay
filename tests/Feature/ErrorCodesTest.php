<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\PayNKolay\Constants\ErrorCodes;
use PHPUnit\Framework\TestCase;

class ErrorCodesTest extends TestCase
{
    public function test_known_codes_resolve_to_documented_messages()
    {
        self::assertEquals('Yetersiz Bakiye / Yetersiz Kart Limiti', ErrorCodes::message('51'));
        self::assertEquals('CVV Hatalı', ErrorCodes::message('82'));
        self::assertEquals('Sistem Hatası', ErrorCodes::message('99'));
    }

    public function test_pads_single_digit_codes_for_lookup()
    {
        // Paynkolay docs key on "00"; gateway sometimes emits "0".
        self::assertEquals('Onaylandı / İşlem Başarılı', ErrorCodes::message('0'));
        self::assertEquals('Tekrar Deneyebilirsiniz', ErrorCodes::message('9'));
        self::assertEquals('Tekrar Deneyebilirsiniz', ErrorCodes::message(9));
    }

    public function test_unknown_code_returns_null()
    {
        self::assertNull(ErrorCodes::message('xx'));
        self::assertNull(ErrorCodes::message(null));
        self::assertNull(ErrorCodes::message(''));
    }
}
