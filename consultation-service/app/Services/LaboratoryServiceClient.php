<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LaboratoryServiceClient
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.laboratory.url');
    }

    /**
     * Solicita exames laboratoriais
     */
    public function solicitarExames($consultaId, $pacienteId, $exames, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->post("{$this->baseUrl}/api/exames/solicitar", [
                    'consulta_id' => $consultaId,
                    'paciente_id' => $pacienteId,
                    'exames' => $exames,
                    'data_solicitacao' => now()->toDateTimeString()
                ]);

            if ($response->successful()) {
                Log::info('Exames solicitados com sucesso', [
                    'consulta_id' => $consultaId,
                    'paciente_id' => $pacienteId
                ]);
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao solicitar exames', [
                'consulta_id' => $consultaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca resultados de exames
     */
    public function getResultadosExames($consultaId, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->get("{$this->baseUrl}/api/exames/consulta/{$consultaId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar resultados de exames', [
                'consulta_id' => $consultaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function getHeaders($token = null)
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
