# Database Schema — Escolinha Pro (MVP)

## Overview

This schema follows:
- Multi-tenancy via `tenant_id`
- Laravel best practices (UUIDs, timestamps, soft deletes)
- **No enums** → all categorical data normalized into lookup tables
- Designed for scalability and clean relationships

---

## Tables

### tenants
```
Table tenants {
  id uuid [pk]
  name varchar
  logo_path varchar
  address varchar
  city varchar
  state varchar
  phone varchar
  primary_color varchar
  created_at timestamp
  updated_at timestamp
}
```

---

### roles (lookup)
```
Table roles {
  id uuid [pk]
  name varchar
  slug varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `super_admin`, `academy_director`, `coach`, `parent`, `player`

---

### invitations
```
Table invitations {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  role_id uuid [ref: > roles.id]
  email varchar
  token varchar [unique]
  accepted_at timestamp
  expires_at timestamp
  created_at timestamp
  updated_at timestamp
}
```

---

### users
```
Table users {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id, null]
  role_id uuid [ref: > roles.id]
  name varchar
  email varchar [unique]
  email_verified_at timestamp
  password varchar
  phone varchar
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp
}
```

> `tenant_id` is null only for `super_admin`. All other roles must belong to a tenant.

---

### categories (age groups)
```
Table categories {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  name varchar
  min_age int
  max_age int
  monthly_fee decimal
  active boolean
  created_at timestamp
  updated_at timestamp
}
```

---

### positions (lookup)
```
Table positions {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `Goleiro`, `Zagueiro`, `Lateral`, `Volante`, `Meia`, `Atacante`

---

### dominant_feet (lookup)
```
Table dominant_feet {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `Direito`, `Esquerdo`, `Ambidestro`

---

### players
```
Table players {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  user_id uuid [ref: > users.id, null]
  category_id uuid [ref: > categories.id]
  position_id uuid [ref: > positions.id]
  dominant_foot_id uuid [ref: > dominant_feet.id]
  name varchar
  date_of_birth date
  photo_path varchar
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp
}
```

> `user_id` is null until the player (15+) accepts their invitation and creates an account.

---

### parent_player
```
Table parent_player {
  id uuid [pk]
  parent_id uuid [ref: > users.id]
  player_id uuid [ref: > players.id]
  relationship varchar
}
```

---

### coach_category
```
Table coach_category {
  id uuid [pk]
  coach_id uuid [ref: > users.id]
  category_id uuid [ref: > categories.id]
}
```

---

## Training

### training_schedules
```
Table training_schedules {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  category_id uuid [ref: > categories.id]
  coach_id uuid [ref: > users.id]
  day_of_week int
  start_time time
  duration_minutes int
  location varchar
  active boolean
  created_at timestamp
  updated_at timestamp
}
```

> `day_of_week`: 0 = Sunday … 6 = Saturday. Each row represents one recurring slot (e.g., every Tuesday at 19:00). A category can have multiple rows for multiple weekly slots.

---

### session_statuses (lookup)
```
Table session_statuses {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `scheduled`, `in_progress`, `completed`, `cancelled`

---

### training_sessions
```
Table training_sessions {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  category_id uuid [ref: > categories.id]
  coach_id uuid [ref: > users.id]
  schedule_id uuid [ref: > training_schedules.id, null]
  status_id uuid [ref: > session_statuses.id]
  session_date date
  start_time time
  duration_minutes int
  location varchar
  notes text
  group_performance_rating int
  created_at timestamp
  updated_at timestamp
}
```

> `schedule_id` is null for one-off sessions created manually outside the recurring schedule.
> `group_performance_rating`: 1–5 scale, optional, set by coach after session.

---

### attendance_statuses (lookup)
```
Table attendance_statuses {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `present`, `absent`, `justified`

---

### attendance
```
Table attendance {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  training_session_id uuid [ref: > training_sessions.id]
  player_id uuid [ref: > players.id]
  status_id uuid [ref: > attendance_statuses.id]
  created_at timestamp
  updated_at timestamp
}
```

---

## Player Development

### evaluations
```
Table evaluations {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  player_id uuid [ref: > players.id]
  coach_id uuid [ref: > users.id]
  notes text
  created_at timestamp
  updated_at timestamp
}
```

> **Design note (MVP):** Evaluation scores are stored in a separate `evaluation_metrics` table rather than fixed columns. This allows adding new football fundamentals (e.g., "aerial duels") without schema migrations. The tradeoff is slightly more complex queries, which is acceptable at MVP scale.

---

### evaluation_metric_keys (lookup)
```
Table evaluation_metric_keys {
  id uuid [pk]
  key varchar
  label varchar
  category varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `passing`, `dribbling`, `finishing`, `ball_control`, `heading` (Technical) · `speed`, `endurance`, `strength` (Physical) · `positioning`, `decision_making`, `off_ball_movement` (Tactical) · `effort`, `coachability`, `teamwork` (Attitude)

---

### evaluation_metrics
```
Table evaluation_metrics {
  id uuid [pk]
  evaluation_id uuid [ref: > evaluations.id]
  metric_key_id uuid [ref: > evaluation_metric_keys.id]
  score int
}
```

---

### evaluation_narratives
```
Table evaluation_narratives {
  id uuid [pk]
  evaluation_id uuid [ref: > evaluations.id]
  ai_generated_text text
  edited_text text
  created_at timestamp
}
```

---

## Training Plans

### exercise_objectives (lookup)
```
Table exercise_objectives {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `Technical`, `Physical`, `Tactical`

---

### exercises
```
Table exercises {
  id uuid [pk]
  name varchar
  description text
  objective_id uuid [ref: > exercise_objectives.id]
  video_url varchar
  created_at timestamp
  updated_at timestamp
}
```

---

### training_plan_statuses (lookup)
```
Table training_plan_statuses {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `draft`, `published`

---

### training_plans
```
Table training_plans {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  coach_id uuid [ref: > users.id]
  status_id uuid [ref: > training_plan_statuses.id]
  category_id uuid [ref: > categories.id, null]
  player_id uuid [ref: > players.id, null]
  title varchar
  description text
  created_at timestamp
  updated_at timestamp
}
```

> Either `category_id` or `player_id` must be set (not both). Category-level plans are visible to all players in that category (15+). Player-level plans are visible only to that player.

---

### training_plan_exercises
```
Table training_plan_exercises {
  id uuid [pk]
  training_plan_id uuid [ref: > training_plans.id]
  exercise_id uuid [ref: > exercises.id]
  order_index int
  duration_minutes int
  notes varchar
}
```

---

## Payments

### payment_statuses (lookup)
```
Table payment_statuses {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `pending`, `paid`, `overdue`, `failed`, `cancelled`

---

### payment_providers (lookup)
```
Table payment_providers {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `pagarme`, `stripe`

---

### payments
```
Table payments {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  parent_id uuid [ref: > users.id]
  player_id uuid [ref: > players.id]
  status_id uuid [ref: > payment_statuses.id]
  provider_id uuid [ref: > payment_providers.id]
  amount decimal
  due_date date
  paid_at timestamp
  external_id varchar
  created_at timestamp
  updated_at timestamp
}
```

---

### subscription_statuses (lookup)
```
Table subscription_statuses {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `trialing`, `active`, `past_due`, `cancelled`, `suspended`

---

### subscriptions
```
Table subscriptions {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  status_id uuid [ref: > subscription_statuses.id]
  provider_id uuid [ref: > payment_providers.id]
  external_id varchar
  current_period_end timestamp
  created_at timestamp
  updated_at timestamp
}
```

---

## AI & Communication

### monthly_reports
```
Table monthly_reports {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  player_id uuid [ref: > players.id]
  reference_month date
  ai_generated_text text
  edited_text text
  sent_at timestamp
  created_at timestamp
}
```

---

### notification_types (lookup)
```
Table notification_types {
  id uuid [pk]
  name varchar
  created_at timestamp
  updated_at timestamp
}
```

> Seeded with: `absence`, `evaluation`, `monthly_report`, `payment_due`, `payment_overdue`

---

### notifications
```
Table notifications {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  user_id uuid [ref: > users.id]
  type_id uuid [ref: > notification_types.id]
  title varchar
  body text
  read_at timestamp
  created_at timestamp
}
```

---

## System

### feature_flags
```
Table feature_flags {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  key varchar
  enabled boolean
  created_at timestamp
  updated_at timestamp
}
```

> Keys: `ai_features`, `parent_payment_collection`, `beta_features`

---

### ai_usage_logs
```
Table ai_usage_logs {
  id uuid [pk]
  tenant_id uuid [ref: > tenants.id]
  feature varchar
  tokens_used int
  cost decimal
  created_at timestamp
}
```

> `feature` values: `evaluation_narrative`, `monthly_report`, `session_suggestion`, `absence_alert`

---

### audit_logs
```
Table audit_logs {
  id uuid [pk]
  user_id uuid [ref: > users.id]
  tenant_id uuid [ref: > tenants.id, null]
  action varchar
  entity_type varchar
  entity_id uuid
  payload json
  ip_address varchar
  created_at timestamp
}
```

> Captures sensitive actions: tenant impersonation, tenant suspend/reactivate, feature flag changes, manual AI report triggers. `tenant_id` is null when the action is performed by `super_admin` at the platform level.

---

## Notes

- All categorical fields implemented via **lookup tables (no enums)**
- All file uploads use `_path` convention (DO Spaces ready)
- Use `deleted_at` for soft deletes in critical tables (`users`, `players`)
- Evaluation scores stored in `evaluation_metrics` (flexible) — new fundamentals can be added via seed, not migration
- `training_schedules` defines the recurring weekly calendar; `training_sessions` are the individual instances generated from it
- `monthly_reports.edited_text` allows director review before sending to parent; `sent_at` tracks delivery
- `training_sessions.location` is free-text in MVP, reserved for FK to a `fields` table in a future module
- `training_plans`: either `category_id` or `player_id` must be set, not both
- Index recommendations:
  - `tenant_id` on all tenant-scoped tables
  - `player_id`, `category_id`, `created_at` on high-read tables
  - `evaluation_id` on `evaluation_metrics`
  - `token` on `invitations` (unique lookup on invite acceptance)
  - `schedule_id`, `session_date` on `training_sessions`
  - `user_id`, `action` on `audit_logs`
