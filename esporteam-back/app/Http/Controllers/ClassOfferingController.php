<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexClassOfferingRequest;
use App\Http\Requests\StoreClassOfferingRequest;
use App\Http\Resources\ClassOfferingResource;
use App\Models\ClassOffering;
use App\Services\ClassOfferingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassOfferingController extends Controller
{
    public function __construct(
        private readonly ClassOfferingService $classes,
    ) {}

    public function index(IndexClassOfferingRequest $request): JsonResponse
    {
        return $this->successResponse(
            ClassOfferingResource::collection($this->classes->openClassesForUser((int) $request->user()->id, $request->validated())),
            'Classes listed.',
        );
    }

    public function store(StoreClassOfferingRequest $request): JsonResponse
    {
        $class = $this->classes->createForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->createdResponse(new ClassOfferingResource($class), 'Class created.');
    }

    public function interest(Request $request, ClassOffering $classOffering): JsonResponse
    {
        $class = $this->classes->registerInterest((int) $request->user()->id, $classOffering);

        return $this->createdResponse(new ClassOfferingResource($class), 'Class interest registered.');
    }
}
