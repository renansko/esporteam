<?php

namespace Tests\Feature;

use Database\Seeders\CommonSportsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('sports')
            ->whereIn('slug', CommonSportsSeeder::slugs())
            ->delete();
    }
}
