<?php

namespace App\Services;

use App\Enums\ClassInterestStatus;
use App\Enums\ClassOfferingStatus;
use App\Models\ClassOffering;
use App\Models\SportProfile;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassOfferingService
{
    public function createForUser(int $userId, array $data): ClassOffering
    {
        $teacher = $this->requireTeacherForUser($userId);

        $class = ClassOffering::query()->create([
            'teacher_profile_id' => $teacher->id,
            'sport_id' => $data['sport_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_cents' => $data['price_cents'] ?? null,
            'starts_at' => $data['starts_at'],
            'recurrence' => $data['recurrence'] ?? null,
            'location_label' => $data['location_label'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'latitude_approx' => $data['latitude_approx'] ?? null,
            'longitude_approx' => $data['longitude_approx'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'status' => $data['status'] ?? ClassOfferingStatus::Open->value,
        ]);

        return $this->freshClass($class);
    }

    public function openClassesForUser(int $userId, array $filters = []): Collection
    {
        $profile = SportProfile::query()
            ->where('user_id', $userId)
            ->first();

        return ClassOffering::query()
            ->with(['teacher.profile', 'sport'])
            ->withCount(['interests as interest_count' => fn (Builder $query) => $query->where('status', ClassInterestStatus::Interested->value)])
            ->where('status', ClassOfferingStatus::Open->value)
            ->when(isset($filters['sport_id']), fn (Builder $query) => $query->where('sport_id', (int) $filters['sport_id']))
            ->when(isset($filters['sport_slug']), fn (Builder $query) => $query->whereHas('sport', fn (Builder $query) => $query->where('slug', $filters['sport_slug'])))
            ->when(isset($filters['city']), fn (Builder $query) => $query->where('city', $filters['city']))
            ->when(isset($filters['region']), fn (Builder $query) => $query->where('region', $filters['region']))
            ->when(isset($filters['min_price_cents']), fn (Builder $query) => $query->where('price_cents', '>=', (int) $filters['min_price_cents']))
            ->when(isset($filters['max_price_cents']), fn (Builder $query) => $query->where('price_cents', '<=', (int) $filters['max_price_cents']))
            ->when(isset($filters['starts_after']), fn (Builder $query) => $query->where('starts_at', '>=', $filters['starts_after']))
            ->when(isset($filters['starts_before']), fn (Builder $query) => $query->where('starts_at', '<=', $filters['starts_before']))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (ClassOffering $class) => $this->withDistance($class, $profile))
            ->filter(fn (ClassOffering $class) => $this->passesDistanceFilter($class, $filters))
            ->take(50)
            ->values();
    }

    public function registerInterest(int $userId, ClassOffering $class): ClassOffering
    {
        $profile = $this->requireProfile($userId);

        return DB::transaction(function () use ($profile, $class) {
            $lockedClass = ClassOffering::query()
                ->whereKey($class->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedClass->status !== ClassOfferingStatus::Open) {
                throw ValidationException::withMessages([
                    'class' => 'Class is not open for interest.',
                ]);
            }

            if ($lockedClass->teacher()->where('sport_profile_id', $profile->id)->exists()) {
                throw ValidationException::withMessages([
                    'profile' => 'Teacher cannot register interest in their own class.',
                ]);
            }

            $interestCount = DB::table('class_interests')
                ->where('class_offering_id', $lockedClass->id)
                ->where('status', ClassInterestStatus::Interested->value)
                ->count();

            if ($lockedClass->capacity !== null && $interestCount >= $lockedClass->capacity) {
                throw ValidationException::withMessages([
                    'capacity' => 'Class capacity is full.',
                ]);
            }

            $existing = DB::table('class_interests')
                ->where('class_offering_id', $lockedClass->id)
                ->where('sport_profile_id', $profile->id)
                ->first();

            if ($existing?->status === ClassInterestStatus::Interested->value) {
                throw ValidationException::withMessages([
                    'profile' => 'Sport profile already registered interest in this class.',
                ]);
            }

            if ($existing) {
                DB::table('class_interests')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => ClassInterestStatus::Interested->value,
                        'updated_at' => now(),
                    ]);
            } else {
                $lockedClass->interestedProfiles()->attach($profile->id, [
                    'status' => ClassInterestStatus::Interested->value,
                ]);
            }

            return $this->freshClass($lockedClass);
        });
    }

    private function freshClass(ClassOffering $class): ClassOffering
    {
        return $class
            ->fresh()
            ->load(['teacher.profile', 'sport', 'interestedProfiles'])
            ->loadCount(['interests as interest_count' => fn (Builder $query) => $query->where('status', ClassInterestStatus::Interested->value)]);
    }

    private function withDistance(ClassOffering $class, ?SportProfile $profile): ClassOffering
    {
        $class->setAttribute('distance_km', $this->distanceKm($profile, $class));

        return $class;
    }

    private function passesDistanceFilter(ClassOffering $class, array $filters): bool
    {
        if (! isset($filters['distance_km'])) {
            return true;
        }

        return $class->distance_km !== null && $class->distance_km <= (float) $filters['distance_km'];
    }

    private function distanceKm(?SportProfile $profile, ClassOffering $class): ?float
    {
        if (
            $profile?->latitude_approx === null
            || $profile->longitude_approx === null
            || $class->latitude_approx === null
            || $class->longitude_approx === null
        ) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($class->latitude_approx - $profile->latitude_approx);
        $longitudeDelta = deg2rad($class->longitude_approx - $profile->longitude_approx);
        $profileLatitude = deg2rad($profile->latitude_approx);
        $classLatitude = deg2rad($class->latitude_approx);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($profileLatitude) * cos($classLatitude) * sin($longitudeDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine)), 1);
    }

    private function requireTeacherForUser(int $userId): TeacherProfile
    {
        return TeacherProfile::query()
            ->whereHas('profile', fn (Builder $query) => $query->where('user_id', $userId))
            ->firstOrFail();
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
