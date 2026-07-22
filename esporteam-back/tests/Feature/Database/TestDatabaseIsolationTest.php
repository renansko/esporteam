<?php

use Illuminate\Support\Facades\DB;

it('runs feature tests only against the isolated in-memory database', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:');
});
