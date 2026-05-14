<?php

namespace App\Http\Controllers;

use App\Models\CasLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    public function page(Request $request): View
    {
        $logs = $this->userQuery($request)
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('pages.logs', [
            'logs' => $logs,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $logs = $this->baseQuery()->paginate($this->perPage($request));

        return response()->json([
            'data' => $logs->getCollection()
                ->map(fn (CasLog $log): array => $this->formatLog($log))
                ->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    public function export(): Response
    {
        return $this->csvResponse();
    }

    public function webExport(Request $request): Response
    {
        return $this->csvResponse($this->userQuery($request));
    }

    private function baseQuery(): Builder
    {
        return CasLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function userQuery(Request $request): Builder
    {
        return $this->baseQuery()
            ->where('user_token', (string) $request->attributes->get('user_token', ''));
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        if ($perPage < 1) {
            return 20;
        }

        return min($perPage, 100);
    }

    /**
     * @return array{id: int, created_at: string|null, command: string, status: string, request_payload: mixed, output: string|null, error_message: string|null, ip_address: string|null, user_token: string|null}
     */
    private function formatLog(CasLog $log): array
    {
        return [
            'id' => $log->id,
            'created_at' => $log->created_at?->toIso8601String(),
            'command' => $log->command,
            'status' => $log->status,
            'request_payload' => $log->request_payload,
            'output' => $log->output,
            'error_message' => $log->error_message,
            'ip_address' => $log->ip_address,
            'user_token' => $log->user_token,
        ];
    }

    private function csvResponse(?Builder $query = null): Response
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'id',
            'created_at',
            'command',
            'status',
            'user_token',
            'ip_address',
            'request_payload',
            'output',
            'error_message',
        ]);

        foreach (($query ?? $this->baseQuery())->cursor() as $log) {
            fputcsv($handle, [
                $log->id,
                $log->created_at?->toDateTimeString() ?? '',
                $log->command,
                $log->status,
                $log->user_token ?? '',
                $log->ip_address ?? '',
                $this->jsonForCsv($log->request_payload),
                $log->output ?? '',
                $log->error_message ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=cas_logs.csv',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function jsonForCsv(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
