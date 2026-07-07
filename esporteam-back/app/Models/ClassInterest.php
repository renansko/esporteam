<?php

namespace App\Models;

use App\Enums\ClassInterestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassInterest extends Pivot
{
    protected $table = 'class_interests';

    protected $fillable = [
        'class_offering_id',
        'sport_profile_id',
        'status',
    ];

    protected $casts = [
        'status' => ClassInterestStatus::class,
    ];

    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
