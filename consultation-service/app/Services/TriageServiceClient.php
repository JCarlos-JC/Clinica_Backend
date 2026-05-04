<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriageServiceClient
{
    protected $baseUrl;
    protected $serviceToken;

    public function __construct()
    {
        $this->baseUrl = config('services.triage.url');
        $this->serviceToken = config('services.service_token');
    }

    /**
     * Busca dados da triagem por ID
     */
    public function getTriagem($triagemId)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/triagens/{$triagemId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Falha ao buscar triagem', [
                'triagem_id' => $triagemId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar triagem do triage-service', [
                'triagem_id' => $triagemId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca agendamento por ID
     */
    public function getAgendamento($agendamentoId)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/agendamentos/{$agendamentoId}/detalhes");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar agendamento do triage-service', [
                'agendamento_id' => $agendamentoId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca triagem por NID
     */
    public function getTriagemByNid($nid)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/triagens/nid/{$nid}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar triagem por NID', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Lista todos os agendamentos do triage-service
     */
    public function listarAgendamentos($filtros = [], $token = null)
    {
        try {
            $url = "{$this->baseUrl}/api/agendamentos/";
            
            $headers = [
                'Accept' => 'application/json',
            ];
            
            // Se um token JWT foi fornecido, usá-lo
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
                Log::info('TriageServiceClient: Usando token JWT', [
                    'token_prefix' => substr($token, 0, 20) . '...',
                ]);
            } else {
                // Caso contrário, usar o SERVICE_TOKEN
                $headers['X-Service-Token'] = $this->serviceToken;
                Log::info('TriageServiceClient: Sem token fornecido, usando SERVICE_TOKEN');
            }
            
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url, $filtros);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Falha ao listar agendamentos', [
                'status' => $response->status(),
                'response' => $response->body(),
                'token_provided' => !empty($token)
            ]);

            return ['data' => []];
        } catch (\Exception $e) {
            Log::error('Erro ao listar agendamentos do triage-service', [
                'error' => $e->getMessage()
            ]);
            return ['data' => []];
        }
    }

    /**
     * Busca agendamentos pendentes (não sincronizados)
     */
    public function getAgendamentosPendentes()
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/agendamentos/pendentes");

            if ($response->successful()) {
                return $response->json();
            }

            return ['data' => []];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar agendamentos pendentes', [
                'error' => $e->getMessage()
            ]);
            return ['data' => []];
        }
    }

    /**
     * Atualiza status do agendamento na triagem
     */
    public function atualizarStatusAgendamento($agendamentoId, $status, $consultaId)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->put("{$this->baseUrl}/api/agendamentos/{$agendamentoId}/status", [
                    'status' => $status,
                    'consulta_id' => $consultaId
                ]);

            if ($response->successful()) {
                Log::info('Status do agendamento atualizado na triagem', [
                    'agendamento_id' => $agendamentoId,
                    'status' => $status
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status do agendamento', [
                'agendamento_id' => $agendamentoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Marca agendamento como sincronizado
     */
    public function marcarComoSincronizado($agendamentoId)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->post("{$this->baseUrl}/api/agendamentos/{$agendamentoId}/marcar-sincronizado");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Erro ao marcar agendamento como sincronizado', [
                'agendamento_id' => $agendamentoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Atualiza médico e especialidade do agendamento
     */
    public function atualizarMedicoAgendamento($agendamentoId, $medico, $medicoId, $especialidade = null, $especialidadeId = null)
    {
        try {
            $dados = [
                'medico' => $medico,
                'medico_id' => $medicoId,
            ];

            if ($especialidade) {
                $dados['especialidade'] = $especialidade;
            }

            if ($especialidadeId) {
                $dados['especialidade_id'] = $especialidadeId;
            }

            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->patch("{$this->baseUrl}/api/agendamentos/{$agendamentoId}/medico", $dados);

            if ($response->successful()) {
                Log::info('Médico do agendamento atualizado no triage-service', [
                    'agendamento_id' => $agendamentoId,
                    'medico' => $medico,
                    'especialidade' => $especialidade
                ]);
                return true;
            }

            Log::warning('Falha ao atualizar médico do agendamento', [
                'agendamento_id' => $agendamentoId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar médico do agendamento', [
                'agendamento_id' => $agendamentoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Atualiza status de pagamento e tipo do agendamento
     */
    public function atualizarAgendamentoPagamento($agendamentoId, $dados)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->patch("{$this->baseUrl}/api/agendamentos/{$agendamentoId}/pagamento", $dados);

            if ($response->successful()) {
                Log::info('Pagamento do agendamento atualizado no triage-service', [
                    'agendamento_id' => $agendamentoId,
                    'dados' => $dados
                ]);
                return true;
            }

            Log::warning('Falha ao atualizar pagamento do agendamento', [
                'agendamento_id' => $agendamentoId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pagamento do agendamento', [
                'agendamento_id' => $agendamentoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Busca dados do paciente por NID no triage-service
     */
    public function getPacienteByNid($nid)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/pacientes/nid/{$nid}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar paciente do triage-service', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca detalhes completos de uma triagem incluindo sinais vitais
     */
    public function getTriagemDetalhes($triagemId)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/triagens/{$triagemId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar detalhes da triagem', [
                'triagem_id' => $triagemId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Busca histórico de sinais vitais por NID
     */
    public function getHistoricoSinaisVitais($nid)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/sinais-vitais/historico/{$nid}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de sinais vitais', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Busca histórico de consultas por NID
     */
    public function getConsultasByNid($nid)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get("{$this->baseUrl}/api/consultas/historico/{$nid}");

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
}