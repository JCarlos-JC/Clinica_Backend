<?php
// filepath: services/triage-service/app/Services/ConsultationServiceClient.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConsultationServiceClient
{
    protected $baseUrl;
    protected $serviceToken;
    
    public function __construct()
    {
        $this->baseUrl = config('services.consultation.url');
        $this->serviceToken = config('services.service_token');
    }
    
    /**
     * Receber agendamento e criar consulta no consultation-service
     * Segue padrão do PatientServiceClient (X-Service-Token header)
     */
    public function criarConsulta($agendamentoData)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post("{$this->baseUrl}/api/consultas/receber-agendamento", $agendamentoData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                $consultaId = $responseData['data']['id'] ?? $responseData['id'] ?? null;
                
                Log::info('Consulta criada no consultation-service', [
                    'agendamento_id' => $agendamentoData['agendamento_id'] ?? null,
                    'consulta_id' => $consultaId
                ]);
                
                return [
                    'success' => true,
                    'consulta_id' => $consultaId,
                    'data' => $responseData
                ];
            }
            
            // Se retornar 409 (Conflict), a consulta já existe - extrair consulta_id
            if ($response->status() === 409) {
                $responseData = $response->json();
                $consultaId = $responseData['consulta']['id'] ?? null;
                
                if ($consultaId) {
                    Log::warning('Consulta já existia para este agendamento', [
                        'agendamento_id' => $agendamentoData['agendamento_id'] ?? null,
                        'consulta_id' => $consultaId
                    ]);
                    
                    return [
                        'success' => true,
                        'consulta_id' => $consultaId,
                        'already_existed' => true,
                        'data' => $responseData
                    ];
                }
            }
            
            Log::error('Erro ao criar consulta no consultation-service', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return [
                'success' => false,
                'consulta_id' => null,
                'error' => $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Exception ao criar consulta no consultation-service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'consulta_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Schedule consultation from triage (legacy method)
     * @deprecated Use criarConsulta() instead
     */
    public function agendarConsultaFromTriagem($agendamentoData)
    {
        return $this->criarConsulta($agendamentoData);
    }
    
    /**
     * Update consultation status
     */
    public function atualizarStatusConsulta($consultaId, $status)
    {
        try {
            $response = Http::withToken($this->serviceToken)
                ->timeout(10)
                ->patch("{$this->baseUrl}/api/services/consultas/{$consultaId}/status", [
                    'status' => $status
                ]);
            
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error("Error updating consultation status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel consultation
     */
    public function cancelarConsulta($consultaId, $motivo)
    {
        try {
            $response = Http::withToken($this->serviceToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/api/services/consultas/{$consultaId}/cancelar", [
                    'motivo' => $motivo
                ]);
            
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error("Error cancelling consultation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get consultations with scheduled or waiting status
     * Returns patients that are scheduled or waiting for consultation
     */
    public function buscarConsultasAgendadas($filtros = [])
    {
        try {
            $queryParams = array_merge([
                'status' => 'aguardando_consulta', // Status padrão para consultas agendadas/aguardando
            ], $filtros);
            
            $response = Http::withToken($this->serviceToken)
                ->timeout(15)
                ->get("{$this->baseUrl}/api/consultas", $queryParams);
            
            if ($response->successful()) {
                $data = $response->json('data');
                
                Log::info('Consultas agendadas buscadas do consultation-service', [
                    'total' => is_array($data) ? count($data) : ($data['total'] ?? 0),
                    'filtros' => $filtros
                ]);
                
                return $data;
            }
            
            Log::warning('Falha ao buscar consultas agendadas', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('Exception ao buscar consultas agendadas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Fetch pending consultas (aguardando_consulta) from consultation-service
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
                ->get("{$this->baseUrl}/api/consultas/pendentes");

            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json) && array_key_exists('data', $json)) {
                    return $json['data'];
                }
                return $json;
            }

            Log::warning('Consultation service returned non-success for pendentes', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching pendentes from consultation-service: ' . $e->getMessage());
        }

        return null;
    }
    
    /**
     * Get consultations scheduled for today
     */
    public function buscarConsultasHoje()
    {
        try {
            $response = Http::withToken($this->serviceToken)
                ->timeout(15)
                ->get("{$this->baseUrl}/api/consultas", [
                    'status' => 'pendente',
                    'data_inicio' => now()->startOfDay()->format('Y-m-d H:i:s'),
                    'data_fim' => now()->endOfDay()->format('Y-m-d H:i:s'),
                ]);
            
            if ($response->successful()) {
                return $response->json('data');
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('Exception ao buscar consultas de hoje: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get consultations by patient
     */
    public function buscarConsultasPorPaciente($pacienteId, $status = null)
    {
        try {
            $queryParams = ['paciente_id' => $pacienteId];
            
            if ($status) {
                $queryParams['status'] = $status;
            }
            
            $response = Http::withToken($this->serviceToken)
                ->timeout(15)
                ->get("{$this->baseUrl}/api/consultas/paciente/{$pacienteId}", $queryParams);
            
            if ($response->successful()) {
                return $response->json('data');
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('Exception ao buscar consultas do paciente: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get consultation details
     */
    public function buscarConsulta($consultaId)
    {
        try {
            $response = Http::withToken($this->serviceToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/api/consultas/{$consultaId}");
            
            if ($response->successful()) {
                return $response->json('data');
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Exception ao buscar consulta: ' . $e->getMessage());
            return null;
        }
    }
}