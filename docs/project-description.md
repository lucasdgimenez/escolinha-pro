# Escolinha Pro — Product Requirements Document (MVP)

## Overview

**Escolinha Pro** is a football-specialized SaaS platform for managing youth football academies (*escolinhas*) in Brazil. Unlike generic sports management tools, it is built exclusively for football — with the sport's terminology, age categories, technical fundamentals, and culture embedded at its core.

The platform serves three audiences simultaneously: **academy directors** who need operational control (enrollments, payments, attendance), **coaches** who need tools to track and develop players technically, and **parents** who want visibility into their child's progress and feel connected to the club.

The MVP focuses on a single geographic region (one city or metro area) to enable high-touch onboarding, word-of-mouth growth, and rapid iteration based on real customer feedback. The goal is to become the default tool for football academies in that market before expanding nationally.

The AI layer is a first-class differentiator from day one: it generates player development reports, suggests training sessions based on a player's position and age, and automates communication to parents — capabilities that no generic competitor currently offers at this market segment.

---

## Tech Stack

| Layer | Technology | Rationale |
|---|---|---|
| Backend | Laravel 13 | Proven, fast to build, excellent ecosystem for Brazilian market |
| Frontend Web | Laravel Blade + Livewire | Server-rendered UI within Laravel — no separate build pipeline, ideal for solo MVP |
| Mobile App | React Native (Expo) | Cross-platform parent & player app; consumes Laravel API routes |
| Database | PostgreSQL (on Droplet) | Runs on the same Droplet; simple to manage at MVP scale |
| Cache / Queue | Redis (on Droplet) + Laravel Horizon | Co-located with the app; zero network latency for cache hits |
| File Storage | DigitalOcean Spaces (S3-compatible) | Only external service — player photos and documents stored off-server to avoid disk exhaustion |
| Payments | Stripe or Pagar.me | Recurring subscriptions (B2B) + parent fee collection (B2C) |
| AI / LLM | OpenAI GPT-4o via API | Player development narratives, training suggestions, parent communications |
| RAG | Laravel + pgvector | Contextualizes AI responses with player history and club data |
| Push Notifications | Firebase Cloud Messaging | Parent and player mobile notifications |
| Email | Resend | Transactional and marketing emails |
| Local Dev Environment | Docker Compose (manual) | Reproducible local environment matching production services |
| Testing | Pest PHP (Feature + Unit) | Expressive syntax, fast feedback loop; Dusk added later if browser tests are needed |
| Hosting | DigitalOcean Droplet (manual) | Single Ubuntu droplet with Nginx + PHP 8.3; straightforward ops for solo MVP phase |
| CI/CD | GitHub Actions | Run Pest suite on every push; deploy to Droplet via SSH on merge to main |

### Hosting Architecture (DigitalOcean)

```
┌──────────────────────────────────────────────────┐
│                 DigitalOcean                     │
│                                                  │
│  ┌───────────────────────────────────────────┐   │
│  │              Droplet (single)             │   │
│  │              Ubuntu 22.04                 │   │
│  │                                           │   │
│  │   Nginx  ──►  PHP 8.3 / Laravel           │   │
│  │                    │                      │   │
│  │             ┌──────┴──────┐               │   │
│  │             │             │               │   │
│  │         PostgreSQL      Redis             │   │
│  │         (local)         (local)           │   │
│  │                                           │   │
│  │   Laravel Horizon (queue worker)          │   │
│  └───────────────────────────────────────────┘   │
│                      │                           │
│                      ▼                           │
│           ┌─────────────────────┐                │
│           │     DO Spaces       │                │
│           │  (S3-compatible)    │                │
│           │  player photos      │                │
│           │  documents          │                │
│           └─────────────────────┘                │
└──────────────────────────────────────────────────┘
```

**Migration path when needed:** PostgreSQL and Redis running on the Droplet is the right call for MVP. When the product grows, both can be migrated to DO Managed Database and DO Managed Redis with a single connection string change in `.env` — no application code changes required. DO Spaces stays as-is throughout.

**DO Spaces configuration in Laravel `.env`:**
```
FILESYSTEM_DISK=spaces
DO_SPACES_KEY=your-key
DO_SPACES_SECRET=your-secret
DO_SPACES_REGION=nyc3
DO_SPACES_BUCKET=escolinha-pro
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
```
Usage in application code is identical to local storage — only the driver changes.

---

## Users & Roles (RBAC)

### Role: `super_admin`
Platform-level administrator (you, the SaaS owner). Has full access to all tenants, billing, feature flags, and system health. Not visible to customers.

### Role: `academy_director`
Owner or manager of an *escolinha*. Creates and manages the academy's account (tenant). Responsibilities:
- Configure academy settings, categories, fields, and fee structure
- Manage enrollments, contracts, and payment collection from parents
- Invite and manage coaches
- View financial reports and occupancy dashboards
- Access all player profiles within the academy

### Role: `coach` (`treinador`)
Technical staff assigned to one or more age categories. Responsibilities:
- Manage player attendance per training session
- Create and assign training plans (workout sheets) per category or individual player
- Record technical evaluations (per fundamental: passing, dribbling, finishing, positioning, set pieces)
- View AI-generated player development reports
- Cannot access financial data

### Role: `parent` (`responsável`)
Parent or legal guardian of an enrolled player. Access via **mobile app only** (React Native). The web panel is reserved for operational roles (director, coach, super_admin). Capabilities:
- View their child's profile, attendance history, and technical evaluation timeline
- Receive push notifications for upcoming sessions, absences, and monthly reports
- Pay monthly fees (boleto or credit card via in-app payment)
- Receive AI-generated monthly progress narrative for their child
- Cannot see other players' data

### Role: `player` (`atleta`) — optional for 15+
Older players can have their own app login. Capabilities:
- View their own training plan and check in to sessions
- See their personal stats and evaluation history
- Receive training reminders and club news feed

---

## Core Workflows

### 1. Academy Onboarding
1. Director signs up and creates the academy account (name, logo, address, age categories)
2. System provisions a tenant with isolated data
3. Director configures categories (Sub-7 through Sub-17, following CBF age groups), monthly fees per category, and training schedule
4. Director invites coaches by email (role: `coach`)
5. Director optionally imports existing player list via CSV

### 2. Player Enrollment
1. Director or coach registers a new player (name, DOB, position, dominant foot, photo, guardian contact)
2. System auto-assigns player to the correct CBF age category based on DOB
3. Parent receives onboarding invite via WhatsApp/SMS link to download the parent app
4. Parent completes profile and sets up recurring payment
5. Player profile is immediately visible to assigned coach

### 3. Training Session Management
1. Coach opens the session for their category (date, field, duration)
2. Coach marks attendance — present, absent, or justified absence — per player
3. System records attendance timestamp and notifies parents of absent players automatically
4. After session, coach can add session notes (free text) and rate group performance

### 4. Player Technical Evaluation
1. Coach accesses a player's profile and opens a new evaluation
2. Coach scores the player across football-specific fundamentals:
   - Technical: passing, dribbling, finishing, ball control, heading
   - Physical: speed, endurance, strength (subjective 1–5 scale)
   - Tactical: positioning, decision-making, off-ball movement
   - Attitude: effort, coachability, teamwork
3. Coach adds free-text observations per evaluation
4. System plots evaluation history on a radar chart over time
5. AI generates a development narrative in Portuguese based on the evaluation delta and player history (powered by RAG with the player's full record)

### 5. Training Plan (Workout Sheet)
1. Coach creates a training plan for a category or individual player
2. Plan consists of ordered exercises from a football-specific library (e.g., "Rondo 4v2", "Finishing from crosses", "Positional shadow play")
3. Each exercise includes description, duration, objective (technical/physical/tactical), and optional video reference link
4. Plan is published and visible to players (15+) in the app
5. Coach can duplicate and iterate plans across categories

### 6. Parent Portal (Mobile App)
1. Parent opens app and sees their child's dashboard:
   - Upcoming sessions (calendar view)
   - Last attendance record (present/absent)
   - Current evaluation scores (radar chart)
   - Monthly fee status (paid / due / overdue)
2. Parent receives push notification when:
   - Child is marked absent
   - New evaluation is recorded
   - Monthly AI report is ready
   - Fee payment is due or overdue
3. Monthly AI Report: on the 1st of each month, the system auto-generates a personalized 150–200 word narrative per player ("João teve uma excelente evolução no passe curto este mês...") and sends to parent via push + email
4. Parent pays monthly fee directly in the app (Pix, boleto, or credit card)

### 7. AI Features (Powered by OpenAI + RAG)
All AI outputs use the player's stored history (evaluations, attendance, training plans, coach notes) as RAG context, ensuring responses are grounded in real data rather than generic.

| Feature | Trigger | Output |
|---|---|---|
| Monthly player report | Automated, 1st of month | 150–200 word Portuguese narrative for parents |
| Development summary | Coach requests from player profile | Technical 1-page scouting-style summary |
| Training session suggestion | Coach opens "suggest session" | 3 exercise suggestions based on category age + recent evaluation gaps |
| Attendance alert | Player misses 3+ consecutive sessions | Automated message draft for coach to send to parent |
| Evaluation narrative | After each evaluation is saved | 2–3 sentence coach note auto-generated, editable before saving |

---

## Admin Tools (System Level)

These tools are accessible only to `super_admin` and are not visible to academy customers.

### Tenant Management
- List all academies (tenants): name, plan, created date, MRR, last active
- Ability to impersonate any tenant for support purposes (audit-logged)
- Suspend or reactivate academy accounts
- Manually adjust billing or extend trials

### Feature Flags
- Toggle AI features per tenant (useful for controlled rollout and support triage)
- Enable/disable parent payment collection per tenant (for regions where Pagar.me is not supported)
- Beta feature enrollment per tenant

### Usage & Health Dashboard
- Active academies, MAU (monthly active users), churn indicators
- AI API cost per tenant (to monitor margin per customer)
- Failed payment webhook log
- Push notification delivery rates

### Content Management
- Manage the global exercise library (add, edit, categorize football exercises)
- Manage CBF age category definitions (Sub-7 through Sub-20 rules)
- Manage email and push notification templates

### Support Tools
- View any player profile, evaluation, or training plan in read-only mode (for support tickets)
- Manually trigger AI report generation for a specific player
- Export tenant data for LGPD (Brazilian GDPR) data requests

---

## Tech Requirements

### Multi-tenancy
Full data isolation per academy using a `tenant_id` scoping strategy on all models. No cross-tenant data leakage. Middleware-enforced at the Laravel service layer.

### Security
- All API routes protected via Laravel Sanctum (token-based auth)
- RBAC enforced at the policy layer (Laravel Policies), not just middleware
- PII fields (player DOB, guardian CPF) encrypted at rest using application-level encryption
- LGPD compliance: data export and deletion endpoints required at MVP
- Rate limiting on all public-facing endpoints

### Performance
- All heavy operations (AI report generation, payment processing, bulk notifications) run as queued jobs via Laravel Horizon
- PostgreSQL indexes on `tenant_id`, `player_id`, `created_at` for all high-read tables
- Redis caching for dashboard aggregations (attendance counts, payment summaries) with 5-minute TTL

### AI / RAG
- Player context assembled server-side before each OpenAI call — never expose raw database records to the model
- All AI outputs are stored and versioned (never regenerated on-the-fly for display)
- Coach can edit any AI-generated text before it is sent to parents
- Token usage tracked per tenant for cost attribution

### Payments
- Academy subscription (B2B): Stripe recurring billing in USD or BRL
- Parent fee collection (B2C): Pagar.me for Pix, boleto bancário, and credit card — native Brazilian payment methods
- Webhook processing for both providers with idempotency keys
- Automatic dunning: 3-email sequence + in-app banner for overdue parent fees

### Frontend Architecture
The web panel (director, coach, super_admin) is served entirely via **Laravel Blade + Livewire**. There is no separate frontend build step or JavaScript framework for the web layer. Dynamic interactions (attendance marking, live search, modal forms) are handled by Livewire components, keeping everything within the Laravel monolith.

The **React Native app** (parents and players 15+) consumes dedicated `api/` routes in the same Laravel application, authenticated via Laravel Sanctum tokens. This clean separation means the web panel and mobile API coexist in one codebase without duplication.

### Mobile App (React Native)
- Consumes `api/` routes from the same Laravel application (Sanctum token auth)
- Separate experience per role: parent app is read-only + payments; player app (15+) adds training check-in
- Offline-capable attendance marking for coaches (sync on reconnect)
- Biometric authentication support (Face ID / fingerprint)
- Deep links for notification CTAs (e.g., tap "View Report" → opens player report directly)

### File Storage (DigitalOcean Spaces)
All user-uploaded files are stored in DO Spaces, not on the Droplet's local filesystem. This prevents disk exhaustion on the server and makes files accessible via CDN URL regardless of server restarts or migrations.

Files stored: player profile photos, guardian document uploads (RG/CPF for LGPD), academy logos. Video clips are out of scope for MVP.

Laravel's `Storage` facade abstracts the driver entirely — application code calls `Storage::disk('spaces')->put(...)` identically to how it would call local storage. The driver is swapped via `.env` with no code changes required.

### Local Development (Docker Compose)
The local environment is fully containerized via Docker Compose. This ensures every developer (and CI) runs the exact same stack regardless of host OS, eliminating "works on my machine" issues.

**Services in `docker-compose.yml`:**
```yaml
services:
  escolinhapro_app_fpm:      # PHP 8.3 + Laravel (php-fpm) — docker/php/
  escolinhapro_app_nginx:    # Nginx reverse proxy → fpm — docker/nginx/
  escolinhapro_app_postgres: # PostgreSQL — docker/postgresql/
  escolinhapro_app_redis:    # Redis — docker/redis/
  escolinhapro_app_queue:    # Laravel Horizon queue worker (supervisor) — docker/supervisor/
```

**Docker folder structure:**
```
docker/
├── nginx/
│   └── default.conf        # Reverse proxy config to php-fpm
├── php/
│   └── Dockerfile          # PHP 8.3-fpm + extensions (pdo_pgsql, redis, pcntl)
├── postgresql/
│   └── Dockerfile (or init scripts if needed)
├── redis/
│   └── redis.conf
└── supervisor/
    └── supervisord.conf    # Manages Horizon queue worker
```

**Network:** All containers connect through a dedicated bridge network `escolinhapro_network`.

**Key conventions:**
- `.env` has a `.env.docker` counterpart with container hostnames (`DB_HOST=escolinhapro_app_postgres`, `REDIS_HOST=escolinhapro_app_redis`)
- `docker-compose.override.yml` for local-only overrides (volume mounts, debug ports) — not committed
- Production Droplet runs bare-metal (Nginx + PHP-FPM + Postgres + Redis installed directly), not Docker — keeps production ops simple for a solo founder

### Testing (Pest PHP)
Pest PHP is the sole testing framework. All tests written using Pest's expressive syntax on top of Laravel's testing utilities.

**Test structure:**
```
tests/
├── Feature/          # HTTP-level tests: routes, auth, RBAC, workflows
│   ├── Auth/
│   ├── Players/
│   ├── Evaluations/
│   ├── Payments/
│   └── Api/          # Mobile API endpoints
└── Unit/             # Isolated logic: services, helpers, value objects
    ├── TenantScope/
    ├── CategoryAssignment/
    └── AiContext/
```

**Conventions:**
- Every Feature test runs against a dedicated test database (SQLite in-memory for speed, or a separate `escolinha_test` PostgreSQL DB for tests that require pgvector)
- All tests use `RefreshDatabase` trait
- AI calls mocked via `Http::fake()` in all tests — no real OpenAI calls in CI
- Payment webhook tests use fixture payloads from Pagar.me and Stripe sandbox
- Each phase's acceptance criteria maps directly to a Pest `it()` or `test()` block

**Running tests:**
```bash
# All tests
./vendor/bin/pest

# Specific suite
./vendor/bin/pest tests/Feature/Players

# With coverage
./vendor/bin/pest --coverage
```

**CI integration:** GitHub Actions runs `./vendor/bin/pest` on every push to any branch. Merge to `main` is blocked if the suite fails.
- Structured logging via Laravel Telescope (dev) and a log aggregator like Logtail (prod)
- Error tracking via Sentry
- Uptime monitoring via Better Uptime or similar
- AI call logging: prompt, response, tokens used, latency — stored for debugging and cost auditing
