<?php

use App\Console\Commands\ClusteringWatchdogCommand;
use App\Jobs\MaterializeSportSessionSeries;
use App\Models\SportSessionSeries;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ClusteringWatchdogCommand::class)->everyMinute();

Schedule::call(function (): void {
    if (! config('features.recurring_events', false)) {
        return;
    }
    SportSessionSeries::query()->where('status', 'active')->pluck('id')->each(
        fn (int $id) => MaterializeSportSessionSeries::dispatch($id),
    );
})->hourly();
