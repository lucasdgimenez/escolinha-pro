# Migração UUID → Auto-increment IDs

## Contexto

O projeto usava `uuid('id')` em todas as tabelas e a trait `HasUuids` em todos os models. Como o projeto está em desenvolvimento sem dados de produção, a estratégia foi editar as migrations diretamente e rodar `migrate:fresh --seed`.

---

## Abordagem

Editar as migrations existentes in-place + remover `HasUuids` dos models + `migrate:fresh --seed`.

---

## Migrations alteradas

Substituições aplicadas em cada migration:
- `$table->uuid('id')->primary()` → `$table->id()`
- `$table->foreignUuid('campo')` → `$table->foreignId('campo')`

| Migration | O que mudou |
|-----------|-------------|
| `0001_01_01_000000_create_users_table.php` | PK de users + `foreignUuid('user_id')` na sessions table |
| `2026_03_21_004403_create_tenants_table.php` | PK de tenants |
| `2026_03_21_004429_create_roles_table.php` | PK de roles |
| `2026_03_21_004453_add_tenant_and_role_to_users_table.php` | `tenant_id`, `role_id` |
| `2026_03_21_205444_create_invitations_table.php` | PK + `tenant_id`, `invited_by`, `role_id` |
| `2026_03_21_223205_create_categories_table.php` | PK + `tenant_id` |
| `2026_03_27_000001_create_players_table.php` | PK + `tenant_id`, `category_id` |
| `2026_03_27_000002_create_coach_category_table.php` | `coach_id`, `category_id` |

Migrations duplicadas removidas:
- `2026_03_22_161759_create_players_table.php`
- `2026_03_28_004639_create_coach_category_table.php`

---

## Models alterados

Removido import e trait `HasUuids` de: `User`, `Tenant`, `Role`, `Invitation`, `Category`, `Player`

---

## Livewire alterados

Type hints `string $id` → `int $id` em:
- `pages/academy/⚡categories.blade.php`
- `pages/coaches/⚡assignments.blade.php`
- `pages/invitations/⚡index.blade.php`
