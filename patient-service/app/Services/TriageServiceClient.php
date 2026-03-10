<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriageServiceClient
{
    protected $baseUrl;
    protected $timeout;
    protected $serviceToken;

    public function __construct()
    {
        $this->baseUrl = config('services.triage.url', 'http://localhost:8005');
        $this->timeout = config('services.triage.timeout', 30);
        $this->serviceToken = config('services.triage.token');
    }

    /**
     * Create a triage in the triage-service
     */
    public function criarTriagem(array $data)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/triagens", $data);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Failed to create triage in triage-service', [
                'status' => $response->status(),
                'response' => $response->body(),
                'data' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error calling triage-service to create triage', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return null;
        }
    }

    /**
     * Update triage status in triage-service
     */
    public function atualizarStatusTriagem($triagemId, $status)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->patch("{$this->baseUrl}/api/triagens/{$triagemId}/status", [
                    'status' => $status
                ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Failed to update triage status in triage-service', [
                'status' => $response->status(),
                'response' => $response->body(),
                'triagem_id' => $triagemId
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error calling triage-service to update status', [
                'error' => $e->getMessage(),
                'triagem_id' => $triagemId
            ]);

            return null;
        }
    }

    /**
     * Get triage details from triage-service
     */
    public function obterTriagem($triagemId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/triagens/{$triagemId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error calling triage-service to get triage', [
                'error' => $e->getMessage(),
                'triagem_id' => $triagemId
            ]);

            return null;
        }
    }

    /**
     * Cancel triage in triage-service
     */
    public function cancelarTriagem($triagemId, $motivo)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/triagens/{$triagemId}/cancelar", [
                    'motivo' => $motivo
                ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error calling triage-service to cancel triage', [
                'error' => $e->getMessage(),
                'triagem_id' => $triagemId
            ]);

            return null;
        }
    }
}
