<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::updateOrCreate(
            ['slug' => 'mesa'],
            [
                'id'        => 1,
                'name'      => 'Mesa',
                'owner_id'  => 1,
                'is_active' => true,
            ],
        );

        WorkspaceMember::updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => 1],
            ['role' => 'owner'],
        );

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('workspaces', 'id'), COALESCE((SELECT MAX(id) FROM workspaces), 1))");
        }
    }
}
