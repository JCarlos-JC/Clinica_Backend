<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientServiceClient
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.patient.url');
    }

    /**
     * Busca dados do paciente por NID
     */
    public function getPacienteByNid($nid, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->get("{$this->baseUrl}/api/pacientes/nid/{$nid}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Paciente não encontrado no patient-service', [
                'nid' => $nid,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar paciente do patient-service', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca dados do paciente por ID
     */
    public function getPaciente($pacienteId, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->get("{$this->baseUrl}/api/pacientes/{$pacienteId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar paciente do patient-service', [
                'paciente_id' => $pacienteId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca dados de contato do paciente por ID (email, telefone, endereço)
     */
    public function getDadosContatoPaciente($pacienteId, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->timeout(5)
                ->get("{$this->baseUrl}/api/pacientes/{$pacienteId}/dados-contato");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar dados de contato do paciente', [
                'paciente_id' => $pacienteId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Atualiza histórico médico do paciente
     */
    public function atualizarHistoricoMedico($pacienteId, $consultaData, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->post("{$this->baseUrl}/api/pacientes/{$pacienteId}/historico-medico", $consultaData);

            if ($response->successful()) {
                Log::info('Histórico médico atualizado', [
                    'paciente_id' => $pacienteId
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar histórico médico', [
                'paciente_id' => $pacienteId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Registra histórico de consulta no paciente usando NID
     */
    public function registrarHistoricoConsulta($nid, $historicoData, $token = null)
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->post("{$this->baseUrl}/api/pacientes/nid/{$nid}/historico-consulta", $historicoData);

            if ($response->successful()) {
                Log::info('Histórico de consulta registrado no paciente', [
                    'nid' => $nid,
                    'consulta_id' => $historicoData['consulta_id'] ?? null
                ]);
                return $response->json();
            }

            Log::warning('Falha ao registrar histórico de consulta', [
                'nid' => $nid,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao registrar histórico de consulta no patient-service', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca histórico de consultas do paciente por NID
     */
    public function getHistoricoConsultas($nid, $token = null)
    {
        try {
            $nidUrl = $this->formatNidUrl($nid);
            $url = "{$this->baseUrl}/api/pacientes/{$nidUrl}/historico-consultas";
            
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($token))
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de consultas', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Envia exames solicitados para o patient-service
     */
    public function enviarExamesSolicitados($nid, $exames, $consultaData, $token = null)
    {
        try {
            $payload = [
                'consulta_id' => $consultaData['consulta_id'] ?? null,
                'medico_id' => $consultaData['medico_id'] ?? null,
                'medico_nome' => $consultaData['medico'] ?? null,
                'data_solicitacao' => now()->toDateTimeString(),
                'exames' => $exames,
            ];

            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($token))
                ->post("{$this->baseUrl}/api/pacientes/nid/{$nid}/exames-solicitados", $payload);

            if ($response->successful()) {
                Log::info('Exames enviados para patient-service', [
                    'nid' => $nid,
                    'consulta_id' => $consultaData['consulta_id'] ?? null,
                    'quantidade_exames' => count($exames)
                ]);
                return $response->json();
            }

            Log::warning('Falha ao enviar exames para patient-service', [
                'nid' => $nid,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar exames para patient-service', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Converte NID do formato 0015/2026 para nid/0015/2026
     */
    protected function formatNidUrl($nid)
    {
        // Se já está no formato 0015/2026, converte para nid/0015/2026
        if (preg_match('/^(\d{4})\/(\d{4})$/', $nid, $matches)) {
            return "nid/{$matches[1]}/{$matches[2]}";
        }
        
        // Se está no formato 15/2026, adiciona padding e converte
        if (preg_match('/^(\d+)\/(\d{4})$/', $nid, $matches)) {
            $numero = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
            return "nid/{$numero}/{$matches[2]}";
        }
        
        return "nid/{$nid}";
    }

    /**
     * Busca dados completos do paciente incluindo tipo de utente, contatos
     */
    public function getDadosCompletosPaciente($nid, $token = null)
    {
        try {
            $nidUrl = $this->formatNidUrl($nid);
            $url = "{$this->baseUrl}/api/pacientes/{$nidUrl}/dados-completos";
            
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($token))
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Dados completos do paciente não encontrados', [
                'nid' => $nid,
                'url' => $url,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados completos do paciente', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca histórico de sinais vitais do paciente
     */
    public function getHistoricoSinaisVitais($nid, $token = null)
    {
        try {
            $nidUrl = $this->formatNidUrl($nid);
            $url = "{$this->baseUrl}/api/pacientes/{$nidUrl}/sinais-vitais";
            
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($token))
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de sinais vitais', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca histórico de exames do paciente
     */
    public function getHistoricoExames($nid, $token = null)
    {
        try {
            $nidUrl = $this->formatNidUrl($nid);
            $url = "{$this->baseUrl}/api/pacientes/{$nidUrl}/exames";
            
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($token))
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de exames', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca tipos de utentes
     */
    public function getTiposUtentes($token = null)
    {
        try {
            $url = "{$this->baseUrl}/api/pacientes/tipos-utentes";
            
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($token))
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar tipos de utentes', [
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
        } else {
            // Usar service token se não houver token de usuário
            $serviceToken = config('services.service_token');
            if ($serviceToken) {
                $headers['X-Service-Token'] = $serviceToken;
            }
        }

        return $headers;
    }
}
