# 📋 Migrations do Sistema de Consultas Médicas

Todas as migrations necessárias para o sistema completo de gestão de consultas médicas.

## ✅ Tabelas Criadas

### 1. **consultas** - Tabela Principal
Gerencia todo o ciclo de vida de uma consulta médica.

**Campos principais:**
- Relacionamentos: `agendamento_id`, `nid`, `paciente_id`, `triagem_id`, `medico_id`
- Dados clínicos: `anamnese`, `exame_fisico`, `hipotese_diagnostica`, `prescricao`
- Controle: `status`, `prioridade`, `data_consulta`, `hora_consulta`
- Sincronização: `sincronizado_triagem`, `data_sincronizacao_triagem`

**Status disponíveis:**
- `agendada`, `em_atendimento`, `finalizada`, `cancelada`

---

### 2. **consulta_anexos** - Documentos Anexos
Armazena documentos relacionados às consultas.

**Tipos de anexo:**
- `exame`, `receita`, `atestado`, `imagem`, `laudo`, `documento`, `outro`

---

### 3. **prescricoes** - Prescrições Médicas
Prescrições detalhadas de medicamentos.

**Campos principais:**
- Medicamento: `medicamento`, `principio_ativo`, `dosagem`, `forma_farmaceutica`
- Posologia: `frequencia`, `horarios_list` (JSON), `duracao_dias`
- Controle: `medicamento_controlado`, `numero_receita`, `validade_receita`
- Dispensação: `quantidade_dispensada`, `data_dispensacao`, `dispensado_por`

**Status:**
- `prescrita`, `dispensada`, `parcialmente_dispensada`, `cancelada`, `substituida`, `concluida`

**JSON horarios_list:**
```json
["08:00", "14:00", "20:00"]
```

---

### 4. **exames** - Solicitação e Resultados
Fluxo completo de exames laboratoriais e de imagem.

**Campos principais:**
- Solicitação: `tipo_exame`, `nome_exame`, `data_solicitacao`, `urgencia`
- Coleta: `data_hora_coleta`, `material_biologico`, `jejum_necessario`
- Resultados: `resultados` (JSON), `valores_referencia` (JSON), `resultado_qualitativo`
- Laudo: `laudo_medico`, `conclusao`, `laudado_por`, `laudado_por_crm`

**Status:**
- `solicitado`, `agendado`, `aguardando_coleta`, `coletado`, `em_analise`, `laudado`, `disponivel`, `visualizado`, `cancelado`

**JSON resultados:**
```json
{
  "Hemoglobina": "12.5 g/dL",
  "Hemácias": "4.5 milhões/mm³",
  "Leucócitos": "8000/mm³"
}
```

**JSON valores_referencia:**
```json
{
  "Hemoglobina": "12-16 g/dL",
  "Hemácias": "4.0-5.5 milhões/mm³"
}
```

---

### 5. **transferencias** - Transferências de Pacientes
Transferência entre médicos, especialidades ou hospitais.

**Tipos:**
- `entre_medicos`, `entre_especialidades`, `entre_setores`, `entre_hospitais`, `para_uti`, `para_enfermaria`, `para_emergencia`

**Campos principais:**
- Origem: `medico_origem_id`, `especialidade_origem`, `hospital_origem`
- Destino: `medico_destino_id`, `especialidade_destino`, `hospital_destino`
- Clínico: `sumario_clinico`, `diagnostico_principal`, `medicamentos_em_uso` (JSON)
- Transporte: `tipo_transporte`, `necessita_oxigenio`, `necessita_monitor`

**Status:**
- `solicitada`, `aguardando_vaga`, `aguardando_transporte`, `em_transito`, `aceita`, `recusada`, `concluida`, `cancelada`

---

### 6. **altas** - Registro de Altas Médicas
Documentação completa de alta hospitalar.

**Tipos de alta:**
- `alta_melhorada`, `alta_curada`, `alta_criterio_clinico`, `alta_pedido_paciente`, `alta_evasao`, `transferencia`, `obito`

**Campos principais:**
- Diagnóstico: `diagnostico_final`, `cid_principal`, `cids_secundarios` (JSON)
- Recomendações: `recomendacoes_gerais`, `cuidados_domiciliares`, `sinais_alerta`
- Medicamentos: `medicamentos_alta` (JSON), `receita_entregue`, `atestado_entregue`
- Retorno: `necessita_retorno`, `data_retorno`, `especialidade_retorno`

**JSON medicamentos_alta:**
```json
[
  {
    "medicamento": "Losartana",
    "dosagem": "50mg",
    "frequencia": "1x ao dia",
    "duracao": "30 dias"
  }
]
```

---

### 7. **obitos** - Registro de Óbitos
Documentação legal e clínica de óbito.

**Tipos:**
- `natural`, `acidental`, `violento`, `suspeito`, `a_esclarecer`

**Campos principais:**
- Causa: `causa_imediata`, `causa_intermediaria`, `causa_basica`, `cid_principal`
- Declaração: `numero_declaracao_obito`, `cartorio`, `numero_registro_cartorio`
- Necropsia: `necropsia`, `laudo_necropsia`, `medico_legista_nome`
- Notificações: `familia_notificada`, `autoridade_policial_notificada`
- Corpo: `destino_corpo`, `funeraria`, `data_sepultamento`

**Destino do corpo:**
- `aguardando_familia`, `liberado_familia`, `encaminhado_iml`, `encaminhado_necropsia`, `sepultado`

---

### 8. **historico_consultas** - Auditoria Completa
Registro detalhado de todas as ações realizadas.

**Campos de auditoria:**
- Ação: `acao`, `tipo_acao`, `status_anterior`, `status_novo`
- Dados: `dados_anteriores` (JSON), `dados_novos` (JSON)
- Usuário: `usuario_id`, `usuario_nome`, `usuario_papel`
- Rastreamento: `ip_address`, `user_agent`, `dispositivo`, `navegador`, `sistema_operacional`
- Localização: `localizacao_geografica`, `terminal`
- Controle: `acao_critica` (flag para ações sensíveis)

**Tipos de ação:**
- `criacao`, `atualizacao`, `exclusao`, `status_alterado`, `transferencia`, `prescricao`, `exame`, `alta`, `obito`, `outro`

---

## 🔄 Relacionamentos

```
consultas (principal)
    ├── consulta_anexos (1:N)
    ├── prescricoes (1:N)
    ├── exames (1:N)
    ├── transferencias (1:N)
    ├── altas (1:1)
    ├── obitos (1:1)
    └── historico_consultas (1:N)
```

---

## 🚀 Comandos Executados

```bash
# Migrations executadas com sucesso
php artisan migrate --force

# Status atual
php artisan migrate:status
```

---

## 📊 Estatísticas

- **Total de tabelas:** 8
- **Total de campos:** 400+
- **Campos JSON:** 15
- **Campos enum:** 25
- **Foreign keys:** 8
- **Índices criados:** 80+

---

## 🔐 Campos Comuns em Todas as Tabelas

Todas as tabelas incluem:
- `id` - Primary key
- `nid` - Número de Identificação do Paciente
- `created_by` - ID do usuário que criou
- `updated_by` - ID do usuário que atualizou
- `created_at` - Data de criação
- `updated_at` - Data de atualização
- `deleted_at` - Soft delete (exceto historico_consultas)

---

## 📝 Próximos Passos

### 1. Criar Models
```bash
php artisan make:model Prescricao
php artisan make:model Exame
php artisan make:model Transferencia
php artisan make:model Alta
php artisan make:model Obito
```

### 2. Configurar Relacionamentos nos Models
```php
// Consulta.php
public function prescricoes() {
    return $this->hasMany(Prescricao::class);
}

public function exames() {
    return $this->hasMany(Exame::class);
}

public function transferencias() {
    return $this->hasMany(Transferencia::class);
}

public function alta() {
    return $this->hasOne(Alta::class);
}

public function obito() {
    return $this->hasOne(Obito::class);
}
```

### 3. Criar Controllers
```bash
php artisan make:controller Api/PrescricaoController --resource
php artisan make:controller Api/ExameController --resource
php artisan make:controller Api/TransferenciaController --resource
php artisan make:controller Api/AltaController --resource
php artisan make:controller Api/ObitoController --resource
```

### 4. Definir Rotas
Adicionar em `routes/api.php` as rotas para cada resource.

---

## 🛠️ Comandos Úteis

```bash
# Ver estrutura de uma tabela
php artisan db:show prescricoes

# Gerar dump do schema
php artisan schema:dump

# Rollback da última migration
php artisan migrate:rollback

# Rollback específico
php artisan migrate:rollback --step=1

# Recriar todas as tabelas (CUIDADO!)
php artisan migrate:fresh

# Ver queries SQL
php artisan migrate --pretend
```

---

## ⚠️ Observações Importantes

1. **Soft Deletes:** Todas as tabelas (exceto historico_consultas) usam soft delete
2. **JSON Fields:** Campos JSON permitem estruturas flexíveis
3. **Índices:** Otimizados para consultas frequentes
4. **Foreign Keys:** Cascade delete nas tabelas dependentes
5. **Enums:** Valores fixos para garantir consistência
6. **Auditoria:** histórico_consultas registra TODAS as mudanças

---

## 📚 Documentação Relacionada

- [README Principal](../README.md)
- [Models Guide](../app/Models/README.md)
- [Controllers Guide](../app/Http/Controllers/Api/README.md)
- [Rotas API](../routes/api.php)

---

## ✅ Status: COMPLETO

Todas as 8 tabelas foram criadas e estão prontas para uso! 🎉
