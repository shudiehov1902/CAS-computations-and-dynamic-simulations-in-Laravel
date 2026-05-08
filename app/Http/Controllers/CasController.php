<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class CasController extends Controller
{
    public function execute(): JsonResponse
    {
        return response()->json([
            'message' => 'CAS execution will be implemented in the Octave service step.',
        ], 501);
    }
}
