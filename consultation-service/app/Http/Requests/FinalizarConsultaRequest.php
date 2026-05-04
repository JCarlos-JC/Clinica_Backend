<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizarConsultaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Normalizar agendamento_id para agendamentoId
        if ($this->has('agendamento_id') && !$this->has('agendamentoId')) {
            $this->merge([
                'agendamentoId' => $this->input('agendamento_id')
            ]);
        }

        // Normalizar horários nas prescrições (converter string para array se necessário)
        if ($this->has('prescricoes')) {
            $prescricoes = $this->input('prescricoes');
            foreach ($prescricoes as $key => $prescricao) {
                // Se horarios é string, converter para array
                if (isset($prescricao['horarios']) && is_string($prescricao['horarios'])) {
                    $prescricoes[$key]['horarios'] = [$prescricao['horarios']];
                }
                // Se horariosList existe e horarios não, usar horariosList
                if (isset($prescricao['horariosList']) && !isset($prescricao['horarios'])) {
                    $prescricoes[$key]['horarios'] = $prescricao['horariosList'];
                }
            }
            $this->merge(['prescricoes' => $prescricoes]);
        }

        // Normalizar exames (converter formato frontend para backend)
        if ($this->has('exames')) {
            $exames = $this->input('exames');
            foreach ($exames as $key => $exame) {
                // Se não tem nome mas tem examesSolicitados, usar examesSolicitados como nome
                if (!isset($exame['nome']) && isset($exame['examesSolicitados'])) {
                    $exames[$key]['nome'] = $exame['examesSolicitados'];
                }
                
                // Se não tem examesSolicitados mas tem nome, usar nome como examesSolicitados
                if (!isset($exame['examesSolicitados']) && isset($exame['nome'])) {
                    $exames[$key]['examesSolicitados'] = $exame['nome'];
                }
                
                // Se não tem estado mas tem outros campos, definir estado padrão
                if (!isset($exame['estado'])) {
                    $exames[$key]['estado'] = $exame['prioridade'] ?? 'Normal';
                }
                
                // Normalizar data_coleta/dataColeta para dataSolicitacao
                if (!isset($exame['dataSolicitacao'])) {
                    if (isset($exame['data_coleta'])) {
                        $exames[$key]['dataSolicitacao'] = $exame['data_coleta'];
                    } elseif (isset($exame['dataColeta'])) {
                        $exames[$key]['dataSolicitacao'] = $exame['dataColeta'];
                    }
                }
            }
            $this->merge(['exames' => $exames]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // ========== DADOS DA CONSULTA ==========
            'agendamentoId' => 'required|integer',
            'dataAlta' => 'nullable|date',
            'status' => 'nullable|string|in:finalizada,finalizada_com_exames,em_andamento',
            
            // ========== DIAGNÓSTICO E OBSERVAÇÕES ==========
            'queixaPrincipal' => 'nullable|string|max:1000',
            'sintomas' => 'nullable|string|max:1000',
            'historiaDoenca' => 'nullable|string|max:3000',
            'historico' => 'nullable|string|max:3000',
            'exameClinico' => 'nullable|string|max:3000',
            'diagnostico' => 'nullable|string|max:2000',
            'conduta' => 'nullable|string|max:3000',
            'observacoes' => 'nullable|string|max:3000',
            'recomendacoes' => 'nullable|string|max:3000',
            
            // ========== PRESCRIÇÕES (ARRAY) ==========
            'prescricoes' => 'nullable|array',
            'prescricoes.*.id' => 'nullable|integer',
            'prescricoes.*.medicamento' => 'required|string|max:255',
            'prescricoes.*.quantidade' => 'required|numeric|min:0.01',
            'prescricoes.*.unidade' => 'required|string|max:50',
            'prescricoes.*.viaAdministracao' => 'required|string|max:100',
            'prescricoes.*.doseDiaria' => 'required|integer|min:1|max:10',
            'prescricoes.*.numeroDias' => 'required|integer|min:1|max:365',
            'prescricoes.*.horarios' => 'required|array|min:1',
            'prescricoes.*.horarios.*' => 'required|string',
            'prescricoes.*.comentario' => 'nullable|string|max:500',
            'prescricoes.*.dosagem' => 'required|string|max:255',
            
            // ========== EXAMES (ARRAY) ==========
            'exames' => 'nullable|array',
            'exames.*.id' => 'nullable|integer',
            'exames.*.nome' => 'required|string|max:255',
            'exames.*.examesSolicitados' => 'nullable|string|max:500',
            'exames.*.observacoes' => 'nullable|string|max:1000',
            'exames.*.estado' => 'nullable|string|max:100',
            'exames.*.prioridade' => 'nullable|string|max:100',
            'exames.*.dataColeta' => 'nullable|string|max:100',
            'exames.*.dataSolicitacao' => 'nullable|date',
            
            // ========== FLAGS DE CONTROLE ==========
            'temPrescricao' => 'nullable|boolean',
            'temExames' => 'nullable|boolean',
            'aguardandoExames' => 'nullable|boolean',
            'deveFinalizarConsulta' => 'nullable|boolean',
            'deveTerminarCiclo' => 'nullable|boolean',
            'tipoFinalizacao' => 'nullable|string|in:com_prescricao,so_exames,basica,retorno_exames',
            
            // ========== DADOS DE RETORNO ==========
            'retornoComExames' => 'nullable|boolean',
            'resultadosExames' => 'nullable',
            'exameId' => 'nullable|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'agendamentoId.required' => 'ID do agendamento é obrigatório',
            'diagnostico.required' => 'Diagnóstico é obrigatório',
            'status.required' => 'Status da consulta é obrigatório',
            'status.in' => 'Status inválido',
            
            'prescricoes.*.medicamento.required' => 'Nome do medicamento é obrigatório',
            'prescricoes.*.quantidade.required' => 'Quantidade é obrigatória',
            'prescricoes.*.quantidade.min' => 'Quantidade deve ser maior que zero',
            'prescricoes.*.unidade.required' => 'Unidade de medida é obrigatória',
            'prescricoes.*.viaAdministracao.required' => 'Via de administração é obrigatória',
            'prescricoes.*.doseDiaria.required' => 'Dose diária é obrigatória',
            'prescricoes.*.doseDiaria.min' => 'Dose diária deve ser pelo menos 1',
            'prescricoes.*.doseDiaria.max' => 'Dose diária não pode ser maior que 10',
            'prescricoes.*.numeroDias.required' => 'Número de dias é obrigatório',
            'prescricoes.*.numeroDias.min' => 'Número de dias deve ser pelo menos 1',
            'prescricoes.*.numeroDias.max' => 'Número de dias não pode ser maior que 365',
            'prescricoes.*.horarios.required' => 'Horários de administração são obrigatórios',
            'prescricoes.*.horarios.min' => 'Deve ter pelo menos 1 horário',
            'prescricoes.*.dosagem.required' => 'Dosagem é obrigatória',
            
            'exames.*.nome.required' => 'Nome do exame é obrigatório',
            'exames.*.examesSolicitados.required' => 'Descrição dos exames solicitados é obrigatória',
            'exames.*.estado.required' => 'Estado do exame é obrigatório',
            
            'temPrescricao.required' => 'Flag de prescrição é obrigatória',
            'temExames.required' => 'Flag de exames é obrigatória',
            'deveFinalizarConsulta.required' => 'Flag de finalização é obrigatória',
            'deveTerminarCiclo.required' => 'Flag de término de ciclo é obrigatória',
            'tipoFinalizacao.required' => 'Tipo de finalização é obrigatório',
            'tipoFinalizacao.in' => 'Tipo de finalização inválido',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
        
        \Illuminate\Support\Facades\Log::warning('Validação de finalização de consulta falhou', [
            'errors' => $errors,
            'payload' => $this->all()
        ]);
        
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Dados inválidos para finalizar consulta',
                'errors' => $validator->errors()->toArray(),
                'summary' => implode(' | ', $errors)
            ], 422)
        );
    }
}
