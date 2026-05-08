<?php

namespace App\Http\Controllers;

use App\Models\CasLog;
use App\Services\OctaveExecutionException;
use App\Services\OctaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CasController extends Controller
{
    public function execute(Request $request, OctaveService $octaveService): JsonResponse
    {
        $payload = $request->all();
        $userToken = (string) $request->attributes->get('user_token', 'anonymous');

        $validator = Validator::make($payload, [
            'command' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first('command') ?: 'Invalid CAS command.';

            $this->logExecution($request, $payload, 'error', '', $error, $userToken);

            return $this->errorResponse($error, 422);
        }

        $command = trim((string) $payload['command']);

        if ($command === '') {
            $error = 'The command field must not be empty.';

            $this->logExecution($request, $payload, 'error', '', $error, $userToken);

            return $this->errorResponse($error, 422);
        }

        try {
            $result = $octaveService->execute($command, $userToken);
            $output = $result['output'];

            $this->logExecution($request, $payload, 'success', $output, null, $userToken);

            return response()->json([
                'success' => true,
                'output' => $output,
                'error' => null,
            ]);
        } catch (OctaveExecutionException $exception) {
            $this->logExecution(
                $request,
                $payload,
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
            $error = 'Unexpected CAS execution error.';

            $this->logExecution($request, $payload, 'error', '', $exception->getMessage(), $userToken);

            return $this->errorResponse($error, 500);
        }
    }

    private function logExecution(
        Request $request,
        array $payload,
        string $status,
        ?string $output,
        ?string $errorMessage,
        string $userToken
    ): void {
        CasLog::create([
            'command' => 'cas.execute',
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
