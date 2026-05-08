<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationPdfTest extends TestCase
{
    public function test_api_documentation_pdf_requires_api_key(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->get('/api/docs/pdf');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }

    public function test_api_documentation_pdf_with_valid_key_returns_pdf(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->get('/api/docs/pdf');

        $content = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('webte2-api-documentation.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    public function test_openapi_documents_pdf_endpoint_as_application_pdf(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $document = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/openapi')
            ->assertStatus(200)
            ->json();

        $this->assertArrayHasKey('application/pdf', $document['paths']['/api/docs/pdf']['get']['responses']['200']['content']);
        $this->assertArrayNotHasKey('501', $document['paths']['/api/docs/pdf']['get']['responses']);
    }
}
