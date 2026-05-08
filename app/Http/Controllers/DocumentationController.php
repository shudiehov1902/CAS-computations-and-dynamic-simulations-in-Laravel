<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class DocumentationController extends Controller
{
    public function openApi(): JsonResponse
    {
        return response()->json([
            'message' => 'OpenAPI documentation will be implemented in the documentation step.',
        ], 501);
    }

    public function pdf(): JsonResponse
    {
        return response()->json([
            'message' => 'Dynamic PDF documentation will be implemented in the PDF step.',
        ], 501);
    }
}
