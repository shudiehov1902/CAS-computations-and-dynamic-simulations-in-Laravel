<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SimulationController extends Controller
{
    public function pendulum(): JsonResponse
    {
        return response()->json([
            'message' => 'Inverted pendulum simulation will be implemented in its backend step.',
        ], 501);
    }

    public function ballBeam(): JsonResponse
    {
        return response()->json([
            'message' => 'Ball and beam simulation will be implemented in its backend step.',
        ], 501);
    }
}
