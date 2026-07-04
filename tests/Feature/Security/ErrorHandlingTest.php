<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Task 2.7 Batch 3: confirms that with `APP_DEBUG=false` (the required
 * production setting), an unhandled server error renders Laravel's generic
 * error page — never a stack trace, file path, or raw exception message —
 * regardless of which page/role triggered it. This is what actually protects
 * users if `.env` on the live server is ever misconfigured; the local `.env`
 * in this repo is intentionally `APP_DEBUG=true` for development and cannot
 * be used to prove this by itself (see final report).
 */
class ErrorHandlingTest extends TestCase
{
    public function test_debug_false_hides_stack_trace_and_file_paths_on_a_server_error(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_throw_error', function () {
            throw new \RuntimeException('SELECT * FROM users WHERE password_hash = \'leaked-secret-value\'');
        });

        $response = $this->get('/__test_throw_error');

        $response->assertStatus(500);
        $response->assertDontSee('leaked-secret-value');
        $response->assertDontSee('RuntimeException');
        $response->assertDontSee(__FILE__, false);
        $response->assertDontSee('app/Http/Middleware', false);
    }

    public function test_debug_true_would_leak_details_confirming_the_test_actually_distinguishes_the_two_states(): void
    {
        // Sanity check for the test above: proves assertDontSee would actually
        // catch a real leak if APP_DEBUG were left on, rather than the assertion
        // trivially passing regardless of debug state.
        config(['app.debug' => true]);

        Route::get('/__test_throw_error_debug', function () {
            throw new \RuntimeException('leaked-secret-value-debug-mode');
        });

        $response = $this->get('/__test_throw_error_debug');

        $response->assertSee('leaked-secret-value-debug-mode', false);
    }
}
