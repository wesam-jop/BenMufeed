<?php

namespace Tests\Unit;

use App\Support\PhoneCountries;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoneCountriesTest extends TestCase
{
    #[Test]
    public function it_parses_syrian_whatsapp_number(): void
    {
        $parsed = PhoneCountries::parse('+963 9 1234 5678');

        $this->assertSame('963', $parsed['dial']);
        $this->assertSame('SY', $parsed['iso']);
        $this->assertSame('912345678', $parsed['local']);
    }

    #[Test]
    public function it_formats_digits_without_leading_zero(): void
    {
        $full = PhoneCountries::format('963', 'SY', '0912345678');

        $this->assertSame('963912345678', $full);
    }

    #[Test]
    public function default_country_is_syria(): void
    {
        $this->assertSame('963', PhoneCountries::defaultDial());
        $this->assertSame('963|SY', PhoneCountries::defaultOptionKey());
    }

    #[Test]
    public function frontend_phone_countries_include_syria_first(): void
    {
        $payload = PhoneCountries::forFrontend();

        $this->assertSame('963|SY', $payload['defaultKey']);
        $this->assertNotEmpty($payload['countries']);
        $this->assertSame('963|SY', $payload['countries'][0]['key']);
    }

    #[Test]
    public function it_validates_formatted_phone_length(): void
    {
        $this->assertTrue(PhoneCountries::isValidFormatted('963912345678'));
        $this->assertFalse(PhoneCountries::isValidFormatted('123'));
    }

    #[Test]
    public function options_include_searchable_labels(): void
    {
        $options = PhoneCountries::options();

        $this->assertArrayHasKey('963|SY', $options);
        $this->assertStringContainsString('سوريا', $options['963|SY']);
        $this->assertStringContainsString('+963', $options['963|SY']);
    }
}
