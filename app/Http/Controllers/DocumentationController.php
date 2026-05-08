<?php

namespace App\Http\Controllers;

use App\Services\OpenApiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class DocumentationController extends Controller
{
    public function page(): View
    {
        return view('pages.api-docs');
    }

    public function openApi(OpenApiService $openApiService): JsonResponse
    {
        return response()->json($openApiService->document());
    }

    public function publicOpenApi(OpenApiService $openApiService): JsonResponse
    {
        return response()->json($openApiService->document());
    }

    public function pdf(): JsonResponse
    {
        return response()->json([
            'message' => 'Dynamic PDF documentation will be implemented in the PDF step.',
        ], 501);
    }
}
