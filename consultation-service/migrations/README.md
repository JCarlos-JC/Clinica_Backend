# Migrations Laravel 11 - Sistema de Consultas Médicas

Este diretório contém todas as migrations necessárias para o sistema completo de gestão de consultas médicas.

## 📋 Migrations Criadas

### 1. **consultas** - Tabela principal de consultas
- Gerencia todo o ciclo de vida de uma consulta médica
- Relacionamentos com pacientes, médicos, agendamentos e triagens
- Sinais vitais e dados clínicos
- Status e prioridades
- Auditoria completa

### 2. **prescricoes** - Prescrições médicas
- Medicamentos prescritos durante consultas
- Posologia detalhada (doses, horários, duração)
- Controle de dispensação
- Vias de administração
- Status de prescrição

### 3. **exames** - Solicitação e resultados de exames
- Exames laboratoriais e de imagem
- Fluxo completo: solicitação → coleta → análise → laudo
- Resultados em JSON para flexibilidade
- Anexos de arquivos
- Notificações ao médico

### 4. **transferencias** - Transferências de pacientes
- Transferência entre médicos, especialidades ou hospitais
- Controle de aceite/recusa
- Sumário clínico
- Controle de transporte e pagamento
- Auditoria completa

### 5. **altas** - Registro de altas médicas
- Diferentes tipos de alta
- Recomendações e medicamentos para casa
- Agendamento de retorno
- Documentos entregues (atestados, receitas)
- Dados do acompanhante

### 6. **obitos** - Registro de óbitos
- Causa e circunstâncias
- Declaração de óbito
- Notificação de família e autoridades
- Necropsia
- Encaminhamento do corpo

### 7. **historico_consultas** - Auditoria
- Registro de todas as ações realizadas
- Dados anteriores e novos para comparação
- IP e User Agent
- Rastreabilidade completa

---

## 🚀 Instalação

### 1. Copiar migrations para o projeto Laravel

```bash
# Copiar todas as migrations para o diretório do Laravel
cp migrations/*.php /caminho/do/seu/projeto/database/migrations/
```

### 2. Executar as migrations

```bash
# Navegar até o diretório do projeto Laravel
cd /caminho/do/seu/projeto

# Executar migrations
php artisan migrate
```

### 3. Verificar se as tabelas foram criadas

```bash
php artisan migrate:status
```

---

## 🔄 Rollback

Para reverter as migrations:

```bash
# Reverter última migration
php artisan migrate:rollback

# Reverter todas as migrations
php artisan migrate:reset

# Reverter e executar novamente
php artisan migrate:refresh
```

---

## 📊 Estrutura de Relacionamentos

```
pacientes
    ├── consultas
    │   ├── prescricoes
    │   ├── exames
    │   ├── transferencias
    │   ├── altas
    │   ├── obitos
    │   └── historico_consultas
    ├── agendamentos
    └── triagens

users (médicos)
    ├── consultas (como médico responsável)
    ├── prescricoes (como prescritor)
    ├── exames (como solicitante)
    └── transferencias (origem/destino)
```

---

## 🔐 Campos Importantes

### Status de Consulta
- `agendado` - Consulta agendada
- `em_atendimento` - Em andamento
- `aguardando_exames` - Aguardando resultados
- `aguardando_prescricao` - Aguardando prescrições
- `transferido_medico` - Transferido para outro médico
- `transferido_especialidade` - Transferido para outra especialidade
- `finalizada` - Concluída
- `alta` - Paciente recebeu alta
- `obito` - Paciente faleceu
- `cancelada` - Cancelada

### Prioridades
- `normal` - Prioridade normal
- `urgente` - Urgente
- `emergencia` - Emergência

### Tipos de Alta
- `alta_melhorada` - Alta melhorada
- `alta_curada` - Alta curada
- `alta_criterio_clinico` - Alta a critério clínico
- `alta_pedido_paciente` - Alta a pedido
- `alta_evasao` - Evasão
- `transferencia` - Transferência
- `obito` - Óbito

---

## 📝 Campos JSON

### prescricoes.horarios_list
```json
["08:00", "14:00", "20:00"]
```

### exames.resultados
```json
{
  "Hemoglobina": "12.5 g/dL",
  "Hemácias": "4.5 milhões/mm³",
  "Leucócitos": "8000/mm³"
}
```

### exames.valores_referencia
```json
{
  "Hemoglobina": "12-16 g/dL",
  "Hemácias": "4.0-5.5 milhões/mm³"
}
```

### altas.medicamentos_alta
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

## 🔍 Índices Criados

Todas as tabelas possuem índices otimizados para:
- Consultas por paciente
- Consultas por médico
- Consultas por status
- Consultas por data
- Relacionamentos entre tabelas

---

## ⚠️ Dependências

Certifique-se de que as seguintes tabelas já existem no banco:
- `pacientes`
- `users` (tabela de usuários/médicos)
- `agendamentos`
- `triagens`

---

## 🛠️ Comandos Úteis

```bash
# Criar uma nova migration
php artisan make:migration create_nome_tabela

# Executar migrations pendentes
php artisan migrate

# Verificar status das migrations
php artisan migrate:status

# Gerar dump do schema
php artisan schema:dump

# Limpar e recriar banco (cuidado em produção!)
php artisan migrate:fresh

# Executar migrations com seed
php artisan migrate --seed
```

---

## 📚 Próximos Passos

Após executar as migrations, você pode:

1. **Criar os Models** correspondentes:
   ```bash
   php artisan make:model Consulta
   php artisan make:model Prescricao
   php artisan make:model Exame
   php artisan make:model Transferencia
   php artisan make:model Alta
   php artisan make:model Obito
   php artisan make:model HistoricoConsulta
   ```

2. **Criar os Controllers**:
   ```bash
   php artisan make:controller ConsultaController --resource
   php artisan make:controller PrescricaoController --resource
   php artisan make:controller ExameController --resource
   # etc...
   ```

3. **Configurar os relacionamentos** nos Models

4. **Criar as rotas** em `routes/api.php`

5. **Implementar os métodos** dos controllers baseados nos payloads documentados

---

## 📄 Documentação Relacionada

- [PAYLOADS_CONSULTA_COMPLETA.md](../PAYLOADS_CONSULTA_COMPLETA.md) - Payloads completos do frontend
- [Documentação Laravel 11](https://laravel.com/docs/11.x)
- [Migrations Laravel](https://laravel.com/docs/11.x/migrations)

---

## 🤝 Suporte

Em caso de dúvidas ou problemas:
1. Verifique se todas as tabelas dependentes existem
2. Confira os logs em `storage/logs/laravel.log`
3. Execute `php artisan migrate:status` para ver o estado das migrations
4. Use `php artisan migrate:rollback` seguido de `php artisan migrate` para recriar

---

## 📅 Versão

- **Data de Criação**: 23 de Fevereiro de 2026
- **Laravel**: 11.x
- **PHP**: 8.2+
- **Banco de Dados**: MySQL 8.0+ / PostgreSQL 14+ / SQLite 3+
