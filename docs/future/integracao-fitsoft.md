# Plano: Integração API FitSoft ↔ Escolinha Pro

## Context

O Escolinha Pro precisa de funcionalidades de gestão de treinos (biblioteca de exercícios, geração de fichas de treino com IA, rastreamento de sessões). O FitSoft já implementa exatamente isso, e a estratégia é expor essas funcionalidades como uma API REST autenticada por API key — o Escolinha Pro consome essa API via HTTP client, sem duplicar código ou banco de dados.

**Estado atual dos dois sistemas:**

| Sistema | API | Sanctum | HTTP Client |
|---|---|---|---|
| FitSoft | 4 endpoints `/api/*` existem mas **sem auth** e com `user_id` inseguro via query param | Sanctum 3.3 instalado; `Login` model usa `HasApiTokens` | — |
| Escolinha Pro | Nenhum endpoint API | Sanctum 4.3 instalado; `User` model **não** usa `HasApiTokens` | Nenhuma chamada HTTP outgoing ainda; Guzzle disponível via framework |

---

## Parte 1 — FitSoft: Construir API Segura

### 1.1 Autenticação por API Key (Sanctum)

Esta é uma integração **servidor-a-servidor**: o backend do Escolinha Pro chama o FitSoft diretamente. Os usuários do Escolinha Pro não se autenticam no FitSoft.

**Setup único (feito uma vez):**
1. Criar usuário de serviço no FitSoft: `escolinha-pro@fitsoft.app`
2. Gerar Personal Access Token via tinker:
   ```php
   Login::where('email', 'escolinha-pro@fitsoft.app')->first()->createToken('escolinha-pro')->plainTextToken
   ```
3. Copiar o token gerado para o `.env` do Escolinha Pro como `FITSOFT_API_KEY`

A partir daí, **todas as chamadas** do Escolinha Pro incluem o header `Authorization: Bearer <token>` fixo — nenhuma credencial de usuário final é transmitida.

Proteger todos os endpoints da API com `auth:sanctum` middleware.

**Arquivos a modificar no FitSoft:**
- `routes/api.php` — adicionar grupo com `auth:sanctum`, versionar em `/api/v1/`, remover parâmetro `user_id`
- `app/Http/Controllers/ApiController.php` — substituir `request('user_id')` por `auth()->user()`

### 1.2 CORS

Configurar `config/cors.php` no FitSoft para aceitar requisições do domínio do Escolinha Pro:

```php
'allowed_origins' => [env('ESCOLINHA_PRO_URL', 'http://localhost')],
'allowed_methods' => ['GET', 'POST'],
'allowed_headers' => ['Content-Type', 'Authorization'],
```

### 1.3 Endpoints a Expor

**Biblioteca de Exercícios:**
```
GET  /api/v1/exercises                  — lista paginada (filtros: category, search)
GET  /api/v1/exercises/{id}             — detalhe de um exercício
```

**Planos de Treino:**
```
GET  /api/v1/training-plans             — planos do trainer autenticado
GET  /api/v1/training-plans/{id}        — plano com exercícios (nested)
POST /api/v1/training-plans             — criar plano (manual)
```

**Geração por IA:**
```
POST /api/v1/training-plans/generate    — gera ficha via IA (envia: player_profile, goals, restrictions)
POST /api/v1/training-plans/import-photo — importa ficha via imagem (base64)
```

**Sessões de Treino:**
```
GET  /api/v1/sessions                   — sessões do aluno autenticado
POST /api/v1/sessions/{id}/start        — iniciar sessão
POST /api/v1/sessions/{id}/finish       — finalizar sessão
```

### 1.4 API Resources (Formatação Consistente)

Criar Eloquent API Resources para garantir respostas padronizadas:

**Arquivos a criar no FitSoft:**
- `app/Http/Resources/ExerciseResource.php`
- `app/Http/Resources/TrainingPlanResource.php` — com `DiaExecucaoResource` nested
- `app/Http/Resources/TrainingSessionResource.php`

Exemplo de resposta padronizada:
```json
{
  "data": {
    "id": 1,
    "name": "Rondo 4v2",
    "category": "technical",
    "description": "...",
    "video_url": "https://..."
  }
}
```

### 1.5 Versionamento

Prefixar todas as rotas com `/api/v1/` para permitir evolução sem quebrar compatibilidade.

---

## Parte 2 — Escolinha Pro: Consumir a API

### 2.1 Configuração

**`.env` do Escolinha Pro:**
```
FITSOFT_API_URL=https://fitsoft.app
FITSOFT_API_KEY=<token_gerado_no_fitsoft>
```

**`config/services.php`** — adicionar bloco:
```php
'fitsoft' => [
    'url'     => env('FITSOFT_API_URL'),
    'api_key' => env('FITSOFT_API_KEY'),
],
```

### 2.2 Service Class HTTP Client

**Arquivo a criar:** `app/Services/FitSoftApiService.php`

```php
class FitSoftApiService
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.fitsoft.url'))
            ->withToken(config('services.fitsoft.api_key'))
            ->acceptJson();
    }

    public function getExercises(array $filters = []): Collection { ... }
    public function getExercise(int $id): array { ... }
    public function createTrainingPlan(array $planData): array { ... }
    public function generateTrainingPlan(array $playerProfile): array { ... }
    public function importPlanFromPhoto(string $base64Image): array { ... }
}
```

O service retorna dados ou lança `FitSoftApiException` em caso de erro, nunca lida com apresentação.

**Arquivo a criar:** `app/Exceptions/FitSoftApiException.php`

### 2.3 Pontos de Integração nas Fases do Escolinha Pro

| Fase Escolinha Pro | Como usa a API FitSoft |
|---|---|
| **Fase 6 — Biblioteca de Exercícios** | Livewire component chama `FitSoftApiService::getExercises()` para popular o seletor de exercícios no wizard do plano de treino |
| **Fase 6 — Criar Plano Manual** | `FitSoftApiService::createTrainingPlan()` envia o plano montado pelo coach (exercícios, séries, cargas) para o FitSoft via `POST /api/v1/training-plans`; resultado é salvo localmente com referência ao ID externo |
| **Fase 6 — Plano de Treino por IA** | `FitSoftApiService::generateTrainingPlan()` gera ficha via IA com base no perfil do jogador; resultado é salvo localmente |
| **Fase 6 — Import por Foto** | `FitSoftApiService::importPlanFromPhoto()` processa imagem e retorna estrutura de exercícios |
| **Fase 9 — Sugestão de Sessão** | `FitSoftApiService::generateTrainingPlan()` recebendo lacunas de avaliação do jogador como contexto |

### 2.4 Exemplo de Uso no Livewire (Fase 6)

```php
// No wizard de criação de plano de treino
public function loadExercises(FitSoftApiService $fitSoft): void
{
    $this->exercises = $fitSoft->getExercises([
        'category' => $this->filterCategory,
        'search'   => $this->search,
    ]);
}
```

---

## Arquivos-Chave a Criar/Modificar

### FitSoft
| Arquivo | Ação |
|---|---|
| `routes/api.php` | Reestruturar com prefixo v1, adicionar `auth:sanctum` |
| `app/Http/Controllers/ApiController.php` | Substituir `user_id` param por `auth()->user()` |
| `config/cors.php` | Adicionar domínio do Escolinha Pro |
| `app/Http/Resources/ExerciseResource.php` | Criar |
| `app/Http/Resources/TrainingPlanResource.php` | Criar |
| `app/Http/Resources/TrainingSessionResource.php` | Criar |

### Escolinha Pro
| Arquivo | Ação |
|---|---|
| `config/services.php` | Adicionar bloco `fitsoft` |
| `.env` (+ `.env.example`) | Adicionar `FITSOFT_API_URL` e `FITSOFT_API_KEY` |
| `app/Services/FitSoftApiService.php` | Criar |
| `app/Exceptions/FitSoftApiException.php` | Criar |
| Livewire components das Fases 6 e 9 | Injetar `FitSoftApiService` nos métodos relevantes |

---

## Posicionamento no Roadmap do Escolinha Pro

Este plano deve ser executado **entre a Fase 5 e a Fase 6** do projeto:

```
Fase 4 (Training Sessions) → Fase 5 (Player Evaluation) → [ESTE PLANO] → Fase 6 (Training Plans) → Fase 7+ → Fase 9 (AI)
```

As Fases 4 e 5 são independentes do FitSoft e devem ser implementadas primeiro conforme `docs/project-phases.md`.

---

## Sequência de Implementação

1. **FitSoft — Proteger API existente** com `auth:sanctum` + substituir `user_id` por `auth()->user()`
2. **FitSoft — Criar API Resources** para formato padronizado
3. **FitSoft — Adicionar endpoints faltantes** (exercises detail, training-plans CRUD, AI generation, sessions)
4. **FitSoft — Configurar CORS** para aceitar o domínio do Escolinha Pro
5. **FitSoft — Gerar Personal Access Token** para o Escolinha Pro
6. **Escolinha Pro — Configurar `config/services.php`** e vars de ambiente
7. **Escolinha Pro — Criar `FitSoftApiService`** com todos os métodos
8. **Escolinha Pro — Integrar no wizard da Fase 6** (seletor de exercícios + geração IA)
9. **Testes Pest** em ambos os sistemas (mock `Http::fake()` no Escolinha Pro)

---

## Verificação

- **FitSoft**: `curl -H "Authorization: Bearer <token>" https://fitsoft.app/api/v1/exercises` retorna JSON padronizado
- **Escolinha Pro**: No wizard de criação de plano, exercícios são carregados da API do FitSoft com filtros funcionando
- **Geração IA**: Coach envia perfil do jogador → API FitSoft retorna plano → plano salvo localmente no Escolinha Pro
- **Testes**: `php artisan test --compact` passa em ambos os projetos; `Http::fake()` usado para mockar chamadas ao FitSoft nos testes do Escolinha Pro
- **Falha tolerante**: Se a API do FitSoft estiver indisponível, `FitSoftApiException` é capturada e exibe mensagem em pt-BR ao usuário
