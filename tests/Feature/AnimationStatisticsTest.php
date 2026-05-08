<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAnonymousToken;
use App\Models\AnimationUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class AnimationStatisticsTest extends TestCase
{
    use CreatesFakeOctave;
    use RefreshDatabase;

    public function test_successful_pendulum_simulation_creates_usage_record(): void
    {
        $userToken = (string) Str::uuid();

        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('pendulum'),
        ]);

        $response = $this->withCredentials()
            ->withCookie(EnsureAnonymousToken::COOKIE_NAME, $userToken)
            ->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 0.2,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('animation_usages', [
            'animation_type' => 'pendulum',
            'user_token' => $userToken,
            'city' => 'Unknown',
            'country' => 'Unknown',
        ]);
    }

    public function test_successful_ball_beam_simulation_creates_usage_record(): void
    {
        $userToken = (string) Str::uuid();

        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('ball-beam'),
        ]);

        $response = $this->withCredentials()
            ->withCookie(EnsureAnonymousToken::COOKIE_NAME, $userToken)
            ->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/ball-beam', [
                'reference' => 0.25,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('animation_usages', [
            'animation_type' => 'ball_beam',
            'user_token' => $userToken,
            'city' => 'Unknown',
            'country' => 'Unknown',
        ]);
    }

    public function test_repeated_same_animation_by_same_user_inside_interval_is_not_counted_twice(): void
    {
        $userToken = (string) Str::uuid();

        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('pendulum'),
            'cas.statistics_interval_minutes' => 10,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $this->withCredentials()
                ->withCookie(EnsureAnonymousToken::COOKIE_NAME, $userToken)
                ->withHeader('X-CAS-API-Key', 'test-api-key')
                ->postJson('/api/simulations/pendulum', [
                    'reference' => 0.2,
                ])
                ->assertStatus(200);
        }

        $this->assertSame(1, AnimationUsage::query()
            ->where('animation_type', 'pendulum')
            ->where('user_token', $userToken)
            ->count());
    }

    public function test_same_user_after_interval_is_counted_again(): void
    {
        $userToken = (string) Str::uuid();

        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('pendulum'),
            'cas.statistics_interval_minutes' => 10,
        ]);

        $this->createUsage([
            'animation_type' => 'pendulum',
            'user_token' => $userToken,
            'used_at' => now()->subMinutes(11),
        ]);

        $this->withCredentials()
            ->withCookie(EnsureAnonymousToken::COOKIE_NAME, $userToken)
            ->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 0.2,
            ])
            ->assertStatus(200);

        $this->assertSame(2, AnimationUsage::query()
            ->where('animation_type', 'pendulum')
            ->where('user_token', $userToken)
            ->count());
    }

    public function test_failed_or_invalid_simulation_does_not_create_usage_record(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('fail'),
        ]);

        $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 99,
            ])
            ->assertStatus(422);

        $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 0.2,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('animation_usages', 0);
    }

    public function test_api_statistics_without_key_returns_unauthorized(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->getJson('/api/statistics');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }

    public function test_api_statistics_with_key_returns_counts(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $this->createUsage(['animation_type' => 'pendulum']);
        $this->createUsage(['animation_type' => 'pendulum', 'user_token' => (string) Str::uuid()]);
        $this->createUsage(['animation_type' => 'ball_beam']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.animation_type', 'pendulum')
            ->assertJsonPath('data.0.label', 'Inverted Pendulum')
            ->assertJsonPath('data.0.count', 2)
            ->assertJsonPath('data.1.animation_type', 'ball_beam')
            ->assertJsonPath('data.1.label', 'Ball and Beam')
            ->assertJsonPath('data.1.count', 1)
            ->assertJsonPath('interval_minutes', 10);
    }

    public function test_api_statistics_details_returns_paginated_unknown_location_data(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $usage = $this->createUsage([
            'animation_type' => 'ball_beam',
            'city' => 'Unknown',
            'country' => 'Unknown',
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/statistics/ball-beam');

        $response
            ->assertStatus(200)
            ->assertJsonPath('animation_type', 'ball_beam')
            ->assertJsonPath('label', 'Ball and Beam')
            ->assertJsonPath('data.0.id', $usage->id)
            ->assertJsonPath('data.0.city', 'Unknown')
            ->assertJsonPath('data.0.country', 'Unknown')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_invalid_statistics_animation_returns_not_found(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/statistics/unknown');

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'Animation statistics not found.',
            ]);
    }

    public function test_web_statistics_page_renders_cards_and_selected_details_without_api_key(): void
    {
        $this->createUsage([
            'animation_type' => 'pendulum',
            'user_token' => 'test-user-token',
            'city' => 'Unknown',
            'country' => 'Unknown',
        ]);

        $response = $this->get('/statistics?animation=pendulum');

        $response
            ->assertStatus(200)
            ->assertSee('Animation Statistics')
            ->assertSee('Inverted Pendulum')
            ->assertSee('Ball and Beam')
            ->assertSee('test-user-token')
            ->assertSee('Unknown');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUsage(array $attributes = []): AnimationUsage
    {
        return AnimationUsage::create(array_merge([
            'animation_type' => 'pendulum',
            'user_token' => (string) Str::uuid(),
            'ip_address' => '127.0.0.1',
            'city' => 'Unknown',
            'country' => 'Unknown',
            'used_at' => now(),
        ], $attributes));
    }
}
