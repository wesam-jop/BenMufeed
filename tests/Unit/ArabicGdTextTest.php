<?php

namespace Tests\Unit;

use App\Support\ArabicGdText;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArabicGdTextTest extends TestCase
{
    #[Test]
    public function it_shapes_arabic_for_gd_display(): void
    {
        $raw = 'امسح الرمز لطلبك من الجوال';
        $shaped = ArabicGdText::shape($raw);

        $this->assertNotSame($raw, $shaped);
        $this->assertNotEmpty($shaped);
    }

    #[Test]
    public function it_leaves_latin_only_strings_unchanged(): void
    {
        $url = '127.0.0.1:8000';

        $this->assertSame($url, ArabicGdText::shape($url));
    }
}
