<?php

namespace Tests\Unit;

use App\Services\Shared\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    public function test_send_message_posts_to_telegram_api(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '-100123',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $service = app(TelegramService::class);

        $this->assertTrue($service->sendMessage('Hello <b>world</b>'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '-100123'
                && $request['text'] === 'Hello <b>world</b>'
                && $request['parse_mode'] === 'HTML';
        });
    }

    public function test_send_message_returns_false_when_api_fails(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '-100123',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $service = app(TelegramService::class);

        $this->assertFalse($service->sendMessage('Test'));
    }
}
