# Fase 4.1 — Recurring Training Schedule

## Contexto

Diretores configuram slots recorrentes de treino por categoria (ex: Sub-12 treina segunda/quarta/sexta às 16h). Um job agendado materializa esses templates em `training_sessions` concretas. Treinadores também podem criar sessões avulsas fora do cronograma recorrente.

---

## Passo a passo

1. Migrations: `create_training_schedules_table` + `create_training_sessions_table`
2. Enums: `DayOfWeek`, `SessionStatus`
3. Models: `TrainingSchedule`, `TrainingSession` + atualizar `Category`
4. Factories: `TrainingScheduleFactory`, `TrainingSessionFactory`
5. Service: `TrainingScheduleService` (create, pause, generateUpcomingSessions, createOneOff)
6. Job: `GenerateTrainingSessions` — agendado aos domingos 01:00
7. Form: `TrainingScheduleForm`
8. Pages: `schedules/⚡index`, `schedules/⚡create`, `sessions/⚡create`
9. Rotas: `/cronogramas`, `/cronogramas/criar`, `/sessoes/criar`
10. Testes: `tests/Feature/Training/TrainingScheduleTest.php`

---

## Schema

### training_schedules
| Coluna | Tipo |
|--------|------|
| id | bigIncrements |
| tenant_id | foreignId → tenants |
| category_id | foreignId → categories |
| day_of_week | string (DayOfWeek enum) |
| start_time | time |
| duration_minutes | unsignedSmallInteger |
| location | string nullable |
| is_active | boolean default true |
| timestamps | |

### training_sessions
| Coluna | Tipo |
|--------|------|
| id | bigIncrements |
| tenant_id | foreignId → tenants |
| category_id | foreignId → categories |
| schedule_id | foreignId nullable → training_schedules |
| session_date | date |
| start_time | time |
| duration_minutes | unsignedSmallInteger |
| location | string nullable |
| status | string (SessionStatus enum, default 'scheduled') |
| notes | text nullable |
| rating | unsignedTinyInteger nullable |
| timestamps | |

Índice único: `(schedule_id, session_date)` — garante idempotência na geração automática.
