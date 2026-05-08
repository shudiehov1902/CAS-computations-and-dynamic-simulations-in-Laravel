<?php

namespace App\Http\Controllers;

use App\Services\DocumentationPdfService;
use App\Services\OpenApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    public function pdf(OpenApiService $openApiService, DocumentationPdfService $documentationPdfService): Response
    {
        $pdf = Pdf::loadView('pdf.api-documentation', $documentationPdfService->buildViewData($openApiService->document()))
            ->setPaper('a4');

        return $pdf->download('webte2-api-documentation.pdf');
    }
}
