<?php

namespace Tests\Unit;

use App\Services\DocumentationPdfService;
use App\Services\OpenApiService;
use Tests\TestCase;

class DocumentationPdfServiceTest extends TestCase
{
    public function test_grouped_endpoint_data_contains_required_api_endpoints(): void
    {
        $groups = app(DocumentationPdfService::class)->groupEndpoints(app(OpenApiService::class)->document());
        $paths = collect($groups)
            ->flatMap(fn (array $group): array => collect($group['endpoints'])
                ->map(fn (array $endpoint): string => $endpoint['path'])
                ->all())
            ->all();

        foreach ([
            '/api/cas/execute',
            '/api/simulations/pendulum',
            '/api/simulations/ball-beam',
            '/api/logs',
            '/api/logs/export',
            '/api/statistics',
            '/api/statistics/{animation}',
            '/api/openapi',
            '/api/docs/pdf',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }

    public function test_view_data_contains_pdf_metadata_and_examples(): void
    {
        $viewData = app(DocumentationPdfService::class)->buildViewData(app(OpenApiService::class)->document());

        $this->assertSame('WEBTE2 CAS Simulations API', $viewData['info']['title']);
        $this->assertArrayHasKey('ApiKeyAuth', $viewData['securitySchemes']);
        $this->assertNotEmpty($viewData['generatedAt']);
        $this->assertNotEmpty($viewData['groups']);

        $casEndpoint = collect($viewData['groups'])
            ->flatMap(fn (array $group): array => $group['endpoints'])
            ->firstWhere('path', '/api/cas/execute');

        $this->assertSame('POST', $casEndpoint['method']);
        $this->assertTrue($casEndpoint['requiresAuth']);
        $this->assertNotEmpty($casEndpoint['requestExamples']);
        $this->assertNotEmpty($casEndpoint['responseExamples']);
    }
}
