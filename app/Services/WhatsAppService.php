<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;

    protected string $sendPath;

    protected string $user;

    protected ?string $device;

    protected int $timeoutSeconds;

    protected int $retries;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.mirsalli.base_url', 'https://mirsalli.com/myapp'), '/');
        $this->sendPath = ltrim((string) config('services.mirsalli.send_path', 'sendtextmsg.php'), '/');
        $this->user = (string) (config('services.mirsalli.user') ?? '');
        $this->device = config('services.mirsalli.device');
        $this->timeoutSeconds = (int) config('services.mirsalli.timeout', 15);
        $this->retries = max(0, (int) config('services.mirsalli.retries', 2));
    }

    public function isConfigured(): bool
    {
        return $this->user !== '';
    }

    /**
     * @return array{success: bool, status: ?int, error: ?string, message_id: ?string}
     */
    public function sendTextDetailed(string $phone, string $message, string $type = 'custom'): array
    {
        if (! $this->isConfigured()) {
            Log::warning('Mirsalli send skipped: MIRSALLI_USER is not set', ['type' => $type]);

            return [
                'success' => false,
                'status' => null,
                'error' => 'Mirsalli is not configured',
                'message_id' => null,
            ];
        }

        $formattedPhone = $this->formatPhoneNumber($phone);

        return $this->sendMessage($formattedPhone, $message, $type);
    }

    public function sendText(string $phone, string $message, string $type = 'custom'): bool
    {
        return $this->sendTextDetailed($phone, $message, $type)['success'] === true;
    }

    /**
     * @return array{success: bool, status: ?int, error: ?string, message_id: ?string}
     */
    protected function sendMessage(string $formattedPhone, string $message, string $type): array
    {
        $payload = [
            'user' => $this->user,
            'phone' => $formattedPhone,
            'message' => $message,
        ];

        if (is_string($this->device) && trim($this->device) !== '') {
            $payload['device'] = trim($this->device);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(min(10, $this->timeoutSeconds))
                ->timeout($this->timeoutSeconds)
                ->retry($this->retries, 700)
                ->post("{$this->baseUrl}/{$this->sendPath}", $payload);

            $responseBody = $response->json();
            $isOk = is_array($responseBody) && (($responseBody['ok'] ?? false) === true);

            if ($response->successful() && $isOk) {
                Log::info('Mirsalli message sent', [
                    'phone' => $formattedPhone,
                    'type' => $type,
                    'message_id' => $responseBody['data']['message_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'status' => $response->status(),
                    'message_id' => $responseBody['data']['message_id'] ?? null,
                    'error' => null,
                ];
            }

            $error = is_array($responseBody) ? ($responseBody['error'] ?? 'Unknown error') : 'Unexpected response format';
            Log::error('Mirsalli send failed', [
                'phone' => $formattedPhone,
                'type' => $type,
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'message_id' => null,
                'error' => $error,
            ];
        } catch (\Exception $exception) {
            Log::warning('Mirsalli POST failed, trying GET fallback', [
                'phone' => $formattedPhone,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);

            return $this->sendMessageViaGetFallback($payload, $formattedPhone, $type);
        }
    }

    /**
     * @param  array<string, string>  $payload
     * @return array{success: bool, status: ?int, error: ?string, message_id: ?string}
     */
    protected function sendMessageViaGetFallback(array $payload, string $formattedPhone, string $type): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(min(10, $this->timeoutSeconds))
                ->timeout(max(20, $this->timeoutSeconds))
                ->retry($this->retries + 1, 1000)
                ->get("{$this->baseUrl}/{$this->sendPath}", $payload);

            $responseBody = $response->json();
            $isOk = is_array($responseBody) && (($responseBody['ok'] ?? false) === true);

            if ($response->successful() && $isOk) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'message_id' => $responseBody['data']['message_id'] ?? null,
                    'error' => null,
                ];
            }

            $error = is_array($responseBody)
                ? ($responseBody['error'] ?? 'Unknown fallback error')
                : 'Unexpected fallback response format';

            return [
                'success' => false,
                'status' => $response->status(),
                'message_id' => null,
                'error' => $error,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'status' => null,
                'message_id' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone) ?? '';

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '09')) {
            $phone = '963'.substr($phone, 1);
        }

        if ($phone !== '' && ! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }
}
