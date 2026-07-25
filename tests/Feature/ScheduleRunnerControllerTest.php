<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleRunnerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runs_the_schedule_with_a_valid_token(): void
    {
        config(['services.cron.secret' => 'test-secret']);

        $this->get(route('cron.run-schedule', ['token' => 'test-secret']))->assertOk();
    }

    public function test_rejects_an_invalid_token(): void
    {
        config(['services.cron.secret' => 'test-secret']);

        $this->get(route('cron.run-schedule', ['token' => 'wrong']))->assertForbidden();
    }

    public function test_rejects_a_missing_token(): void
    {
        config(['services.cron.secret' => 'test-secret']);

        $this->get(route('cron.run-schedule'))->assertForbidden();
    }

    public function test_is_hidden_entirely_when_no_secret_is_configured(): void
    {
        config(['services.cron.secret' => null]);

        $this->get(route('cron.run-schedule', ['token' => 'anything']))->assertNotFound();
    }
}
