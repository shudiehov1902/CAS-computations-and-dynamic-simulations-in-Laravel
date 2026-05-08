<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Animation statistics will be implemented after usage tracking exists.',
        ], 501);
    }

    public function show(string $animation): JsonResponse
    {
        return response()->json([
            'animation' => $animation,
            'message' => 'Animation usage details will be implemented after usage tracking exists.',
        ], 501);
    }
}
