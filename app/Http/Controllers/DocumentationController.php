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

        $pdf->render();
        $this->addPageNumbers($pdf->getDomPDF());

        return response($pdf->output(), 200, [
            'Content-Disposition' => 'attachment; filename="webte2-api-documentation.pdf"',
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function addPageNumbers(\Dompdf\Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $fontSize = 9;
        $text = 'Page {PAGE_NUM} / {PAGE_COUNT}';
        $sampleText = 'Page 99 / 99';

        $x = ($canvas->get_width() - $fontMetrics->getTextWidth($sampleText, $font, $fontSize)) / 2;
        $y = $canvas->get_height() - 31;

        $canvas->page_text($x, $y, $text, $font, $fontSize, [0.44, 0.44, 0.48]);
    }
}
