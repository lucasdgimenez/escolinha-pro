# User Stories — Escolinha Pro (MVP)

## Overview

This document contains user stories for Escolinha Pro, a football-specialized SaaS platform for managing youth football academies (*escolinhas*) in Brazil.

**User Types:**
- **Academy Director** (`academy_director`) — Owner or manager of an academy. Accessed via web panel.
- **Coach** (`coach`) — Technical staff managing sessions, evaluations, and training plans. Accessed via web panel.
- **Parent** (`parent` / `responsável`) — Guardian of an enrolled player. Accessed via mobile app only.
- **Player** (`player` / `atleta`) — Optional role for players aged 15+. Accessed via mobile app and web.
- **Super Admin** (`super_admin`) — Platform owner. Full access to all tenants. Accessed via web panel.

---

## 1. Authentication & Registration

### US-1.1: Director Public Registration
**As an** Academy Director
**I want to** register my academy via a public sign-up page
**So that** I can start managing my escolinha on the platform

**Acceptance Criteria:**
- [ ] Registration form collects: director name, email, password, password confirmation, academy name
- [ ] Email must be unique across the platform
- [ ] Password must be at least 8 characters
- [ ] A new tenant is provisioned for the academy upon successful registration
- [ ] Director is assigned the `academy_director` role automatically
- [ ] Email verification is required before accessing the dashboard
- [ ] After verification, director is redirected to the academy setup wizard

**Expected Result:** Director account and academy tenant created, ready for initial setup.

---

### US-1.2: Login / Logout
**As a** Registered User (any role)
**I want to** log in with email and password and log out at any time
**So that** I can securely access my account

**Acceptance Criteria:**
- [ ] Login form accepts email and password
- [ ] Invalid credentials show a generic error message (no hint about which field is wrong)
- [ ] Successful login redirects to the role-specific dashboard
- [ ] "Remember me" option extends session
- [ ] Logout clears the session and redirects to login page
- [ ] Session expires after configured inactivity period

**Expected Result:** User can access and leave their account securely.

---

### US-1.3: Password Reset
**As a** Registered User
**I want to** reset my password via email
**So that** I can regain access if I forget my credentials

**Acceptance Criteria:**
- [ ] "Forgot password" link available on the login page
- [ ] User enters email and receives a reset link if the account exists
- [ ] Reset link is valid for 60 minutes
- [ ] Clicking an expired link shows a clear error and option to request a new one
- [ ] New password must meet minimum security requirements
- [ ] After reset, user is redirected to login

**Expected Result:** User regains account access securely without exposing account existence.

---

### US-1.4: Coach Accepts Invitation
**As a** Coach
**I want to** accept an invitation sent by a director
**So that** I can create my account and access my assigned categories

**Acceptance Criteria:**
- [ ] Invitation email contains a unique, time-limited token link
- [ ] Clicking the link opens a registration form with email pre-filled and read-only
- [ ] Coach sets name and password
- [ ] On submission, account is created with `coach` role linked to the inviting academy (tenant)
- [ ] Token is marked as used and cannot be reused
- [ ] Expired token shows a clear error and prompts director to re-invite
- [ ] After registration, coach is redirected to their dashboard

**Expected Result:** Coach account created and scoped to the correct academy.

---

### US-1.5: Parent Accepts Invitation
**As a** Parent
**I want to** accept an invitation triggered when my child is enrolled
**So that** I can create my account and monitor my child's progress via the mobile app

**Acceptance Criteria:**
- [ ] Invitation is triggered automatically when a player is registered with a guardian email
- [ ] Invitation delivered via email (and optionally WhatsApp/SMS link)
- [ ] Registration form pre-fills email and shows the enrolled child's name
- [ ] Parent sets name and password
- [ ] Account created with `parent` role, linked to the child's player profile
- [ ] Token is single-use and expires after 72 hours
- [ ] After registration, parent is guided to download the mobile app

**Expected Result:** Parent account created and linked to their child's profile.

---

### US-1.6: Player Accepts Invitation (15+)
**As a** Player aged 15 or older
**I want to** accept an invitation to create my own account
**So that** I can view my training plans, evaluations, and check in to sessions

**Acceptance Criteria:**
- [ ] Director or coach can optionally send a player invitation from the player's profile
- [ ] Invitation email pre-fills player name and email
- [ ] Player sets a password
- [ ] Account created with `player` role, linked to their existing player profile
- [ ] Player can access the platform via mobile app and web panel
- [ ] Token is single-use and expires after 72 hours

**Expected Result:** Player account created with access to their own data only.

---

## 2. Academy Setup

### US-2.1: Configure Academy Profile
**As an** Academy Director
**I want to** fill in my academy's profile details
**So that** the platform reflects my brand and operational information

**Acceptance Criteria:**
- [ ] Director can set: academy name, logo, city, address, phone, and primary color
- [ ] Logo upload supports JPG and PNG, max 2MB
- [ ] Changes are saved immediately and reflected across the platform
- [ ] Academy profile is only visible to users within the same tenant

**Expected Result:** Academy profile configured and ready for operational use.

---

### US-2.2: Configure Age Categories
**As an** Academy Director
**I want to** configure the age categories offered by my academy
**So that** players are correctly segmented by CBF age groups

**Acceptance Criteria:**
- [ ] Default categories pre-loaded: Sub-7 through Sub-17 (following CBF rules)
- [ ] Director can activate or deactivate categories for their academy
- [ ] Director can define a custom name per category (e.g., "Frangos" for Sub-7)
- [ ] Each category shows the birth year range based on CBF rules for the current season
- [ ] Players are auto-assigned to categories based on date of birth

**Expected Result:** Academy categories configured and aligned with CBF standards.

---

### US-2.3: Define Monthly Fees per Category
**As an** Academy Director
**I want to** set a monthly fee for each active category
**So that** parents are billed the correct amount based on their child's age group

**Acceptance Criteria:**
- [ ] Director sets a BRL monthly fee per category
- [ ] Fee is shown in the parent's payment screen
- [ ] Changing a fee does not retroactively affect existing payment records
- [ ] Categories with no fee defined cannot trigger payment collection

**Expected Result:** Fee structure configured per age group.

---

### US-2.4: Set Recurring Training Schedule
**As an** Academy Director
**I want to** define a recurring weekly training schedule per category
**So that** coaches and parents always know when sessions occur

**Acceptance Criteria:**
- [ ] Director selects day(s) of the week and time for each category
- [ ] Each entry includes: field/location, start time, duration
- [ ] Sessions are automatically generated based on the schedule
- [ ] Coach assigned to the category is notified of new sessions
- [ ] Director can pause or update the schedule without deleting past sessions

**Expected Result:** Training calendar auto-populated based on recurring schedule.

---

### US-2.5: Invite Coaches
**As an** Academy Director
**I want to** invite coaches via email
**So that** they can access the system and manage their assigned categories

**Acceptance Criteria:**
- [ ] Director enters coach email and optionally assigns one or more categories
- [ ] Invitation email is sent immediately
- [ ] Invitation shows as "Pending" in the coaches list until accepted
- [ ] Director can resend or revoke a pending invitation
- [ ] Once accepted, coach appears in the active coaches list with their assigned categories

**Expected Result:** Coach receives access to the platform scoped to their academy.

---

## 3. Player Management

### US-3.1: Register Player Manually
**As an** Academy Director or Coach
**I want to** register a new player through a form
**So that** the player is onboarded into the system quickly

**Acceptance Criteria:**
- [ ] Form collects: full name, date of birth, position, dominant foot, photo (optional), guardian name, guardian email, guardian phone
- [ ] Category is automatically assigned based on date of birth (editable if needed)
- [ ] Photo upload supports JPG and PNG, max 5MB, stored in DO Spaces
- [ ] Guardian email triggers a parent invitation automatically upon save
- [ ] Player profile is immediately visible to assigned coaches

**Expected Result:** Player registered and parent invite sent in a single step.

---

### US-3.2: Import Players via CSV
**As an** Academy Director
**I want to** import players in bulk via a CSV file
**So that** I can migrate my existing roster efficiently

**Acceptance Criteria:**
- [ ] System provides a downloadable CSV template with required columns
- [ ] Required columns: name, date_of_birth, position, dominant_foot, guardian_name, guardian_email
- [ ] Upload validates each row before importing
- [ ] Rows with missing required fields are flagged with specific error messages
- [ ] Valid rows are imported; invalid rows are listed in a downloadable error report
- [ ] Partial imports are allowed (valid rows succeed even if some fail)
- [ ] Parent invitations are sent for all successfully imported players with a guardian email

**Expected Result:** Bulk player import with clear feedback on successes and failures.

---

### US-3.3: Assign Coach to Category
**As an** Academy Director
**I want to** assign coaches to age categories
**So that** each coach sees only the players and sessions relevant to their group

**Acceptance Criteria:**
- [ ] Director selects a coach and assigns one or more categories
- [ ] Coach can be assigned to multiple categories
- [ ] A category can have multiple coaches
- [ ] Coach immediately gains access to sessions, players, and evaluations within assigned categories
- [ ] Removing an assignment does not delete historical data

**Expected Result:** Coaches have properly scoped access to their categories.

---

## 4. Training Sessions

### US-4.1: View and Open a Scheduled Session
**As a** Coach
**I want to** open a session from my schedule
**So that** I can manage attendance and document what happened

**Acceptance Criteria:**
- [ ] Coach sees upcoming sessions for their assigned categories in a calendar or list view
- [ ] Sessions auto-generated from the recurring schedule are listed
- [ ] Coach can open a session to view its details and player list
- [ ] Session status transitions: `scheduled` → `in_progress` → `completed`
- [ ] Coach can add a one-off session outside the recurring schedule if needed

**Expected Result:** Coach has clear visibility of sessions and can act on them.

---

### US-4.2: Mark Attendance
**As a** Coach
**I want to** mark each player as present, absent, or justified absence
**So that** attendance is accurately recorded for every session

**Acceptance Criteria:**
- [ ] Player list for the session shows all active players in the category
- [ ] Coach marks each player: `present`, `absent`, or `justified`
- [ ] Attendance can be updated until the session is marked as completed
- [ ] Parents of absent players receive a push notification automatically
- [ ] Attendance is timestamped and stored per session
- [ ] Duplicate attendance records for the same player/session are not allowed

**Expected Result:** Attendance recorded and parents of absent players notified.

---

### US-4.3: Add Session Notes
**As a** Coach
**I want to** add notes and a group performance rating after a session
**So that** there is a documented record of what was practiced

**Acceptance Criteria:**
- [ ] Coach can write free-text session notes (optional)
- [ ] Coach can rate overall group performance on a 1–5 scale (optional)
- [ ] Notes are saved to the session record
- [ ] Notes are visible to the academy director
- [ ] Notes are not visible to parents or players

**Expected Result:** Session documented with coach's observations.

---

## 5. Player Evaluation

### US-5.1: Create Player Evaluation
**As a** Coach
**I want to** evaluate a player across technical, physical, tactical, and attitude dimensions
**So that** I can track their development over time

**Acceptance Criteria:**
- [ ] Coach opens a new evaluation from the player's profile
- [ ] Evaluation form dynamically renders metric keys from seeded data (no hardcoded fields)
- [ ] Metrics grouped by category: Technical (passing, dribbling, finishing, ball control, heading), Physical (speed, endurance, strength), Tactical (positioning, decision-making, off-ball movement), Attitude (effort, coachability, teamwork)
- [ ] Each metric scored on a 1–5 scale
- [ ] Coach adds optional free-text observations
- [ ] AI generates a 2–3 sentence narrative after saving (editable before final save)
- [ ] Evaluation is timestamped and linked to the coach who created it

**Expected Result:** Evaluation saved with metrics, observations, and AI narrative.

---

### US-5.2: View Evaluation History
**As a** Coach
**I want to** view a player's past evaluations
**So that** I can monitor progress and identify trends

**Acceptance Criteria:**
- [ ] Evaluations listed in reverse chronological order on the player's profile
- [ ] Radar chart visualizes the latest evaluation scores per category
- [ ] Overlay option shows delta between current and previous evaluation
- [ ] Only coaches assigned to the player's category can view evaluations
- [ ] Director has read access to all evaluations within the tenant

**Expected Result:** Coach can see how a player has evolved over time.

---

### US-5.3: AI Evaluation Narrative
**As a** Coach
**I want to** receive an AI-generated summary after completing an evaluation
**So that** I can save time writing feedback in Portuguese

**Acceptance Criteria:**
- [ ] AI narrative is generated automatically after evaluation scores are submitted
- [ ] Narrative is written in Brazilian Portuguese, 2–3 sentences, grounded in the player's evaluation delta and history (RAG)
- [ ] Coach can edit the narrative before finalizing the evaluation
- [ ] If the AI call fails, the evaluation is still saved and a manual text field is shown
- [ ] AI prompt, response, and token count are stored for cost attribution

**Expected Result:** Coach gets a draft narrative that requires minimal editing.

---

## 6. Training Plans

### US-6.1: Browse Exercise Library
**As a** Coach
**I want to** browse a library of football exercises
**So that** I can select exercises when building a training plan

**Acceptance Criteria:**
- [ ] Exercise library is global (managed by super_admin) and available to all tenants
- [ ] Exercises organized by objective: Technical, Physical, Tactical
- [ ] Each exercise shows: name, description, duration, objective, optional video link
- [ ] Coach can search and filter by objective or keyword
- [ ] Coach can view exercise details before adding to a plan

**Expected Result:** Coach can easily find relevant exercises for their session goals.

---

### US-6.2: Create Training Plan
**As a** Coach
**I want to** create a structured training plan by composing exercises
**So that** I can deliver consistent and planned sessions

**Acceptance Criteria:**
- [ ] Coach creates a plan with: name, description, target category or individual player
- [ ] Coach adds exercises from the library with customizable order
- [ ] Each exercise in the plan can have an override duration and custom notes
- [ ] Plan can be saved as draft or published
- [ ] Published plans are visible to assigned players (15+) in their app

**Expected Result:** Training plan created and ready for assignment.

---

### US-6.3: Assign and Duplicate Training Plan
**As a** Coach
**I want to** assign a training plan to a player or category and duplicate existing plans
**So that** I can distribute plans efficiently and iterate quickly

**Acceptance Criteria:**
- [ ] Coach assigns a plan to one or more players or to an entire category
- [ ] Assigned players (15+) see the plan in their dashboard immediately
- [ ] Coach can duplicate any plan to use as a starting point for a new one
- [ ] Duplicated plan is created as a new draft, independent of the original

**Expected Result:** Plans distributed to players and reusable across categories.

---

## 7. AI Features

### US-7.1: Monthly Player Report Generation
**As the** Platform
**I want to** automatically generate a monthly progress report per active player
**So that** parents receive a personalized narrative of their child's development

**Acceptance Criteria:**
- [ ] Scheduled job runs on the 1st of each month for all active players
- [ ] Report is generated using the player's evaluation history, attendance, training plans, and coach notes as RAG context
- [ ] Output is a 150–200 word narrative in Brazilian Portuguese
- [ ] Report stored with `ai_generated_text` and empty `edited_text`
- [ ] Report status: `draft` until director sends it
- [ ] AI prompt, response, and token count stored per report for cost attribution

**Expected Result:** One draft report created per active player on the 1st of each month.

---

### US-7.2: Director Reviews and Sends Monthly Report
**As an** Academy Director
**I want to** review, edit, and send monthly AI reports to parents
**So that** I maintain control over parent communication before it goes out

**Acceptance Criteria:**
- [ ] Director sees a list of all draft reports for the current month
- [ ] Director can read and edit the `edited_text` independently of `ai_generated_text`
- [ ] Director clicks "Send" per report or bulk-sends all approved reports
- [ ] On send: push notification + email sent to parent; `sent_at` is recorded
- [ ] Reports cannot be unsent, but `edited_text` can be updated before sending
- [ ] If a player has no evaluations that month, a fallback message is generated

**Expected Result:** Director sends personalized, reviewed reports to parents with one click.

---

### US-7.3: AI Training Session Suggestions
**As a** Coach
**I want to** request AI-generated exercise suggestions for a session
**So that** I can improve session quality based on recent player evaluation gaps

**Acceptance Criteria:**
- [ ] Coach opens "Suggest Session" from a category's training plan screen
- [ ] AI returns 3 exercise suggestions based on: category age group + recent evaluation weak points
- [ ] Suggestions shown with exercise name, objective, and rationale in Portuguese
- [ ] Coach can add any suggested exercise directly to a new or existing training plan
- [ ] Suggestions are not stored (stateless — re-triggered each time)

**Expected Result:** Coach gets actionable, data-grounded session ideas in seconds.

---

### US-7.4: Automated Absence Alert Draft
**As a** Coach
**I want to** receive a draft message when a player misses 3+ consecutive sessions
**So that** I can reach out to the parent with minimal effort

**Acceptance Criteria:**
- [ ] System detects 3 or more consecutive absences for a player
- [ ] AI generates a draft message in Brazilian Portuguese addressed to the guardian
- [ ] Draft is shown to the coach for review and editing before sending
- [ ] Coach can approve and send, edit and send, or dismiss the alert
- [ ] Sent alerts are logged on the player's profile

**Expected Result:** Coach is prompted to act on at-risk players with a ready-to-send message.

---

## 8. Parent Portal (Mobile App)

### US-8.1: View Child Dashboard
**As a** Parent
**I want to** see a summary dashboard for my child when I open the app
**So that** I can quickly understand their current status

**Acceptance Criteria:**
- [ ] Dashboard shows: upcoming sessions, last attendance status, latest evaluation scores (radar chart), and monthly fee status
- [ ] If parent has multiple children, they can switch between profiles
- [ ] Data is scoped strictly to the parent's own children — no other player data visible
- [ ] Dashboard loads within 2 seconds on a standard mobile connection

**Expected Result:** Parent has a clear, immediate view of their child's engagement and progress.

---

### US-8.2: View Attendance History
**As a** Parent
**I want to** see my child's attendance history
**So that** I can monitor consistency and identify patterns

**Acceptance Criteria:**
- [ ] Attendance shown in a calendar or list view per month
- [ ] Each session shows: date, status (present / absent / justified), session category
- [ ] Summary shows total present / absent / justified for the current month
- [ ] Parent can navigate between months

**Expected Result:** Parent has a transparent view of session attendance.

---

### US-8.3: Receive Notifications
**As a** Parent
**I want to** receive push notifications for relevant events
**So that** I stay informed without needing to check the app manually

**Acceptance Criteria:**
- [ ] Push notification sent when child is marked absent
- [ ] Push notification sent when a new evaluation is recorded
- [ ] Push notification sent when the monthly AI report is available
- [ ] Push notification sent when a fee payment is due or overdue
- [ ] Parent can manage notification preferences in settings
- [ ] All notifications deep-link to the relevant section in the app

**Expected Result:** Parent is proactively informed of important events.

---

### US-8.4: Pay Monthly Fee
**As a** Parent
**I want to** pay my child's monthly fee directly in the app
**So that** payment is convenient and tracked automatically

**Acceptance Criteria:**
- [ ] Payment screen shows: child name, category, fee amount, due date, and current status
- [ ] Supported payment methods: Pix, boleto bancário, credit card (via Pagar.me)
- [ ] On successful payment: status updates to `paid`, receipt available in app
- [ ] On payment failure: error shown with retry option
- [ ] Payment history accessible for at least 12 months
- [ ] Overdue payments display a banner on the child dashboard

**Expected Result:** Parent completes payment in-app with immediate status update.

---

## 9. Player Portal (Mobile App + Web, 15+)

### US-9.1: View Training Schedule
**As a** Player (15+)
**I want to** view my upcoming training sessions
**So that** I know when and where to attend

**Acceptance Criteria:**
- [ ] Sessions shown in a calendar view, filtered to the player's category
- [ ] Each session shows: date, time, location/field, and assigned training plan (if any)
- [ ] Player can toggle between calendar and list view
- [ ] Available on both mobile app and web panel

**Expected Result:** Player always knows their next scheduled session.

---

### US-9.2: Check In to Session
**As a** Player (15+)
**I want to** check in to a session via the app
**So that** my attendance is registered directly

**Acceptance Criteria:**
- [ ] Check-in option available only during the session window (e.g., 30 min before to 1 hour after start)
- [ ] Check-in records a `present` attendance entry linked to the player
- [ ] If coach has already marked attendance, check-in is disabled and the recorded status is shown
- [ ] Check-in is available on mobile only (not web)

**Expected Result:** Player can self-register attendance without relying on the coach.

---

### US-9.3: View Evaluations and Training Plan
**As a** Player (15+)
**I want to** view my evaluations and assigned training plans
**So that** I understand how I'm developing and what to train

**Acceptance Criteria:**
- [ ] Player sees their own evaluations with radar chart and AI narrative
- [ ] Player cannot see other players' evaluations
- [ ] Assigned training plans show exercise list with descriptions and video links
- [ ] Available on both mobile app and web panel

**Expected Result:** Player has full visibility into their personal development data.

---

## 10. Super Admin

### US-10.1: View and Manage All Tenants
**As a** Super Admin
**I want to** view all registered academies and manage their status
**So that** I can monitor platform usage and enforce policies

**Acceptance Criteria:**
- [ ] List shows: academy name, director email, plan, MRR, created date, last active
- [ ] Super admin can search and filter by status (active / suspended / trial)
- [ ] Super admin can suspend or reactivate any tenant
- [ ] Suspended tenants: all users see a "suspended" screen; no data is deleted
- [ ] All status changes are audit-logged with timestamp and admin ID

**Expected Result:** Super admin has full operational control over all tenants.

---

### US-10.2: Impersonate Tenant
**As a** Super Admin
**I want to** impersonate any academy tenant for support purposes
**So that** I can reproduce issues and assist customers without asking for credentials

**Acceptance Criteria:**
- [ ] Super admin selects a tenant and clicks "Impersonate"
- [ ] Session is scoped to that tenant's data; super admin sees exactly what the director sees
- [ ] A persistent banner indicates "Impersonating [Academy Name]" at all times
- [ ] Super admin can exit impersonation at any time
- [ ] All actions performed during impersonation are audit-logged

**Expected Result:** Super admin can support customers safely and transparently.

---

### US-10.3: Toggle Feature Flags per Tenant
**As a** Super Admin
**I want to** enable or disable specific features per tenant
**So that** I can control rollout and support edge cases

**Acceptance Criteria:**
- [ ] Feature flags available: AI features, parent payment collection, beta features
- [ ] Super admin can toggle each flag per tenant individually
- [ ] Changes take effect immediately (no restart required)
- [ ] Disabled features are hidden from the tenant's UI — no error shown
- [ ] Flag history is logged (who changed what and when)

**Expected Result:** Feature rollout controlled at tenant level without code deployments.

---

### US-10.4: Monitor AI Usage and Costs
**As a** Super Admin
**I want to** track AI API usage and costs per tenant
**So that** I can ensure margin is maintained per customer

**Acceptance Criteria:**
- [ ] Dashboard shows per-tenant: number of AI calls, total tokens used, estimated cost (USD)
- [ ] Breakdown by AI feature type: evaluation narrative, monthly report, session suggestion, absence alert
- [ ] Data filterable by date range
- [ ] Super admin can manually trigger a report generation for a specific player for support purposes
- [ ] Alert threshold configurable: notify super admin if a tenant exceeds X tokens/month

**Expected Result:** Super admin has visibility into AI cost per tenant to protect margins.

---

## Appendix: User Story Status

| ID | Story | Role | Priority | Status |
|---|---|---|---|---|
| US-1.1 | Director Public Registration | Director | High | Pending |
| US-1.2 | Login / Logout | All | High | Pending |
| US-1.3 | Password Reset | All | High | Pending |
| US-1.4 | Coach Accepts Invitation | Coach | High | Pending |
| US-1.5 | Parent Accepts Invitation | Parent | High | Pending |
| US-1.6 | Player Accepts Invitation | Player | Medium | Pending |
| US-2.1 | Configure Academy Profile | Director | Medium | Pending |
| US-2.2 | Configure Age Categories | Director | High | Pending |
| US-2.3 | Define Monthly Fees | Director | High | Pending |
| US-2.4 | Set Recurring Training Schedule | Director | High | Pending |
| US-2.5 | Invite Coaches | Director | High | Pending |
| US-3.1 | Register Player Manually | Director/Coach | High | Pending |
| US-3.2 | Import Players via CSV | Director | Medium | Pending |
| US-3.3 | Assign Coach to Category | Director | High | Pending |
| US-4.1 | View and Open Scheduled Session | Coach | High | Pending |
| US-4.2 | Mark Attendance | Coach | High | Pending |
| US-4.3 | Add Session Notes | Coach | Medium | Pending |
| US-5.1 | Create Player Evaluation | Coach | High | Pending |
| US-5.2 | View Evaluation History | Coach | High | Pending |
| US-5.3 | AI Evaluation Narrative | Coach | Medium | Pending |
| US-6.1 | Browse Exercise Library | Coach | Medium | Pending |
| US-6.2 | Create Training Plan | Coach | Medium | Pending |
| US-6.3 | Assign and Duplicate Training Plan | Coach | Medium | Pending |
| US-7.1 | Monthly Report Generation | Platform | High | Pending |
| US-7.2 | Director Reviews and Sends Report | Director | High | Pending |
| US-7.3 | AI Training Session Suggestions | Coach | Medium | Pending |
| US-7.4 | Automated Absence Alert Draft | Coach | Medium | Pending |
| US-8.1 | View Child Dashboard | Parent | High | Pending |
| US-8.2 | View Attendance History | Parent | High | Pending |
| US-8.3 | Receive Notifications | Parent | High | Pending |
| US-8.4 | Pay Monthly Fee | Parent | High | Pending |
| US-9.1 | View Training Schedule | Player | Medium | Pending |
| US-9.2 | Check In to Session | Player | Medium | Pending |
| US-9.3 | View Evaluations and Training Plan | Player | Medium | Pending |
| US-10.1 | View and Manage All Tenants | Super Admin | High | Pending |
| US-10.2 | Impersonate Tenant | Super Admin | Medium | Pending |
| US-10.3 | Toggle Feature Flags | Super Admin | Medium | Pending |
| US-10.4 | Monitor AI Usage and Costs | Super Admin | Medium | Pending |
