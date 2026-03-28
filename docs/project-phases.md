# Project Phases — Escolinha Pro (MVP)

## Overview

This document outlines the phased development plan for **Escolinha Pro MVP**, based on:
- User Stories (`docs/user-stores.md`)
- Database Schema (`docs/database-schema.md`)
- Project Description (`docs/project-description.md`)

Each phase contains:
- Clear deliverables
- Task breakdown
- Feature test acceptance criteria

---

# Phase 1 — Project Setup & Foundations

## 1.1 Laravel Project Initialization
- [ ] Install Laravel 13 project
- [ ] Configure PostgreSQL connection
- [ ] Setup environment variables (`.env` + `.env.docker`)
- [ ] Install Laravel Sanctum
- [ ] Install and configure Pest PHP (`pest --init`)
- [ ] Install Laravel Horizon
- [ ] Install Livewire

**Docker Compose setup:**
- [ ] Create `docker-compose.yml` with services and network `escolinhapro_network`:
  - `escolinhapro_app_fpm` — PHP 8.3-fpm + Laravel (`docker/php/Dockerfile`)
  - `escolinhapro_app_nginx` — Nginx reverse proxy (`docker/nginx/default.conf`)
  - `escolinhapro_app_postgres` — PostgreSQL (`docker/postgresql/`)
  - `escolinhapro_app_redis` — Redis (`docker/redis/redis.conf`)
  - `escolinhapro_app_queue` — Horizon queue worker via Supervisor (`docker/supervisor/supervisord.conf`)
- [ ] Create `docker/php/Dockerfile` (PHP 8.3-fpm-alpine + extensions: pdo_pgsql, redis, pcntl)
- [ ] Create `docker/nginx/default.conf` (reverse proxy to `escolinhapro_app_fpm`)
- [ ] Create `docker/postgresql/` (Dockerfile or init scripts as needed)
- [ ] Create `docker/redis/redis.conf`
- [ ] Create `docker/supervisor/supervisord.conf` (manages Horizon worker)
- [ ] Create `.env.docker` with container hostnames (`DB_HOST=escolinhapro_app_postgres`, `REDIS_HOST=escolinhapro_app_redis`)
- [ ] Add `docker-compose.override.yml` to `.gitignore` (local-only overrides)
- [ ] Confirm all services start cleanly with `docker compose up`

**Pest PHP setup:**
- [ ] Define test folder structure: `tests/Feature/` and `tests/Unit/`
- [ ] Configure `phpunit.xml` to use SQLite in-memory for default test DB
- [ ] Add `Pest.php` with global `uses(RefreshDatabase::class)` for Feature tests
- [ ] Add GitHub Actions workflow (`.github/workflows/tests.yml`) to run `./vendor/bin/pest` on every push

**Tests:**
- [ ] Application boots successfully inside Docker
- [ ] Database connection works (PostgreSQL container)
- [ ] Redis connection works
- [ ] Health check route returns 200
- [ ] `./vendor/bin/pest` runs and passes with zero tests (empty suite baseline)

---

## 1.2 Multi-Tenancy Foundation
- [ ] Create and run migrations for: `tenants`, `roles`
- [ ] Implement `tenant_id` scoping strategy on all tenant-scoped models
- [ ] Middleware to resolve and scope current tenant per request
- [ ] Helper to resolve current tenant from authenticated user
- [ ] Seed `roles`: `super_admin`, `academy_director`, `coach`, `parent`, `player`

**Tests:**
- [ ] Users cannot access data from another tenant
- [ ] Tenant is resolved correctly per request
- [ ] `super_admin` has no `tenant_id` and can access all tenants

---

## 1.3 Base UI & Design System
- [ ] Setup Blade + Livewire
- [ ] Create base layout (guest + authenticated)
- [ ] Build reusable components:
  - [ ] Button
  - [ ] Input
  - [ ] Select
  - [ ] Checkbox
  - [ ] Modal
- [ ] Define color system (brand + states)

**Tests:**
- [ ] Components render correctly
- [ ] Layout loads without errors

---

# Phase 2 — Authentication & Roles

## 2.1 Authentication System (US-1.1, US-1.2, US-1.3)
- [ ] Director public registration form (name, email, password, academy name)
- [ ] On register: create `tenant` + `user` with `academy_director` role atomically
- [ ] Email verification flow (Laravel's built-in `MustVerifyEmail`)
- [ ] Login / Logout
- [ ] Password reset via email (token valid 60 minutes)
- [ ] Redirect to role-specific dashboard after login

**Tests:**
- [ ] Director can register and tenant is provisioned
- [ ] Email verification required before dashboard access
- [ ] User can login and logout
- [ ] Invalid credentials show generic error
- [ ] Password reset flow works end-to-end
- [ ] Expired reset token returns error

---

## 2.2 Roles & Permissions (RBAC)
- [ ] Laravel Policies per resource (Player, Evaluation, TrainingSession, etc.)
- [ ] Middleware to enforce role access at route level
- [ ] Role-based dashboard redirect on login

**Tests:**
- [ ] Coach cannot access financial data
- [ ] Parent cannot access web panel routes
- [ ] Unauthorized access returns 403

---

## 2.3 Coach & Parent Invitation Flow (US-1.4, US-1.5, US-1.6)
- [ ] Create `invitations` migration
- [ ] Generate unique token on invite creation, set `expires_at`
- [ ] Send invite email via Resend (coach invite by director, parent invite on player registration, optional player invite)
- [ ] Token-based registration page (pre-fills email and role, read-only)
- [ ] Mark invitation as `accepted_at` on completion
- [ ] Role and tenant assigned to new user on acceptance
- [ ] Expired/used token returns clear error

**Tests:**
- [ ] Invite link pre-fills email and is read-only
- [ ] User created with correct role and tenant after acceptance
- [ ] Token cannot be reused after acceptance
- [ ] Expired token returns error message
- [ ] Director can resend a pending invitation

---

# Phase 3 — Academy & Player Management

## 3.1 Academy Setup (US-2.1, US-2.2, US-2.3)
- [ ] Academy profile form (name, logo, address, city, phone, primary color)
- [ ] Logo upload to DO Spaces
- [ ] Age categories CRUD (pre-seeded with Sub-7 through Sub-17 based on CBF rules)
- [ ] Toggle categories active/inactive per tenant
- [ ] Monthly fee configuration per category

**Tests:**
- [ ] Academy profile saved and visible only within tenant
- [ ] Default CBF categories available after tenant creation
- [ ] Monthly fee saved per category

---

## 3.2 Player Registration (US-3.1, US-3.2)
- [ ] Create player form (name, DOB, position, dominant foot, photo, guardian name, guardian email, guardian phone)
- [ ] Seed `positions` and `dominant_feet` lookup tables
- [ ] Auto-assign player to correct category based on DOB
- [ ] Photo upload to DO Spaces (`players.photo_path`)
- [ ] Parent invitation triggered automatically on player creation
- [ ] CSV import: downloadable template, row validation, partial import, error report

**Tests:**
- [ ] Player created successfully via form
- [ ] Category assigned correctly based on DOB
- [ ] Parent invite triggered on player registration
- [ ] CSV import creates players and sends parent invites
- [ ] CSV with invalid rows returns partial success + error list

---

## 3.3 Coach Assignment (US-3.3)
- [ ] Assign coaches to one or more categories (`coach_category` pivot)
- [ ] Coach sees only sessions, players, and evaluations within assigned categories

**Tests:**
- [ ] Coach sees only assigned categories
- [ ] Removing assignment does not delete historical data

---

# Phase 4 — Training Sessions & Attendance

## 4.1 Recurring Training Schedule (US-2.4)
- [ ] Create `training_schedules` migration
- [ ] Director configures recurring slots per category (day of week, time, duration, location)
- [ ] Scheduled job generates `training_sessions` from active schedules (e.g., weekly)
- [ ] Seed `session_statuses`: `scheduled`, `in_progress`, `completed`, `cancelled`
- [ ] One-off sessions can be created manually by coach outside the schedule

**Tests:**
- [ ] Recurring sessions generated correctly from schedule
- [ ] Coach can create a one-off session outside the schedule
- [ ] Pausing a schedule stops future session generation without affecting past sessions

---

## 4.2 Training Session Management (US-4.1)
- [ ] Session list view per category (calendar + list toggle)
- [ ] Session detail page: player list, status, notes, training plan link
- [ ] Coach can transition session status: `scheduled` → `in_progress` → `completed`
- [ ] Coach can cancel a session (`cancelled` status)

**Tests:**
- [ ] Session visible to correct coaches (assigned category)
- [ ] Status transitions work correctly
- [ ] Director can view all sessions within tenant

---

## 4.3 Attendance System (US-4.2)
- [ ] Attendance marking UI: present / absent / justified per player
- [ ] Seed `attendance_statuses`: `present`, `absent`, `justified`
- [ ] Attendance saved per player per session
- [ ] Duplicate attendance records for same player/session not allowed
- [ ] Parent push notification triggered automatically when player marked absent

**Tests:**
- [ ] Attendance saved correctly for all statuses
- [ ] Cannot duplicate records for same player/session
- [ ] Absent notification created and linked to correct parent

---

## 4.4 Session Notes & Rating (US-4.3)
- [ ] Coach adds free-text notes and group performance rating (1–5) to a session
- [ ] Notes visible to director; not visible to parents or players

**Tests:**
- [ ] Notes and rating saved to session
- [ ] Notes not accessible to parent or player roles

---

# Phase 5 — Player Evaluation System

## 5.1 Evaluation CRUD (US-5.1)
- [ ] Create evaluation form (header: player, coach, date, notes)
- [ ] Seed `evaluation_metric_keys` with all fundamentals (Technical, Physical, Tactical, Attitude)
- [ ] Store scores via `evaluation_metrics` (one row per metric)
- [ ] Evaluation form renders metric keys dynamically from seed data
- [ ] AI narrative generated after evaluation save (calls OpenAI, stores in `evaluation_narratives`)
- [ ] Coach can edit narrative before finalizing

**Tests:**
- [ ] Evaluation saved with all metrics
- [ ] New metric key added via seed works without schema change
- [ ] Scores persisted per metric in `evaluation_metrics`
- [ ] AI narrative stored and editable
- [ ] Fallback if AI call fails: evaluation saved, manual text field shown

---

## 5.2 Evaluation History (US-5.2)
- [ ] Evaluations listed in reverse chronological order on player profile
- [ ] Radar chart showing latest scores per category
- [ ] Delta overlay vs previous evaluation
- [ ] Access restricted to assigned coaches and director

**Tests:**
- [ ] Evaluations sorted by date descending
- [ ] Only authorized users can view evaluations

---

# Phase 5.5 — FitSoft API Integration (pre-requisite for Phase 6)

> This phase must be completed before Phase 6. See `docs/future/integracao-fitsoft.md` for full context.
> The exercise library, training plan builder, and AI features in Phase 6 are **powered by FitSoft's API** — Escolinha Pro does not duplicate this infrastructure locally.

## 5.5.1 FitSoft: Secure the Existing API
- [ ] Protect all existing endpoints with `auth:sanctum` middleware
- [ ] Replace insecure `user_id` query param with `auth()->user()` in `ApiController`
- [ ] Prefix all routes with `/api/v1/`
- [ ] Configure `config/cors.php` to accept requests from Escolinha Pro domain

## 5.5.2 FitSoft: Create API Resources & Missing Endpoints
- [ ] Create `ExerciseResource`, `TrainingPlanResource`, `TrainingSessionResource`
- [ ] Ensure all required endpoints exist (see `integracao-fitsoft.md` § 1.3)

## 5.5.3 FitSoft: Generate Service Account Token
- [ ] Create service user `escolinha-pro@fitsoft.app` in FitSoft
- [ ] Generate Personal Access Token via tinker and copy to Escolinha Pro `.env` as `FITSOFT_API_KEY`

## 5.5.4 Escolinha Pro: HTTP Client Setup
- [ ] Add `fitsoft` block to `config/services.php` (`url`, `api_key`)
- [ ] Add `FITSOFT_API_URL` and `FITSOFT_API_KEY` to `.env` and `.env.example`
- [ ] Create `app/Services/FitSoftApiService.php` with methods: `getExercises()`, `getExercise()`, `createTrainingPlan()`, `generateTrainingPlan()`, `importPlanFromPhoto()`
- [ ] Create `app/Exceptions/FitSoftApiException.php`
- [ ] Add `HasApiTokens` trait to `User` model (required for Sanctum token issuance if needed)

**Tests:**
- [ ] `FitSoftApiService::getExercises()` returns exercises (use `Http::fake()`)
- [ ] `FitSoftApiService::generateTrainingPlan()` returns AI-generated plan (use `Http::fake()`)
- [ ] `FitSoftApiException` thrown on non-2xx response
- [ ] Unavailable API shows user-facing error in pt-BR

---

# Phase 6 — Training Plans

> **FitSoft dependency:** All exercise and plan data lives in FitSoft. Escolinha Pro stores only local references (`fitsoft_plan_id`) and assignment metadata. Phase 5.5 must be complete before starting this phase.

## 6.1 Exercise Library via FitSoft API (US-6.1)
- [ ] Livewire component fetches exercises via `FitSoftApiService::getExercises()` (no local exercise table)
- [ ] Support filters: objective (`Technical`, `Physical`, `Tactical`) and free-text search
- [ ] Exercise detail view loads from `FitSoftApiService::getExercise($id)` (includes video link)

**Tests:**
- [ ] Exercise list renders with `Http::fake()` returning mocked FitSoft response
- [ ] Filters pass correct query params to the API call
- [ ] Exercise detail displays video link from API response

---

## 6.2 Training Plan Builder (US-6.2, US-6.3)
- [ ] Seed `training_plan_statuses`: `draft`, `published`
- [ ] Create plan form (title, description, category or player target, status)
- [ ] Exercise selector in wizard calls `FitSoftApiService::getExercises()` to populate options
- [ ] On save: send assembled plan to FitSoft via `FitSoftApiService::createTrainingPlan()`; persist local record with `fitsoft_plan_id` reference
- [ ] AI-generated plan: call `FitSoftApiService::generateTrainingPlan($playerProfile)`; persist result locally
- [ ] Photo import: call `FitSoftApiService::importPlanFromPhoto($base64Image)`; persist result locally
- [ ] Publish plan: visible to assigned players (15+) in their portal
- [ ] Duplicate plan: creates new local draft, independent of original (does not call FitSoft)

**Tests:**
- [ ] Plan saved locally with correct `fitsoft_plan_id` after API call (use `Http::fake()`)
- [ ] AI generation stores returned plan locally
- [ ] Photo import stores returned plan locally
- [ ] Published plan visible to assigned players
- [ ] Duplicate creates independent local draft without hitting FitSoft API

---

## 6.3 Plan Assignment (US-6.3)
- [ ] Assign plan to one or more players or to an entire category
- [ ] Player (15+) sees assigned plan immediately after assignment

**Tests:**
- [ ] Plan visible to player after assignment
- [ ] Category assignment makes plan visible to all players in that category (15+)

---

# Phase 7 — Payments

## 7.1 Parent Payment Integration (US-8.4)
- [ ] Integrate Pagar.me for Pix, boleto, and credit card
- [ ] Seed `payment_statuses`: `pending`, `paid`, `overdue`, `failed`, `cancelled`
- [ ] Seed `payment_providers`: `pagarme`, `stripe`
- [ ] Create payment records per player per month
- [ ] Webhook processing with idempotency keys

**Tests:**
- [ ] Payment created with correct status
- [ ] Status updated correctly on webhook
- [ ] Duplicate webhooks handled idempotently

---

## 7.2 Payment Status & Reminders (US-8.3, US-8.4)
- [ ] Track due / paid / overdue per payment
- [ ] Overdue banner shown on parent's child dashboard
- [ ] Push notification triggered for upcoming due and overdue payments
- [ ] Automated dunning: 3-notification sequence for overdue

**Tests:**
- [ ] Payment transitions to `overdue` after due date
- [ ] Notifications triggered at correct points in dunning sequence

---

# Phase 8 — Parent & Player Portal (API)

## 8.1 Mobile API Authentication (Sanctum)
- [ ] Token-based auth endpoints (login, logout, refresh)
- [ ] Parent and player roles enforced at API middleware level

**Tests:**
- [ ] Authenticated requests succeed
- [ ] Unauthenticated requests return 401
- [ ] Parent cannot access other players' data

---

## 8.2 Parent Dashboard API (US-8.1, US-8.2)
- [ ] Child profile endpoint (summary: attendance, evaluation scores, fee status)
- [ ] Attendance history endpoint (by month, with status breakdown)
- [ ] Multi-child support: parent can switch between child profiles

**Tests:**
- [ ] Data returned correctly and scoped to parent's children
- [ ] Monthly attendance summary calculated correctly

---

## 8.3 Player Portal (US-9.1, US-9.2, US-9.3)
- [ ] Training schedule endpoint (upcoming sessions for player's category)
- [ ] Check-in endpoint: available only within session window (30 min before to 1 hour after start)
- [ ] Evaluations and training plan endpoint
- [ ] Web panel routes for player role (same data, Livewire views)

**Tests:**
- [ ] Check-in only allowed within valid time window
- [ ] Check-in disabled if coach already recorded attendance
- [ ] Player cannot view other players' evaluations

---

# Phase 9 — AI Features

## 9.1 Monthly Reports (US-7.1, US-7.2)
- [ ] Scheduled job (1st of month) generates one report per active player
- [ ] RAG context: evaluation history, attendance, training plans, coach notes
- [ ] Report stored with `ai_generated_text` and empty `edited_text`, status `draft`
- [ ] Director dashboard: list of draft reports with edit and send actions
- [ ] On send: push notification + email to parent; `sent_at` recorded
- [ ] Fallback message if player has no evaluations that month

**Tests:**
- [ ] Job generates one report per active player
- [ ] `edited_text` saved independently of `ai_generated_text`
- [ ] `sent_at` populated only after explicit send action
- [ ] Parent receives notification on send

---

## 9.2 Training Session Suggestions (US-7.3)
> **FitSoft dependency:** AI generation is delegated to `FitSoftApiService::generateTrainingPlan()` with evaluation gap context as input. Phase 5.5 must be complete.

- [ ] "Suggest Session" action: input = category + recent weak evaluation areas (from Phase 5 data)
- [ ] Call `FitSoftApiService::generateTrainingPlan($playerProfile)` passing evaluation gaps as context
- [ ] Returns 3 exercise suggestions with name, objective, and rationale in Portuguese
- [ ] Coach can add any suggestion directly to a training plan (triggers Phase 6 plan builder)

**Tests:**
- [ ] Suggestions returned based on input data (use `Http::fake()` for FitSoft API)
- [ ] Response is stateless (not stored)

---

## 9.3 Automated Absence Alert Draft (US-7.4)
- [ ] Detect 3+ consecutive absences per player
- [ ] AI generates draft message in Portuguese to guardian
- [ ] Draft shown to coach for review before sending
- [ ] Sent alerts logged on player profile

**Tests:**
- [ ] Alert triggered after 3rd consecutive absence
- [ ] Draft message generated in Portuguese
- [ ] Dismissed alerts not re-triggered for same streak

---

# Phase 10 — Admin Panel

## 10.1 Tenant Management (US-10.1)
- [ ] List all tenants with: name, director email, MRR, created date, last active, status
- [ ] Search and filter by status (active / suspended / trial)
- [ ] Suspend and reactivate tenant (all tenant users see suspended screen)
- [ ] All status changes written to `audit_logs`

**Tests:**
- [ ] Tenant status updated correctly
- [ ] Suspended tenant users cannot access the platform
- [ ] Status change written to audit_logs with correct action and user_id

---

## 10.2 Tenant Impersonation (US-10.2)
- [ ] Super admin can impersonate any tenant
- [ ] Persistent "Impersonating [Academy]" banner during impersonation
- [ ] All actions during impersonation logged in `audit_logs`
- [ ] Exit impersonation returns to super admin view

**Tests:**
- [ ] Impersonated session is scoped to tenant's data
- [ ] Impersonation start and exit written to audit_logs
- [ ] Super admin cannot permanently elevate tenant access

---

## 10.3 Feature Flags (US-10.3)
- [ ] Toggle per-tenant flags: `ai_features`, `parent_payment_collection`, `beta_features`
- [ ] Changes take effect immediately
- [ ] Disabled features hidden from tenant UI
- [ ] Flag changes written to `audit_logs`

**Tests:**
- [ ] Feature enabled/disabled correctly per tenant
- [ ] Disabled AI feature hides AI-related UI for that tenant
- [ ] Change logged in audit_logs

---

## 10.4 AI Usage Monitoring (US-10.4)
- [ ] Dashboard: tokens used and estimated cost per tenant, filterable by date and feature type
- [ ] Manual trigger: super admin can generate a report for a specific player
- [ ] Alert threshold: notify super admin if tenant exceeds configured token limit per month

**Tests:**
- [ ] AI usage logged per feature call
- [ ] Cost calculated and stored correctly
- [ ] Manual trigger generates and stores report

---

## 10.5 Content Management
- [ ] Global exercise library management (add, edit, categorize)
- [ ] CBF age category definitions management
- [ ] Email and push notification template management

---

# Phase 11 — Polish & Production Readiness

## 11.1 Performance
- [ ] Add indexes: `tenant_id` (all tables), `player_id`, `category_id`, `created_at` (high-read), `evaluation_id` on `evaluation_metrics`, `token` on `invitations`, `schedule_id` and `session_date` on `training_sessions`, `user_id` and `action` on `audit_logs`
- [ ] Redis caching for dashboard aggregations (attendance counts, payment summaries) with 5-min TTL
- [ ] Queue all heavy operations: AI calls, payment processing, bulk notifications

**Tests:**
- [ ] Key queries use indexes (EXPLAIN ANALYZE)
- [ ] Cache hits reduce repeated query load

---

## 11.2 Security
- [ ] Encrypt PII fields at rest: `players.date_of_birth`, guardian CPF
- [ ] Rate limiting on all public-facing and API endpoints
- [ ] LGPD: data export and deletion endpoints
- [ ] Laravel Policies verified for all resource types

**Tests:**
- [ ] Encrypted fields unreadable in raw DB query
- [ ] Rate limiting enforced on public endpoints
- [ ] Data export returns only the requesting tenant's data

---

## 11.3 CI/CD
- [ ] GitHub Actions pipeline: run `./vendor/bin/pest` on every push to any branch
- [ ] Block merge to `main` if test suite fails
- [ ] Deploy job: SSH into Droplet on merge to `main` (`git pull` + `composer install` + `php artisan migrate --force` + `php artisan horizon:restart`)
- [ ] Add `.env.ci` with SQLite in-memory config for fast CI test runs

**Tests:**
- [ ] Build passes on clean checkout
- [ ] Full Pest suite runs green in CI
- [ ] Deploy script runs without errors on staging push

---

# Summary

This phased plan ensures:
- Fast MVP delivery (Phases 1–5 cover core operations)
- Monetization readiness (Phase 7)
- Differentiation via AI (Phase 9)
- Full admin control and auditability (Phase 10)
- Scalability and maintainability (Phase 11)
