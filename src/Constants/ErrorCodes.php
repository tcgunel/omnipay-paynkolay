<?php

namespace Omnipay\PayNKolay\Constants;

/**
 * Paynkolay `RESPONSE_CODE` lookup, transcribed from
 * https://paynkolay.com.tr/entegrasyon/43-error-codes.php (2026-05-20).
 *
 * `RESPONSE_CODE` is what banks return, *not* what Paynkolay itself returns.
 * Note: Paynkolay's own "success" sentinel on successful payment posts is
 * `RESPONSE_CODE = 2` — that's a Paynkolay-internal code, not in this map.
 * The codes below are the underlying bank codes (00, 01, 51, etc.) the
 * gateway forwards back to us when something went wrong at the bank.
 */
class ErrorCodes
{
    /** @var array<string, string> */
    public const MESSAGES = [
        '00' => 'Onaylandı / İşlem Başarılı',
        '01' => 'Kart Bankasını Arayınız',
        '02' => 'Kart Bankasını Arayınız',
        '03' => 'POS Tanımlarını Kontrol Edin (Üye İşyeri)',
        '04' => 'Bloke Edilmiş Kart / Karta El Koyunuz',
        '05' => 'Kart Bankasını Arayınız / İşlem Onaylanmadı',
        '06' => 'Kart Bankasını Arayınız / İşlem Onaylanmadı',
        '07' => 'Bloke Edilmiş Kart / Karta El Koyunuz',
        '08' => 'Kart Bankasını Arayınız',
        '09' => 'Tekrar Deneyebilirsiniz',
        '11' => 'İşlem Gerçekleştirildi (VIP)',
        '12' => 'Geçersiz İşlem / Sanal POS Tanımsız İşlem Yetkisi',
        '13' => 'İşlem Tutarını Kontrol Edin',
        '14' => 'Geçersiz Kart Numarası',
        '15' => 'Tanımsız Kart Bankası / Kart Bankasını Arayınız',
        '17' => 'İşlem İptal Edildi',
        '18' => 'Kart İşleme Kapalı',
        '19' => 'Tekrar Deneyebilirsiniz',
        '21' => 'İşlem İptal Edilemedi',
        '25' => 'İşlem Sistemde Bulunamadı',
        '26' => 'Kart Bankasını Arayınız / İşlem Onaylanmadı',
        '28' => 'İşlem Sistemde Bulunamadı',
        '29' => 'İptal İşlemi Yapılamadı',
        '30' => 'Format Hatası Nedeni İle Başarısız / Tekrar Deneyiniz',
        '33' => 'Bloke Edilmiş Kart / Karta El Koyunuz',
        '34' => 'Bloke Edilmiş Kart / Karta El Koyunuz',
        '36' => 'Bloke Edilmiş Kart / Karta El Koyunuz',
        '38' => 'Hatalı Şifre Deneme Sayısı Aşıldı',
        '41' => 'Kayıp Kart',
        '43' => 'Kayıp Kart',
        '46' => 'İşlem Onaylanmadı',
        '51' => 'Yetersiz Bakiye / Yetersiz Kart Limiti',
        '52' => 'Karta Tanımlı Hesap Yok',
        '53' => 'Karta Tanımlı Hesap Yok',
        '54' => 'Kartın Son Kullanım Tarihi Dolmuş',
        '55' => 'Hatalı Kart Şifresi Girildi',
        '56' => 'Geçersiz Kart Numarası',
        '57' => 'Karta İzin Verilmeyen İşlem',
        '58' => 'POS Tanımlarını Kontrol Edin (Üye İşyeri)',
        '59' => 'Şüpheli İşlem',
        '60' => 'Karta İzin Verilmeyen İşlem',
        '61' => 'Kart Bankasını Arayınız',
        '62' => 'Kısıtlanmış Kart / Kendi Ülkesinde Geçerli Kart',
        '63' => 'Bu İşlem İçin Yetkiniz Bulunmuyor',
        '65' => 'Kartın İşlem Limitleri Aşıldı / Kart Bankasını Arayınız',
        '69' => 'Kart Bankasını Arayınız',
        '75' => 'Şifre Deneme Sayısı Aşıldı',
        '76' => 'Hatalı Kart Şifresi Girildi',
        '77' => 'Uyumsuz Veri Nedeni İle Red',
        '78' => 'Şifre Güvenilir Bulunmadı',
        '79' => 'ARQC Hatası',
        '80' => 'Kredi Kartı Geçerlilik Tarihi Hatalı',
        '81' => 'Şifreleme Hatası',
        '82' => 'CVV Hatalı',
        '83' => 'Şifre Doğrulama Hatası',
        '84' => 'CVV Hatalı',
        '85' => 'Onaylandı',
        '86' => 'Şifre Doğrulama Hatası',
        '88' => 'Şifreleme Hatası',
        '89' => 'Şifre Doğrulama Hatası',
        '90' => 'Tekrar Deneyiniz (Gün Sonu İşlemleri Yapılıyor)',
        '91' => 'Kart Bankası Yanıt Vermiyor',
        '92' => 'Kart Bankası Yanıt Vermiyor',
        '93' => 'E-ticaret İşlemlerine Kapalı Kart',
        '94' => 'Çift İşlem Gönderme',
        '95' => 'POS Gün Sonu Hatası',
        '96' => 'Kart Bankası Yanıt Vermiyor',
        '98' => 'Çift İşlem Gönderme',
        '99' => 'Sistem Hatası',
    ];

    /**
     * Look up the documented Turkish message for a bank response code.
     * Accepts integer or string input; pads single-digit codes to two
     * characters (Paynkolay emits "0" but the table is keyed on "00").
     */
    public static function message(string|int|null $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $key = str_pad((string) $code, 2, '0', STR_PAD_LEFT);

        return self::MESSAGES[$key] ?? null;
    }
}
