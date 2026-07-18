<?php

namespace App\Jobs;

use App\Models\SportSessionSeries;
use App\Services\SportSessionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MaterializeSportSessionSeries implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $seriesId) {}

    public function handle(SportSessionService $sessions): void
    {
        $series = SportSessionSeries::query()->find($this->seriesId);
        if ($series !== null) {
            $sessions->materializeSeries($series);
        }
    }
}
