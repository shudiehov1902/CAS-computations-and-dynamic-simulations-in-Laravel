<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_protected_openapi_requires_api_key(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->getJson('/api/openapi');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }

    public function test_protected_openapi_with_valid_key_returns_document(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/openapi');

        $response
            ->assertStatus(200)
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', 'WEBTE2 CAS Simulations API')
            ->assertJsonPath('components.securitySchemes.ApiKeyAuth.name', 'X-CAS-API-Key');
    }

    public function test_public_openapi_json_is_available_without_api_key(): void
    {
        $response = $this->getJson('/openapi.json');

        $response
            ->assertStatus(200)
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('paths./api/cas/execute.post.summary', 'Execute a GNU Octave CAS command');
    }

    public function test_openapi_contains_all_required_paths_and_schemas(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $document = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/openapi')
            ->assertStatus(200)
            ->json();

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
            $this->assertArrayHasKey($path, $document['paths']);
        }

        $this->assertArrayHasKey('command', $document['components']['schemas']['CasExecuteRequest']['properties']);
        $this->assertArrayHasKey('reference', $document['components']['schemas']['PendulumRequest']['properties']);
        $this->assertArrayHasKey('initial_position', $document['components']['schemas']['PendulumRequest']['properties']);
        $this->assertArrayHasKey('reference', $document['components']['schemas']['BallBeamRequest']['properties']);
        $this->assertArrayHasKey('initial_velocity', $document['components']['schemas']['BallBeamRequest']['properties']);
        $this->assertArrayHasKey('text/csv', $document['paths']['/api/logs/export']['get']['responses']['200']['content']);
        $this->assertArrayHasKey('application/pdf', $document['paths']['/api/docs/pdf']['get']['responses']['200']['content']);
    }

    public function test_api_docs_page_renders_swagger_ui_container_without_api_key(): void
    {
        $response = $this->get('/api-docs');

        $response
            ->assertStatus(200)
            ->assertSee('API Documentation')
            ->assertSee('id="swagger-ui"', false);
    }
}
