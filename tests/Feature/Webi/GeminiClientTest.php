<?php

namespace Tests\Feature\Webi;

use App\Services\Webi\GeminiApiException;
use App\Services\Webi\GeminiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Connectivity itself was verified live against the real Gemini API (2026-07-04,
 * after the API key/project quota issue was resolved) — see the task 2.5 report.
 * These tests mock the HTTP layer instead of hitting the real network on every
 * run: real-network verification is a one-off smoke test, not something that
 * belongs in the regression suite (slow, costs quota, and would make CI flaky
 * against transient provider errors like the 503 "high demand" seen live).
 */
class GeminiClientTest extends TestCase
{
    private function client(): GeminiClient
    {
        return new GeminiClient('fake-key', 'gemini-test-model');
    }

    public function test_successful_response_returns_generated_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Halo, ini balasan WEBI.']]]],
                ],
            ], 200),
        ]);

        $text = $this->client()->generate('system prompt', [], 'Halo WEBI');

        $this->assertSame('Halo, ini balasan WEBI.', $text);
    }

    public function test_conversation_history_is_forwarded_as_prior_turns(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'oke']]]]],
            ], 200),
        ]);

        $this->client()->generate('system prompt', [
            ['role' => 'user', 'text' => 'pertanyaan sebelumnya'],
            ['role' => 'model', 'text' => 'jawaban sebelumnya'],
        ], 'pertanyaan baru');

        Http::assertSent(function ($request) {
            $contents = $request->data()['contents'];

            return count($contents) === 3
                && $contents[0]['parts'][0]['text'] === 'pertanyaan sebelumnya'
                && $contents[1]['parts'][0]['text'] === 'jawaban sebelumnya'
                && $contents[2]['parts'][0]['text'] === 'pertanyaan baru'
                && $request->data()['systemInstruction']['parts'][0]['text'] === 'system prompt';
        });
    }

    public function test_missing_api_key_fails_gracefully_without_a_network_call(): void
    {
        Http::fake();

        $client = new GeminiClient('', 'gemini-test-model');

        try {
            $client->generate('system prompt', [], 'Halo');
            $this->fail('Expected GeminiApiException was not thrown.');
        } catch (GeminiApiException $e) {
            $this->assertNotEmpty($e->userMessage);
        }

        Http::assertNothingSent();
    }

    public function test_rate_limit_error_is_translated_to_a_friendly_message(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['code' => 429, 'message' => 'quota exceeded']], 429),
        ]);

        try {
            $this->client()->generate('system prompt', [], 'Halo');
            $this->fail('Expected GeminiApiException was not thrown.');
        } catch (GeminiApiException $e) {
            $this->assertStringContainsString('kebanjiran', $e->userMessage);
        }
    }

    public function test_server_error_is_translated_to_a_friendly_message(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['code' => 503, 'message' => 'high demand']], 503),
        ]);

        try {
            $this->client()->generate('system prompt', [], 'Halo');
            $this->fail('Expected GeminiApiException was not thrown.');
        } catch (GeminiApiException $e) {
            $this->assertStringContainsString('bermasalah di sisi mereka', $e->userMessage);
        }
    }

    public function test_model_not_found_error_is_translated_to_a_friendly_message(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['code' => 404, 'message' => 'model not found']], 404),
        ]);

        try {
            $this->client()->generate('system prompt', [], 'Halo');
            $this->fail('Expected GeminiApiException was not thrown.');
        } catch (GeminiApiException $e) {
            $this->assertStringContainsString('konfigurasi model', $e->userMessage);
        }
    }

    public function test_malformed_response_without_candidates_fails_gracefully(): void
    {
        Http::fake([
            '*' => Http::response(['candidates' => []], 200),
        ]);

        try {
            $this->client()->generate('system prompt', [], 'Halo');
            $this->fail('Expected GeminiApiException was not thrown.');
        } catch (GeminiApiException $e) {
            $this->assertNotEmpty($e->userMessage);
        }
    }

    public function test_thinking_level_is_sent_when_configured(): void
    {
        Http::fake([
            '*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'oke']]]]]], 200),
        ]);

        $client = new GeminiClient('fake-key', 'gemini-test-model', 'low');
        $client->generate('system prompt', [], 'Halo');

        Http::assertSent(fn ($request) => $request->data()['generationConfig']['thinkingConfig']['thinkingLevel'] === 'low');
    }

    /**
     * Locks in the production default itself — bumped from "low" to "minimal"
     * same day after a live side-by-side showed minimal at ~2.1s vs low/medium
     * at ~4.3-4.7s for the same question (new dedicated GCP project, fresh quota).
     */
    public function test_default_thinking_level_config_is_minimal(): void
    {
        $this->assertSame('minimal', config('services.gemini.thinking_level'));
    }

    public function test_thinking_config_is_omitted_when_thinking_level_is_not_set(): void
    {
        // the client's own ?? falls back to config('services.gemini.thinking_level')
        // when the constructor arg is null, so that fallback needs clearing too
        // to actually exercise the "omitted" branch.
        config(['services.gemini.thinking_level' => null]);

        Http::fake([
            '*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'oke']]]]]], 200),
        ]);

        $client = new GeminiClient('fake-key', 'gemini-test-model', null);
        $client->generate('system prompt', [], 'Halo');

        Http::assertSent(fn ($request) => ! array_key_exists('generationConfig', $request->data()));
    }

    public function test_connection_exception_fails_gracefully(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        try {
            $this->client()->generate('system prompt', [], 'Halo');
            $this->fail('Expected GeminiApiException was not thrown.');
        } catch (GeminiApiException $e) {
            $this->assertStringContainsString('timeout', $e->userMessage);
        }
    }
}
