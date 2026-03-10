<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class ConfigurationService
{
    protected string $baseUrl;
    protected int $cacheMinutes = 10; // Cache por 10 minutos

    public function __construct()
    {
        $this->baseUrl = config('microservices.configuration_service.url', 'http://127.0.0.1:8004');
    }

    /**
     * Get service authentication token
     */
    protected function getToken(): ?string
    {
        // First try to get token from current request (for user-initiated requests)
        $userToken = request()->bearerToken();
        if ($userToken) {
            return $userToken;
        }

        // For service-to-service communication, get a service token
        return $this->getServiceToken();
    }

    /**
     * Get service-to-service authentication token
     */
    protected function getServiceToken(): ?string
    {
        $cacheKey = 'patient_service_token';
        
        return Cache::remember($cacheKey, config('microservices.service_auth.token_cache_minutes', 30) * 60, function () {
            try {
                $authUrl = config('microservices.authentication_service.url', 'http://127.0.0.1:8001');
                
                $response = Http::timeout(30)->post("{$authUrl}/api/service-auth", [
                    'client_id' => config('microservices.service_auth.client_id', 'patient-service'),
                    'client_secret' => config('microservices.service_auth.client_secret', 'patient-service-secret'),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['token'] ?? null;
                }

                Log::error("Erro ao obter token de serviço: " . $response->body());
                return null;
            } catch (\Exception $e) {
                Log::error("Exceção ao obter token de serviço: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Make authenticated request to configuration service
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $token = $this->getToken();

        if (!$token) {
            throw new Exception('Token de autenticação não encontrado');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->{$method}("{$this->baseUrl}/api/{$endpoint}", $data);

        if (!$response->successful()) {
            throw new Exception("Erro ao comunicar com configuration-service: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Validate tipo_utente_id
     */
    public function validateTipoUtente(?int $tipoUtenteId): bool
    {
        if (!$tipoUtenteId) {
            return true;
        }

        try {
            $cacheKey = "tipo_utente_{$tipoUtenteId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tipoUtenteId) {
                $response = $this->makeRequest('get', "tipos-utentes/{$tipoUtenteId}");
                return !empty($response) && 
                       isset($response['status']) && 
                       $response['status'] === 'success' &&
                       isset($response['data']) &&
                       isset($response['data']['id']);
            });
        } catch (Exception $e) {
            Log::error("Erro ao validar tipo_utente_id: {$e->getMessage()}");
            return false;
        }
    }
    

    /**
     * Validate provincia_id
     */
    public function validateProvincia(?int $provinciaId): bool
    {
        if (!$provinciaId) {
            return true;
        }

        try {
            $cacheKey = "provincia_{$provinciaId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($provinciaId) {
                Log::debug("🔍 Iniciando validação de província", ['provincia_id' => $provinciaId]);
                
                $response = $this->makeRequest('get', "provincias/{$provinciaId}");
                Log::debug("📦 Resposta do endpoint de província", ['response' => $response]);
                
                // Verificar se a resposta tem sucesso e contém dados com ID
                $isValid = !empty($response) && 
                          isset($response['status']) && 
                          $response['status'] === 'success' &&
                          isset($response['data']) &&
                          isset($response['data']['id']);
                          
                Log::debug("✅ Resultado da validação de província", [
                    'provincia_id' => $provinciaId,
                    'valid' => $isValid,
                    'response_empty' => empty($response),
                    'has_status' => isset($response['status']),
                    'status_success' => isset($response['status']) && $response['status'] === 'success',
                    'has_data' => isset($response['data']),
                    'data_has_id' => isset($response['data']['id'])
                ]);
                
                return $isValid;
            });
        } catch (Exception $e) {
            Log::error("Erro ao validar provincia_id: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Validate distrito_id
     */
    public function validateDistrito(?int $distritoId): bool
    {
        if (!$distritoId) {
            return true;
        }

        try {
            $cacheKey = "distrito_{$distritoId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($distritoId) {
                $response = $this->makeRequest('get', "distritos/{$distritoId}");
                return !empty($response) && 
                       isset($response['status']) && 
                       $response['status'] === 'success' &&
                       isset($response['data']) &&
                       isset($response['data']['id']);
            });
        } catch (Exception $e) {
            Log::error("Erro ao validar distrito_id: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Validate bairro_id
     */
    public function validateBairro(?int $bairroId): bool
    {
        if (!$bairroId) {
            return true;
        }

        try {
            $cacheKey = "bairro_{$bairroId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($bairroId) {
                $response = $this->makeRequest('get', "bairros/{$bairroId}");
                return !empty($response) && 
                       isset($response['status']) && 
                       $response['status'] === 'success' &&
                       isset($response['data']) &&
                       isset($response['data']['id']);
            });
        } catch (Exception $e) {
            Log::error("Erro ao validar bairro_id: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Validate tipo_documento_id
     */
    public function validateTipoDocumento(?int $tipoDocumentoId): bool
    {
        if (!$tipoDocumentoId) {
            return true;
        }

        try {
            $cacheKey = "tipo_documento_{$tipoDocumentoId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tipoDocumentoId) {
                $response = $this->makeRequest('get', "tipos-documentos/{$tipoDocumentoId}");
                return !empty($response) && 
                       isset($response['status']) && 
                       $response['status'] === 'success' &&
                       isset($response['data']) &&
                       isset($response['data']['id']);
            });
        } catch (Exception $e) {
            Log::error("Erro ao validar tipo_documento_id: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Validate raca_id
     */
    public function validateRaca(?int $racaId): bool
    {
        if (!$racaId) {
            return true;
        }

        try {
            $cacheKey = "raca_{$racaId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($racaId) {
                Log::info("🔍 Validando raca_id: {$racaId}");
                $response = $this->makeRequest('get', "racas/{$racaId}");
                Log::info("📋 Resposta da API de raças:", ['response' => $response]);
                
                // O endpoint de raças retorna dados diretamente, não dentro de 'data'
                $isValid = !empty($response) && isset($response['id']);
                Log::info("✅ Resultado da validação de raça:", ['raca_id' => $racaId, 'valid' => $isValid]);
                return $isValid;
            });
        } catch (Exception $e) {
            Log::error("❌ Erro ao validar raca_id: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Validate unidade_organica_id
     */
    public function validateUnidadeOrganica(?int $unidadeOrganicaId): bool
    {
        if (!$unidadeOrganicaId) {
            return true;
        }

        try {
            $cacheKey = "unidade_organica_{$unidadeOrganicaId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($unidadeOrganicaId) {
                Log::debug("🔍 Iniciando validação de unidade orgânica", ['unidade_organica_id' => $unidadeOrganicaId]);
                
                $response = $this->makeRequest('get', "unidades-organicas/{$unidadeOrganicaId}");
                Log::debug("📦 Resposta do endpoint de unidade orgânica", ['response' => $response]);
                
                // Verificar se a resposta tem sucesso e contém dados com ID
                $isValid = !empty($response) && 
                          isset($response['status']) && 
                          $response['status'] === 'success' &&
                          isset($response['data']) &&
                          isset($response['data']['id']);
                          
                Log::debug("✅ Resultado da validação de unidade orgânica", [
                    'unidade_organica_id' => $unidadeOrganicaId,
                    'valid' => $isValid,
                    'response_empty' => empty($response),
                    'has_status' => isset($response['status']),
                    'status_success' => isset($response['status']) && $response['status'] === 'success',
                    'has_data' => isset($response['data']),
                    'data_has_id' => isset($response['data']['id'])
                ]);
                
                return $isValid;
            });
        } catch (Exception $e) {
            Log::error("Erro ao validar unidade_organica_id: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Validate all external references at once
     */
    public function validateExternalReferences(array $data): array
    {
        $errors = [];

        if (isset($data['tipo_utente_id']) && !$this->validateTipoUtente($data['tipo_utente_id'])) {
            $errors['tipo_utente_id'] = 'Tipo de utente inválido';
        }

        if (isset($data['provincia_id']) && !$this->validateProvincia($data['provincia_id'])) {
            $errors['provincia_id'] = 'Província inválida';
        }

        if (isset($data['distrito_id']) && !$this->validateDistrito($data['distrito_id'])) {
            $errors['distrito_id'] = 'Distrito inválido';
        }

        if (isset($data['bairro_id']) && !$this->validateBairro($data['bairro_id'])) {
            $errors['bairro_id'] = 'Bairro inválido';
        }

        if (isset($data['tipo_documento_id']) && !$this->validateTipoDocumento($data['tipo_documento_id'])) {
            $errors['tipo_documento_id'] = 'Tipo de documento inválido';
        }

        if (isset($data['raca_id']) && !$this->validateRaca($data['raca_id'])) {
            $errors['raca_id'] = 'Raça inválida';
        }

        if (isset($data['unidade_organica_id']) && !$this->validateUnidadeOrganica($data['unidade_organica_id'])) {
            $errors['unidade_organica_id'] = 'Unidade orgânica inválida';
        }

        return $errors;
    }

    /**
     * Get all tipos de utentes
     */
    public function getTiposUtentes(): array
    {
        try {
            return Cache::remember('tipos_utentes_list', $this->cacheMinutes * 60, function () {
                // Usar endpoint services sem autenticação
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get("{$this->baseUrl}/api/services/tipos-utente");

                if (!$response->successful()) {
                    Log::error("Erro ao buscar tipos utentes: " . $response->body());
                    return [];
                }

                $data = $response->json();
                // Alguns endpoints retornam {status, data}, outros retornam array direto
                return isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar tipos de utentes: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get provincias
     */
    public function getProvincias(): array
    {
        try {
            return Cache::remember('provincias_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'provincias');
                // Alguns endpoints retornam {status, data}, outros retornam array direto
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar províncias: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get distritos by provincia
     */
    public function getDistritosByProvincia(int $provinciaId): array
    {
        try {
            $cacheKey = "distritos_provincia_{$provinciaId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($provinciaId) {
                $response = $this->makeRequest('get', "provincias/{$provinciaId}/distritos");
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar distritos: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get bairros by distrito
     */
    public function getBairrosByDistrito(int $distritoId): array
    {
        try {
            $cacheKey = "bairros_distrito_{$distritoId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($distritoId) {
                $response = $this->makeRequest('get', "distritos/{$distritoId}/bairros");
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar bairros: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all tipos de documentos
     */
    public function getTiposDocumentos(): array
    {
        try {
            return Cache::remember('tipos_documentos_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'tipos-documentos');
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar tipos de documentos: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all racas
     */
    public function getRacas(): array
    {
        try {
            return Cache::remember('racas_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'racas');
                // O configuration-service retorna array direto, não objeto com 'data'
                return is_array($response) ? $response : [];
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar raças: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all unidades organicas
     */
    public function getUnidadesOrganicas(): array
    {
        try {
            return Cache::remember('unidades_organicas_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'unidades-organicas');
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar unidades orgânicas: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all graus de parentesco
     */
    public function getGrausParentesco(): array
    {
        try {
            return Cache::remember('graus_parentesco_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'graus-parentesco');
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar graus de parentesco: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get valor da consulta baseado no tipo_consulta_id e tipo_utente_id
     * 
     * @param int $tipoConsultaId
     * @param int $tipoUtenteId
     * @return array|null ['valor' => float, 'descricao' => string]
     */
    public function getValorConsulta(int $tipoConsultaId, int $tipoUtenteId): ?array
    {
        try {
            $cacheKey = "valor_consulta_{$tipoConsultaId}_{$tipoUtenteId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tipoConsultaId, $tipoUtenteId) {
                // CORRIGIDO: Query params devem ser passados na URL, não no body
                $token = $this->getToken();

                if (!$token) {
                    throw new Exception('Token de autenticação não encontrado');
                }

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ])->get("{$this->baseUrl}/api/services/tipos-consultas/{$tipoConsultaId}/valor", [
                    'tipo_utente_id' => $tipoUtenteId // Query parameter
                ]);

                if (!$response->successful()) {
                    throw new Exception("Erro ao buscar valor da consulta: " . $response->body());
                }

                $data = $response->json();
                
                if (isset($data['data'])) {
                    return [
                        'valor' => $data['data']['valor'] ?? 0,
                        'descricao' => $data['data']['descricao'] ?? null,
                        'tipo_consulta' => $data['data']['tipo_consulta'] ?? null,
                        'tipo_utente' => $data['data']['tipo_utente'] ?? null
                    ];
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar valor da consulta: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get all distritos
     */
    public function getAllDistritos(): array
    {
        try {
            return Cache::remember('all_distritos_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'distritos');
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar todos os distritos: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all bairros
     */
    public function getAllBairros(): array
    {
        try {
            return Cache::remember('all_bairros_list', $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'bairros');
                return isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar todos os bairros: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get specific provincia by ID
     */
    public function getProvincia(int $provinciaId): ?array
    {
        $provincias = $this->getProvincias();
        return collect($provincias)->firstWhere('id', $provinciaId);
    }
    
    /**
     * Get specific distrito by ID
     */
    public function getDistrito(int $distritoId): ?array
    {
        $distritos = $this->getAllDistritos();
        return collect($distritos)->firstWhere('id', $distritoId);
    }
    
    /**
     * Get specific bairro by ID
     */
    public function getBairro(int $bairroId): ?array
    {
        $bairros = $this->getAllBairros();
        return collect($bairros)->firstWhere('id', $bairroId);
    }
    
    /**
     * Get specific tipo utente by ID
     */
    public function getTipoUtente(int $tipoUtenteId): ?array
    {
        $tiposUtentes = $this->getTiposUtentes();
        return collect($tiposUtentes)->firstWhere('id', $tipoUtenteId);
    }
    
    /**
     * Get specific unidade organica by ID
     */
    public function getUnidadeOrganica(int $unidadeOrganicaId): ?array
    {
        $unidadesOrganicas = $this->getUnidadesOrganicas();
        return collect($unidadesOrganicas)->firstWhere('id', $unidadeOrganicaId);
    }
    
    /**
     * Get specific tipo documento by ID
     */
    public function getTipoDocumento(int $tipoDocumentoId): ?array
    {
        $tiposDocumentos = $this->getTiposDocumentos();
        return collect($tiposDocumentos)->firstWhere('id', $tipoDocumentoId);
    }
    
    /**
     * Get specific raca by ID
     */
    public function getRaca(int $racaId): ?array
    {
        $racas = $this->getRacas();
        return collect($racas)->firstWhere('id', $racaId);
    }

    /**
     * Get métodos de pagamento
     */
    public function getMetodosPagamento(): array
    {
        try {
            $cacheKey = 'metodos_pagamento';
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () {
                // Usar endpoint services sem autenticação
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get("{$this->baseUrl}/api/services/metodos-pagamento");

                if (!$response->successful()) {
                    Log::error("Erro ao buscar métodos pagamento: " . $response->body());
                    return [];
                }

                $data = $response->json();
                return $data['data'] ?? [];
            });
        } catch (\Exception $e) {
            Log::error('Erro ao buscar métodos de pagamento: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tipos de consulta
     */
    public function getTiposConsulta(): array
    {
        try {
            $cacheKey = 'tipos_consulta';
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () {
                $response = $this->makeRequest('get', 'tipos-consulta');
                return $response['data'] ?? [];
            });
        } catch (\Exception $e) {
            Log::error('Erro ao buscar tipos de consulta: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get método de pagamento by ID
     */
    public function getMetodoPagamento(int $metodoPagamentoId): ?array
    {
        $metodos = $this->getMetodosPagamento();
        return collect($metodos)->firstWhere('id', $metodoPagamentoId);
    }

    /**
     * Get tipo consulta by ID
     */
    public function getTipoConsulta(int $tipoConsultaId): ?array
    {
        $tipos = $this->getTiposConsulta();
        return collect($tipos)->firstWhere('id', $tipoConsultaId);
    }

    /**
     * Get consultas disponíveis para um tipo de utente da tabela preco_consultas
     * 
     * @param int $tipoUtenteId
     * @return array Lista de consultas disponíveis com valor, descrição e dados completos
     */
    public function getConsultasDisponiveisParaUtente(int $tipoUtenteId): array
    {
        try {
            $cacheKey = "consultas_disponiveis_utente_{$tipoUtenteId}";
            
            return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tipoUtenteId) {
                // Fazer requisição direta sem autenticação JWT para o endpoint services
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get("{$this->baseUrl}/api/services/tipos-utentes/{$tipoUtenteId}/consultas-disponiveis");

                if (!$response->successful()) {
                    Log::error("Erro ao buscar consultas disponíveis: " . $response->body());
                    return [];
                }

                $data = $response->json();
                return $data['data'] ?? [];
            });
        } catch (Exception $e) {
            Log::error("Erro ao buscar consultas disponíveis para utente {$tipoUtenteId}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Clear all configuration cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
