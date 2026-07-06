<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherStudentRequest;
use App\Http\Requests\UpsertTeacherProfileRequest;
use App\Http\Resources\SportProfileResource;
use App\Http\Resources\TeacherProfileResource;
use App\Models\SportProfile;
use App\Services\TeacherProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherProfileController extends Controller
{
    public function __construct(
        private readonly TeacherProfileService $teachers,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $teacher = $this->teachers->findForUser((int) $request->user()->id);

        if (! $teacher) {
            return $this->successResponse(null, 'Teacher profile not created.');
        }

        return $this->successResponse(new TeacherProfileResource($teacher));
    }

    public function upsert(UpsertTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $this->teachers->upsertForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->successResponse(new TeacherProfileResource($teacher), 'Teacher profile saved.');
    }

    public function students(Request $request): JsonResponse
    {
        $teacher = $this->teachers->findForUser((int) $request->user()->id);

        if (! $teacher) {
            return $this->successResponse([], 'Teacher profile not created.');
        }

        return $this->successResponse(SportProfileResource::collection($teacher->students), 'Teacher students listed.');
    }

    public function addStudent(StoreTeacherStudentRequest $request): JsonResponse
    {
        $teacher = $this->teachers->addStudent(
            (int) $request->user()->id,
            (int) $request->validated('student_profile_id'),
            $request->validated('status') ?? 'active',
        );

        return $this->createdResponse(new TeacherProfileResource($teacher), 'Student added.');
    }

    public function removeStudent(Request $request, SportProfile $studentProfile): JsonResponse
    {
        $this->teachers->endStudent((int) $request->user()->id, $studentProfile);

        return $this->deletedResponse();
    }
}
