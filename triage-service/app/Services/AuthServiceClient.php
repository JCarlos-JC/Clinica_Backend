<?php
// filepath: services/triage-service/app/Services/AuthServiceClient.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthServiceClient
{
    protected $baseUrl;
    protected $serviceToken;
    
    public function __construct()
    {
        $this->baseUrl = config('services.authentication.url', 'http://localhost:8001');
        $this->serviceToken = config('services.service_token');
    }
    
    /**
     * Get user/medico data by ID
     * @param int $userId
     * @return array|null
     */
    public function getUser($userId)
    {
        try {
            $response = Http::withHeaders([
                    'X-Service-Token' => $this->serviceToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(5)
                ->get("{$this->baseUrl}/api/users/{$userId}");

            if ($response->successful()) {
                return $response->json('data') ?? $response->json();
            }
            
            Log::warning('Failed to fetch user from auth service', [
                'user_id' => $userId,
                'status' => $response->status()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error fetching user from auth service: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return null;
        }
    }
    
    /**
     * Get medico data by ID with fallback to direct DB query
     * @param int $medicoId
     * @return array|null
     */
    public function getMedico($medicoId)
    {
        if (!$medicoId) return null;
        
        // Try API first
        $user = $this->getUser($medicoId);
        if ($user) {
            return [
                'id' => $user['id'] ?? $medicoId,
                'nome' => $user['nome'] ?? null,
                'email' => $user['email'] ?? null,
                'cargo' => $user['cargo'] ?? null,
            ];
        }
        
        // Fallback to direct DB query
        try {
            $authDb = DB::connection('mysql')->getPdo();
            $stmt = $authDb->prepare(
                "SELECT id, nome, email, cargo FROM usuarios WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$medicoId]);
            $medico = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $medico ?: null;
            
        } catch (\Exception $e) {
            Log::error('Error fetching medico from database: ' . $e->getMessage());
            return null;
        }
    }
}
