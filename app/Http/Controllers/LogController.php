<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'CAS logs will be implemented after the database tables are created.',
        ], 501);
    }

    public function export(): JsonResponse
    {
        return response()->json([
            'message' => 'CSV export will be implemented after CAS logging exists.',
        ], 501);
    }
}
