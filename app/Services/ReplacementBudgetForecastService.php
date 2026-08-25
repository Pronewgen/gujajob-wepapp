<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReplacementBudgetForecastService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int    $timeout,
    ) {}

    /**
     * Call the FastAPI forecast endpoint.
     *
     * @param  array{forecast_years: int, training_records: list<array>, candidate_assets: list<array>}  $payload
     * @return array  Decoded JSON response
     *
     * @throws \RuntimeException  when the service is unreachable
     */
    public function forecast(array $payload): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/forecast/replacement-budget", $payload);

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            Log::error('AI forecast service unreachable', ['url' => $this->baseUrl, 'error' => $e->getMessage()]);
            throw new \RuntimeException('AI service connection failed', 0, $e);
        } catch (Throwable $e) {
            Log::error('AI forecast service error', ['error' => $e->getMessage()]);
            throw new \RuntimeException('AI service error', 0, $e);
        }
    }

    /**
     * Quick health check — returns true when the service responds OK.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->ok();
        } catch (Throwable) {
            return false;
        }
    }
}
