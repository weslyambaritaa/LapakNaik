<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Stands in for a system cron calling `php artisan schedule:run` every
 * minute — hosts without a free native cron (e.g. Render's free tier) hit
 * this from an external scheduler instead. Whatever's actually due (see
 * routes/console.php) runs; Laravel's own scheduler handles the timing, so
 * this endpoint doesn't need to know what commands exist.
 */
class ScheduleRunnerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.cron.secret');

        abort_if($secret === '', 404);
        abort_unless(hash_equals($secret, (string) $request->query('token')), 403);

        Artisan::call('schedule:run');

        return response(Artisan::output());
    }
}
