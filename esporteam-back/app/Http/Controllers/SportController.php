<?php

namespace App\Http\Controllers;

use App\Http\Resources\SportResource;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;

class SportController extends Controller
{
    public function index(): JsonResponse
    {
        $sports = Sport::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->successResponse(SportResource::collection($sports));
    }
}
