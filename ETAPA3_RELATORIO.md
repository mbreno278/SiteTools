# Etapa 3 — Banco de Dados — Relatório

## Diagnóstico do dump atual

### Já ok
- `tools` já é **InnoDB** (não precisa converter MyISAM)
- `payment_webhook_logs` existe com `UNIQUE(provider, event_id)` (idempotência)
- `user_payments.status` já cobre pending/paid/failed/refunded/cancelled
- `user_tool_access` com status active/expired/cancelled/suspended
- Tabelas de limite e contador presentes

### Problemas encontrados

| Problema | Impacto |
|---|---|
| `users.id` / `tools.id` = **INT**, mas vários `user_id`/`tool_id` = **BIGINT** | Impede FOREIGN KEY |
| Quase nenhuma FK entre users/tools e tabelas filhas | Integridade frágil |
| `tools.category` + `tools.category_id` paralelos | Dupla fonte de categoria |
| `tools.access_type` vs `tool_access_rules.access_mode` | Dupla fonte de regra de acesso |
| Sem `schema_migrations` | Difícil versionar schema |
| Sem `payment_events` | Histórico de domínio limitado |

## Entregáveis

Pasta `database/migrations/` com scripts **incrementais** (sem DROP de tabelas de negócio).

## Política de FK (resumo)

| Relação | ON DELETE |
|---|---|
| favorites → user/tool | CASCADE |
| files → user | CASCADE |
| files → tool | SET NULL |
| payments → user/tool | RESTRICT |
| tool_access → user | CASCADE |
| tool_access → tool | RESTRICT |
| tool_access → payment | SET NULL |
| usage history → user/tool | SET NULL |
| tools → category | SET NULL |

## Como aplicar

1. Backup  
2. `000_precheck.sql`  
3. `001` → `007` em ordem  
4. Conferir `SELECT * FROM schema_migrations`  

## O que o código deve respeitar depois

- Regra comercial: **`tool_access_rules.access_mode`**
- Operacional: **`tools.status` + `is_active`**
- Grant pago: **`user_tool_access`** só após webhook/`paid`
- Cotas: **`tool_usage_limits` + `tool_usage_counters`** (serviço da Etapa 2)
