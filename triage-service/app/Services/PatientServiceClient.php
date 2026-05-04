<?php
// filepath: services/triage-service/app/Services/PatientServiceClient.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientServiceClient
{
    protected $baseUrl;
    protected $serviceToken;
    
    public function __construct()
    {
        $this->baseUrl = config('services.patient.url');
        $this->serviceToken = config('services.service_token');
    }
    
    /**
     * Update solicitacao triagem status
     */
    public function atualizarStatusSolicitacaoTriagem($solicitacaoId, $status, $triagemId = null)
    {
        try {
            $response = Http::withHeaders(['X-Service-Token' => $this->serviceToken])
                ->timeout(10)
                ->patch("{$this->baseUrl}/api/services/solicitacoes-triagem/{$solicitacaoId}/status", [
                    'status' => $status,
                    'triagem_id' => $triagemId
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error updating triage request status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update patient status
     */
    public function atualizarStatusPaciente($pacienteId, $status)
    {
        try {
            $response = Http::withToken($this->serviceToken)
                ->timeout(10)
                ->patch("{$this->baseUrl}/api/services/pacientes/{$pacienteId}/status", [
                    'status' => $status
                ]);
            
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error("Error updating patient status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch pending solicitacoes from patient-service
     * Returns array|null
     */
    public function getPendentes()
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/solicitacoes-triagem/pendentes");

            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json) && array_key_exists('data', $json)) {
                    return $json['data'];
                }
                return $json;
            }

            Log::warning('Patient service returned non-success for pendentes', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching pendentes from patient-service: ' . $e->getMessage());
        }

        return null;
    }
}