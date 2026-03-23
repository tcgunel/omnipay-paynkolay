<?php

namespace Omnipay\PayNKolay\Tests\Feature;

use Omnipay\PayNKolay\Helpers\PayNKolayHelper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
	public function test_hash()
	{
		$hash = PayNKolayHelper::hash('test|data|string');

		self::assertNotEmpty($hash);
		self::assertIsString($hash);

		// SHA512 produces 64 bytes, base64 encoded = 88 characters
		self::assertEquals(88, strlen($hash));
	}

	public function test_generate_sale_hash()
	{
		$hash = PayNKolayHelper::generateSaleHash(
			'merchantId',
			'ORDER-001',
			'100.00',
			'https://example.com/success',
			'https://example.com/fail',
			'01.01.2025 12:00:00',
			'',
			'storeKey'
		);

		self::assertNotEmpty($hash);

		// Same inputs should produce same hash
		$hash2 = PayNKolayHelper::generateSaleHash(
			'merchantId',
			'ORDER-001',
			'100.00',
			'https://example.com/success',
			'https://example.com/fail',
			'01.01.2025 12:00:00',
			'',
			'storeKey'
		);

		self::assertEquals($hash, $hash2);
	}

	public function test_generate_cancel_refund_hash()
	{
		$hash = PayNKolayHelper::generateCancelRefundHash(
			'merchantPassword',
			'REF-001',
			'cancel',
			'',
			'',
			'storeKey'
		);

		self::assertNotEmpty($hash);
	}

	public function test_generate_merchant_info_hash()
	{
		$hash = PayNKolayHelper::generateMerchantInfoHash(
			'merchantId',
			'01.01.2025',
			'storeKey'
		);

		self::assertNotEmpty($hash);
	}

	public function test_format_amount()
	{
		self::assertEquals('100.00', PayNKolayHelper::formatAmount(100.0));
		self::assertEquals('100.50', PayNKolayHelper::formatAmount(100.5));
		self::assertEquals('0.99', PayNKolayHelper::formatAmount(0.99));
		self::assertEquals('1234.56', PayNKolayHelper::formatAmount(1234.56));
	}

	public function test_clean_html()
	{
		self::assertEquals('<html></html>', PayNKolayHelper::cleanHtml('<html></html>'));
		self::assertEquals('<html></html>', PayNKolayHelper::cleanHtml("\\r\\n<html></html>\\r\\n"));
		self::assertEquals('"test"', PayNKolayHelper::cleanHtml('\\"test\\"'));
		self::assertNull(PayNKolayHelper::cleanHtml(null));
		self::assertEquals('', PayNKolayHelper::cleanHtml(''));
	}
}
