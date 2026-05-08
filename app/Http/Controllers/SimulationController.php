<?php

namespace App\Http\Controllers;

use App\Models\CasLog;
use App\Services\BallBeamService;
use App\Services\OctaveExecutionException;
use App\Services\PendulumService;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SimulationController extends Controller
{
    public function pendulum(
        Request $request,
        PendulumService $pendulumService,
        StatisticsService $statisticsService
    ): JsonResponse {
        $payload = $request->all();
        $userToken = (string) $request->attributes->get('user_token', 'anonymous');

        $validator = Validator::make($payload, [
            'reference' => ['sometimes', 'numeric', 'min:-5', 'max:5'],
            'initial_position' => ['sometimes', 'numeric', 'min:-5', 'max:5'],
            'initial_angle' => ['sometimes', 'numeric', 'min:-1.57', 'max:1.57'],
            'time_step' => ['sometimes', 'numeric', 'min:0.001', 'max:1'],
            'duration' => ['sometimes', 'numeric', 'min:0.1', 'max:60'],
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first() ?: 'Invalid pendulum simulation parameters.';

            $this->logSimulation($request, 'simulation.pendulum', $payload, 'error', '', $error, $userToken);

            return $this->errorResponse($error, 422);
        }

        $parameters = [
            'reference' => (float) ($payload['reference'] ?? 0.2),
            'initial_position' => (float) ($payload['initial_position'] ?? 0),
            'initial_angle' => (float) ($payload['initial_angle'] ?? 0),
            'time_step' => (float) ($payload['time_step'] ?? 0.05),
            'duration' => (float) ($payload['duration'] ?? 10),
        ];

        try {
            $result = $pendulumService->simulate($parameters);
            $output = json_encode($result, JSON_THROW_ON_ERROR);

            $this->logSimulation($request, 'simulation.pendulum', $parameters, 'success', $output, null, $userToken);
            $statisticsService->recordUsage(StatisticsService::TYPE_PENDULUM, $userToken, $request->ip());

            return response()->json($result);
        } catch (OctaveExecutionException $exception) {
            $this->logSimulation(
                $request,
                'simulation.pendulum',
                $parameters,
                'error',
                $exception->output(),
                $exception->getMessage(),
                $userToken
            );

            return $this->errorResponse(
                $exception->getMessage(),
                $exception->httpStatus(),
                $exception->output()
            );
        } catch (Throwable $exception) {
            $error = 'Unexpected pendulum simulation error.';

            $this->logSimulation($request, 'simulation.pendulum', $parameters, 'error', '', $exception->getMessage(), $userToken);

            return $this->errorResponse($error, 500);
        }
    }

    public function ballBeam(
        Request $request,
        BallBeamService $ballBeamService,
        StatisticsService $statisticsService
    ): JsonResponse {
        $payload = $request->all();
        $userToken = (string) $request->attributes->get('user_token', 'anonymous');

        $validator = Validator::make($payload, [
            'reference' => ['sometimes', 'numeric', 'min:-2', 'max:2'],
            'initial_velocity' => ['sometimes', 'numeric', 'min:-5', 'max:5'],
            'initial_acceleration' => ['sometimes', 'numeric', 'min:-5', 'max:5'],
            'time_step' => ['sometimes', 'numeric', 'min:0.001', 'max:1'],
            'duration' => ['sometimes', 'numeric', 'min:0.1', 'max:60'],
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first() ?: 'Invalid ball and beam simulation parameters.';

            $this->logSimulation($request, 'simulation.ball_beam', $payload, 'error', '', $error, $userToken);

            return $this->errorResponse($error, 422);
        }

        $parameters = [
            'reference' => (float) ($payload['reference'] ?? 0.25),
            'initial_velocity' => (float) ($payload['initial_velocity'] ?? 0),
            'initial_acceleration' => (float) ($payload['initial_acceleration'] ?? 0),
            'time_step' => (float) ($payload['time_step'] ?? 0.01),
            'duration' => (float) ($payload['duration'] ?? 5),
        ];

        try {
            $result = $ballBeamService->simulate($parameters);
            $output = json_encode($result, JSON_THROW_ON_ERROR);

            $this->logSimulation($request, 'simulation.ball_beam', $parameters, 'success', $output, null, $userToken);
            $statisticsService->recordUsage(StatisticsService::TYPE_BALL_BEAM, $userToken, $request->ip());

            return response()->json($result);
        } catch (OctaveExecutionException $exception) {
            $this->logSimulation(
                $request,
                'simulation.ball_beam',
                $parameters,
                'error',
                $exception->output(),
                $exception->getMessage(),
                $userToken
            );

            return $this->errorResponse(
                $exception->getMessage(),
                $exception->httpStatus(),
                $exception->output()
            );
        } catch (Throwable $exception) {
            $error = 'Unexpected ball and beam simulation error.';

            $this->logSimulation($request, 'simulation.ball_beam', $parameters, 'error', '', $exception->getMessage(), $userToken);

            return $this->errorResponse($error, 500);
        }
    }

    private function logSimulation(
        Request $request,
        string $command,
        array $payload,
        string $status,
        ?string $output,
        ?string $errorMessage,
        string $userToken
    ): void {
        CasLog::create([
            'command' => $command,
            'request_payload' => $payload,
            'status' => $status,
            'output' => $output,
            'error_message' => $errorMessage,
            'ip_address' => $request->ip(),
            'user_token' => $userToken,
        ]);
    }

    private function errorResponse(string $error, int $status, string $output = ''): JsonResponse
    {
        return response()->json([
            'success' => false,
            'output' => $output,
            'error' => $error,
        ], $status);
    }
}
