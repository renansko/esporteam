<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'target_snapshot' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
