<?php

namespace App\Services\Webi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around Google Gemini's generateContent REST endpoint.
 *
 * docs/tech-stack.md 3: calls happen ONLY from this backend service via
 * Laravel's HTTP client (Guzzle-backed) — never from browser JS, so the API
 * key is never exposed to the client.
 */
class GeminiClient
{
    private const ENDPOINT_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    private const TIMEOUT_SECONDS = 20;

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
        private readonly ?string $thinkingLevel = null,
    ) {}

    /**
     * @param  array<int, array{role: string, text: string}>  $history  prior turns, oldest first
     *
     * @throws GeminiApiException
     */
    public function generate(string $systemPrompt, array $history, string $userMessage): string
    {
        $apiKey = $this->apiKey ?? config('services.gemini.key');
        $model = $this->model ?? config('services.gemini.model');
        $thinkingLevel = $this->thinkingLevel ?? config('services.gemini.thinking_level');

        if (empty($apiKey)) {
            throw new GeminiApiException(
                'WEBI lagi tidak bisa dihubungi karena konfigurasi API belum lengkap. Coba lagi nanti ya, atau kabari PIC kalau ini terus muncul.',
                'GEMINI_API_KEY is not configured.',
            );
        }

        $contents = [];
        foreach ($history as $turn) {
            $contents[] = [
                'role' => $turn['role'],
                'parts' => [['text' => $turn['text']]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
        ];

        // 2026-07-04: manual testing showed genuine ~20s timeouts ("0 bytes
        // received") — the model was still reasoning when the client gave up.
        // Only Gemini 3.x-family models support thinkingLevel (2.5-series uses
        // thinkingBudget instead), so this is skipped entirely if unset —
        // matters if GEMINI_MODEL is ever swapped to an older model family.
        if (! empty($thinkingLevel)) {
            $payload['generationConfig']['thinkingConfig']['thinkingLevel'] = $thinkingLevel;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(2, 500, throw: false)
                ->post(self::ENDPOINT_BASE."/{$model}:generateContent?key={$apiKey}", $payload);
        } catch (Throwable $e) {
            Log::warning('WEBI Gemini API call threw an exception', ['error' => $e->getMessage()]);

            throw new GeminiApiException(
                'WEBI lagi susah dihubungi (koneksi timeout). Coba kirim lagi beberapa saat ya.',
                'Gemini API request failed: '.$e->getMessage(),
            );
        }

        if ($response->failed()) {
            Log::warning('WEBI Gemini API returned an error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new GeminiApiException(
                $this->friendlyMessageFor($response->status()),
                "Gemini API returned HTTP {$response->status()}: {$response->body()}",
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            Log::warning('WEBI Gemini API returned no usable text', ['body' => $response->json()]);

            throw new GeminiApiException(
                'WEBI belum bisa kasih jawaban untuk pesan ini. Coba tulis ulang pertanyaanmu dengan cara lain ya.',
                'Gemini API response had no candidates[0].content.parts[0].text.',
            );
        }

        return $text;
    }

    private function friendlyMessageFor(int $status): string
    {
        return match (true) {
            $status === 404 => 'WEBI lagi ada masalah konfigurasi model AI. Kabari PIC ya supaya bisa dicek.',
            $status === 429 => 'WEBI lagi kebanjiran permintaan. Coba lagi beberapa saat lagi ya.',
            $status >= 500 => 'Layanan AI yang dipakai WEBI lagi bermasalah di sisi mereka. Coba lagi beberapa saat lagi ya.',
            default => 'WEBI lagi tidak bisa merespons. Coba lagi ya, atau kabari PIC kalau ini terus muncul.',
        };
    }
}
