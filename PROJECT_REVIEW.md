# IMS THAI: Project Review and Refactor Plan

Reviewed: 2026-08-25

## Scope and Current State

This is a custom PHP 8.2 MVC application for managing internships. It uses Supabase Auth, PostgREST, and Storage directly through cURL, with Google Apps Script as a Google Drive upload proxy. The front controller is [`public/index.php`](public/index.php); there is no Composer dependency manifest, automated test suite, CI configuration, or locally installed PHP executable available in this workspace.

The working tree contains in-progress changes:

- Modified: `app/Controllers/AttendanceController.php`, `app/Controllers/DailyLogController.php`, `config/actions.php`, `config/view_data.php`
- New/untracked: `app/Controllers/DashboardController.php`, `app/Controllers/ProfileController.php`, `database/migrations/004_add_student_contact_fields.sql`

The implementation is mid-transition from static/mock data to live data. Authentication, session handling, RBAC, and many business services are present, but attendance and daily-log code contain two competing data models.

## Work Completed in This Turn

The Attendance browser flow was hardened without changing the unresolved database model: JSON input is now parsed, the request's `in/out` type is honored, duplicate/missing records are rejected, CSRF is sent, GPS permission failure no longer submits a fake coordinate, and the browser only shows success for a successful HTTP/API response. The remaining GPS enforcement must still be moved into `AttendanceService` after the authoritative attendance schema is confirmed.

## Architecture at a Glance

```text
Browser
  -> public/index.php
      -> config/actions.php     mutable HTTP actions + role requirement
      -> config/routes.php      GET route -> view + role
      -> config/view_data.php   GET route -> controller loader
      -> AuthGuard + CSRF check
      -> Controller -> Service -> SupabaseClient -> Supabase
      -> View -> layout + partials

Cron / Google Apps Script
  -> Services -> Supabase / Google Drive
```

The route configuration is the application composition root. `actions.php` is checked before the GET view table. Every mutating request is CSRF-checked in `public/index.php`, then role-checked by `AuthGuard`. This is a good central choke point, but any JavaScript fetch request must send `X-CSRF-Token` or it will be rejected.

## Folder and File Map

### Root

| File / folder | Purpose |
| --- | --- |
| `README.md` | Setup guide and older phase history. It no longer accurately describes the amount of Phase 3+ business logic now present, so it should be refreshed. |
| `Dockerfile` | Starts PHP 8.2's built-in development server on port 8080. It is suitable for local/demo use, not production PHP-FPM/web-server deployment. |
| `.git/` | Git metadata. Current worktree is intentionally dirty. |
| `storage/` | Runtime logs/uploads. Must remain outside public web root and be writable in deployment. |

### `public/`

| File | Purpose |
| --- | --- |
| `index.php` | Front controller: bootstrap, custom autoloader, exception handler, route matching, centralized CSRF enforcement, RBAC enforcement, controller dispatch, and view rendering. |

### `config/`

| File | Purpose |
| --- | --- |
| `routes.php` | GET route catalog mapping URL to view, required role, selected navigation item, and title. |
| `actions.php` | Mutating route catalog mapping HTTP method/path to controller method and required role. The active IDE file; it now wires profile saving. |
| `view_data.php` | View-model loader catalog. It injects controller data into GET views; recent changes wire Dashboard and Profile loaders. |
| `supabase.php` (gitignored, expected by code) | Runtime URL and API keys. It must never be committed. |
| `google_drive.php` (gitignored, expected by service) | Google Apps Script endpoint and shared secret. It must never be committed. |

### `app/Controllers/`

Controllers translate route input into service calls, choose redirects/JSON responses, and provide arrays for views.

| File | Purpose |
| --- | --- |
| `AnnouncementController.php` | Admin announcement list/create/delete actions. |
| `ApplicationController.php` | Student company search/detail/application views and application submission. |
| `AttendanceController.php` | Current attendance page, check-in/out action, and history loader. It directly uses `attendance_logs`, rather than the newer `AttendanceService` model. |
| `AuditLogController.php` | Admin audit-log page data. |
| `AuthController.php` | Registration, sign-in/out, password reset, and auth form rendering. |
| `BatchController.php` | Internship batch list/form/create/update actions. |
| `CompanyController.php` | Company directory, approvals, company form, and supervisor-facing application data. |
| `CompanyProfileController.php` | Company GPS setup/read/update flow. |
| `DailyLogController.php` | Student logs, company/teacher review view, create/update actions, and reviewer action delegation to `DailyLogService`. |
| `DashboardController.php` | New live-data loaders for student, company, admin, and super-admin dashboards. |
| `EvaluationController.php` | Weekly/final evaluation forms, lists, submissions, and student history. |
| `EvaluationTemplateController.php` | Admin evaluation-template list/form/update. |
| `ImportController.php` | CSV import pages and import actions for students and companies. |
| `InternshipController.php` | Matching, internship creation, approval, termination, and super-admin deletion. |
| `LeaveController.php` | Student leave request form/list/cancel and company/teacher decision flow. |
| `NotificationController.php` | Notification list and read-state actions. |
| `OrgController.php` | Faculty/department lists and create/toggle actions. |
| `ProfileController.php` | New student profile loader/updater for contact and emergency-contact fields. |
| `ReportController.php` | Admin/teacher reports plus CSV/print output. |
| `SettingsController.php` | Admin settings loader and updates. |
| `StudentTimelineController.php` | Teacher/company student lists and timeline detail, including ownership checks. |
| `SuperAdminAdminController.php` | Super-admin admin-account management. |
| `SuperAdminEvaluationController.php` | Super-admin score override flow. |
| `SuperAdminPermissionController.php` | Permission matrix view data. |
| `SuperAdminPinController.php` | PIN setup/verification and short-lived action-token issuance. |
| `TeacherController.php` | Teacher creation form/action. |

### `app/Services/`

Services contain domain rules and all Supabase/Drive interactions. This is the intended home for authorization-aware business logic.

| File | Purpose |
| --- | --- |
| `SupabaseClient.php` | cURL transport for Supabase Auth, PostgREST CRUD, and Storage. REST methods use the service-role key, bypassing RLS. |
| `SupabaseException.php` | Typed error for failed Supabase responses. |
| `AuthService.php` / `AuthException.php` | Authentication, registration, lockout, reset-token handling, and validation failures. |
| `AttendanceService.php` | More complete GPS attendance rules: Haversine distance, photo requirement, late threshold, checkout threshold, history, and stale-record closure. It uses the `attendance` table. |
| `ApplicationService.php` | Student/company application lists and accepted-but-unmatched cases. |
| `AnnouncementService.php` | Announcement CRUD. |
| `AuditLogger.php` | Audit-log writes. |
| `BatchService.php` | Internship batch operations. |
| `CompanyService.php` | Company search, approval, supervisor management, and GPS updates. |
| `DailyLogService.php` | Canonical-looking daily-log draft/submit/edit/attachment/review behavior, keyed by `internship_id`. It conflicts with the controller's alternate schema. |
| `EvaluationService.php` | Evaluation templates, eligibility, submission, score visibility, and history. |
| `FileUploadService.php` | Upload validation and storage metadata writes. |
| `GoogleDriveStorageClient.php` | Calls the Apps Script storage proxy. |
| `ImportService.php` | CSV parsing and student/company import orchestration. |
| `InternshipService.php` | Internship lifecycle, completion checks, supervisee progress, and deletion cleanup assumptions. |
| `LeaveService.php` | Leave-request validation and approval lifecycle. |
| `NotificationService.php` | Notification creation, list/read state, and reminder logic. |
| `OrgService.php` | Faculty/department data management. |
| `ReportService.php` | Aggregate dashboard/report data. |
| `SettingsService.php` | Typed settings reads plus validation rules for configurable values. |
| `SignatureService.php` | Signature validation/upload/audit support. |
| `StudentTimelineService.php` | Timeline construction and teacher/company ownership checks. |
| `SuperAdminAdminService.php` | Admin account lifecycle. |
| `SuperAdminPinService.php` | PIN hash setup, verification, lockout, and action-token issuance. |
| `TeacherService.php` | Teacher list/create operations. |
| `ApiException.php` | Structured API error with HTTP status/code/details. |

### `app/Middleware/` and `app/Support/`

| File | Purpose |
| --- | --- |
| `Middleware/AuthGuard.php` | Redirects unauthenticated/pending users, checks role, refreshes expired token, and emits 403. Supports pipe-delimited multi-role routes. |
| `Middleware/ActionTokenGuard.php` | Enforces a single-use, short-lived super-admin action token for destructive operations. |
| `Support/Session.php` | Session cookie configuration, login/logout, user snapshot, flash messages, CSRF, rate limit, and action token storage. |
| `Support/View.php` | Shared view rendering and universal notification dropdown data. |
| `Support/Uuid.php` | UUID-related helper. |

### `app/Views/`

Views are server-rendered PHP/Tailwind templates. They should only format view data and include a CSRF token in every regular HTML form.

| Folder | Files and purpose |
| --- | --- |
| `layouts/` | `app.php`: common document shell, Tailwind/theme setup, and page content slot. |
| `partials/` | `sidebar.php`, `topbar.php`, `bottom_nav.php`, `notification_dropdown.php`: shared navigation. `signature_pad.php`: client signature UI. |
| `auth/` | `login.php`, `register.php`, `forgot_password.php`, `reset_password.php`, `pending_approval.php`: public/auth workflow. |
| `student/` | `dashboard.php`, `profile.php`, `company_search.php`, `company_detail.php`, `applications.php`, `attendance_checkin.php`, `attendance_history.php`, `daily_logs_list.php`, `daily_log_form.php`, `leave_requests_list.php`, `leave_request.php`, `evaluation_history.php`, `notifications_list.php`: student self-service screens. |
| `company/` | `dashboard.php`, `applications.php`, `students_list.php`, `daily_log_review.php`, `evaluation_list.php`, `evaluation_form.php`, `leave_approval.php`, `gps_setup.php`, `supervisors.php`: company supervisor screens. |
| `teacher/` | `dashboard.php`, `student_timeline.php`, `reports.php`, `evaluation_form.php`: teacher supervision and reporting screens. |
| `admin/` | `dashboard.php`, `pending_approvals.php`, `user_approval_detail.php`, `company_form.php`, `organization.php`, `batch_management.php`, `batch_form.php`, `matching_list.php`, `matching.php`, `internship_approvals.php`, `teacher_form.php`, `csv_import.php`, `csv_import_companies.php`, `announcements.php`, `evaluation_templates.php`, `evaluation_template_form.php`, `settings.php`, `reports.php`, `audit_log.php`: administrative operation screens. |
| `super_admin/` | `manage_admins.php`, `permissions.php`, `pin_setup.php`, `pin_modal.php`, `evaluation_override.php`: privileged administrative screens. |

### `database/`

| File | Purpose |
| --- | --- |
| `seeds/001_initial_seed.sql` | Initial roles, permissions, and reference/seed data. |
| `migrations/001_add_password_reset_token_tracking.sql` | Adds reset token tracking/reuse protection. |
| `migrations/002_add_super_admin_pins.sql` | Adds super-admin PIN data. |
| `migrations/003_add_student_citizen_id.sql` | Adds student citizen ID. |
| `migrations/004_add_student_contact_fields.sql` | New contact, address, and emergency-contact columns required by `ProfileController`. |

### `cron/`, `google-apps-script/`, and `design-reference/`

| File / folder | Purpose |
| --- | --- |
| `cron/activate_internships.php` | Activates eligible internships. |
| `cron/close_incomplete_attendance.php` | Calls attendance stale-checkout logic. It currently follows the `attendance` table model, not `attendance_logs`. |
| `cron/complete_internships.php` | Marks qualifying internships completed. |
| `cron/notify_missed_daily_logs.php` | Sends missing-log reminders. |
| `cron/send_notifications.php` | Processes/sends notifications. |
| `cron/verify_backup.php` | Checks backup/storage health. |
| `google-apps-script/Code.gs` | Google Apps Script HTTP endpoint; validates a shared secret, restricts upload categories, stores files in Drive, and records backup metadata in Sheets. |
| `design-reference/` | Source snapshots from the design tool. Each variant directory contains `screen.png` and/or `code.html`; these are visual references, not runtime application code. `ims/DESIGN.md` describes the design system. |

## Findings in the Files Being Changed

### Critical: Attendance is not enforcing the intended GPS or checkout rules

[`app/Controllers/AttendanceController.php`](app/Controllers/AttendanceController.php) bypasses `AttendanceService` and writes straight to `attendance_logs`; [`app/Services/AttendanceService.php`](app/Services/AttendanceService.php) reads/writes `attendance`. The two paths use different table and column names (`date`/`check_in_time` versus `work_date`/`check_in_at`). Consequently, the Haversine distance check, accuracy handling, photo rule, late rule, and early-checkout rule in the service are not applied to the live page or its history/dashboard/cron flow.

The active view also overwrites the controller's real policy values: [`app/Views/student/attendance_checkin.php:10`](app/Views/student/attendance_checkin.php:10) forces a 1,000,000 m radius and [line 17](app/Views/student/attendance_checkin.php:17) always permits checkout. The page falls back to fixed GPS coordinates when browser geolocation fails, so a failed permission request can still lead to a fabricated location being submitted.

Impact: attendance can be created without a real geofence decision or minimum working-hours enforcement; reporting can disagree with the cron job and service logic.

Recommendation: choose one schema, make `AttendanceService` the sole domain API, and have the controller only parse/validate the request then call it. Do not allow a location fallback; return a visible error when the browser cannot provide a position.

### Critical: Attendance CSRF/success handling was fixed; GPS enforcement remains incomplete

The original [`app/Views/student/attendance_checkin.php`](app/Views/student/attendance_checkin.php) posted JSON with only `Content-Type`, while [`public/index.php`](public/index.php) requires `csrf_token` or `X-CSRF-Token` for every POST. The browser also ignored the response and showed success on failure. This turn now sends `X-CSRF-Token`, parses the API response, blocks missing GPS, and renders errors. The remaining risk is server-side: the controller still does not call the Haversine/GPS rules in `AttendanceService`.

Impact: the request can now reach the server reliably, but a caller may still bypass the intended geofence/minimum-hours policy until the service is made authoritative.

Recommendation: complete the schema decision and route all attendance writes through `AttendanceService`; keep client-side GPS checks only as UX, never as authorization.

### Critical: Daily-log review must remain behind the service ownership checks

The current [`app/Controllers/DailyLogController.php`](app/Controllers/DailyLogController.php) delegates student writes and company/teacher review to `DailyLogService`, which is the correct direction. Before adding any new review endpoint, preserve this boundary: the service must verify the actor's company/teacher ownership, allow-list status transitions, and enforce the source status before updating a row. The shared [`SupabaseClient.php`](app/Services/SupabaseClient.php) uses a service-role key and therefore cannot be treated as an authorization layer.

Impact: a future direct controller patch or unscoped PostgREST query could modify another company's/student's log even when the route role is valid.

Recommendation: keep one `review()` path, add negative ownership tests, and never expose raw upstream exception messages.

### High: Daily-log schema and controller/service contracts need one documented source of truth

The controller now uses `DailyLogService`, but the repository contains historical references to alternate `daily_logs` fields such as `student_id`, `title`, and `activity_description`, while the service's canonical write contract uses `internship_id`, `work_description`, `learning_outcome`, and `problem_found`.

Impact: a view or future endpoint using the older field names can silently write incomplete data or query no rows. This is a data-integrity risk rather than just a naming issue.

Recommendation: document the live schema, remove obsolete field mappings after migration/backfill, and add integration tests for create, revise, submit, approve, and reject transitions.

### High: Dashboard loader creates avoidable N+1 request patterns

[`app/Controllers/DashboardController.php`](app/Controllers/DashboardController.php) makes separate calls per company advisee for internships, departments, and attendance. The page also gets notification queries automatically in `View::render()`.

Impact: the company dashboard's latency and Supabase request volume grow linearly with student count and can fail partially, rendering misleading zeros.

Recommendation: add explicit service-level read models using PostgREST embedded selects or a database view/RPC that returns dashboard rows in one/few calls. Return an explicit `dataUnavailable` state rather than silently converting an operational failure to business value zero.

### Medium: Profile feature depends on an unapplied migration and has incomplete input validation

[`app/Controllers/ProfileController.php`](app/Controllers/ProfileController.php) correctly scopes updates by the current user's `students` row and is protected by the new route's RBAC/CSRF checks. However, saving fails until migration 004 has been applied manually. Inputs validate only that two emergency fields are non-empty; lengths and phone formats are unbounded.

Impact: deployment order can make the new form unusable, and data quality is not protected consistently.

Recommendation: use a migration runner/version table and deploy migrations before application code. Create a ProfileService/request validator with maximum lengths and a documented phone normalization policy.

### Medium: Error suppression conceals operational failures

Several new loaders catch `Throwable` or `Exception` and render zero/empty state, including `AttendanceController`, `DashboardController`, and `ProfileController`. Degraded rendering is desirable for optional widgets, but the error is neither logged nor distinguishable from genuinely empty data.

Impact: Supabase outages, schema drift, and permission errors look like ordinary empty dashboards.

Recommendation: log contextual errors server-side, distinguish `empty` from `unavailable` in each view model, and only use silent degradation for nonessential widgets.

## Refactor Plan

### Phase 0: Stop unsafe behavior before further feature work

1. Freeze attendance and daily-log changes until their data contract is chosen.
2. Remove all `?? 1`, `?? 3`, global fallback queries, fixed GPS coordinates, and success-on-failure JavaScript.
3. Wire CSRF into every fetch request; make JSON controllers return a consistent `{ success, data, error }` envelope.
4. Implement daily-log review through an ownership-checked service and allow-list status transitions.
5. Apply migration 004 in a controlled environment and record it in a migration ledger.
6. Add an incident-safe application logger for caught exceptions; never expose raw upstream errors to a browser.

### Phase 1: Establish one domain contract per module

1. Create a schema inventory from the live Supabase schema, then document table/column ownership under `docs/schema.md`.
2. Decide whether the authoritative attendance table is `attendance` or `attendance_logs`; write a migration/data-backfill plan and retire the other path.
3. Make `AttendanceController` call `AttendanceService` only. Move page/history/summary view-model composition to a dedicated `AttendanceReadService` if needed.
4. Make `DailyLogController` call `DailyLogService` only. Extend that service for reviewer authorization rather than introducing direct controller SQL-like calls.
5. Define state transition enums in PHP (`AttendanceStatus`, `DailyLogStatus`, `InternshipStatus`) so raw strings are not scattered across controllers/views.

### Phase 2: Simplify application boundaries

1. Introduce a small `Request` helper for JSON/form parsing, typed validation, `wantsJson`, and response helpers.
2. Introduce a `Response` helper for redirect/JSON/error handling; remove repeated header/echo/exit branches.
3. Replace the homegrown fallback-heavy autoloader with Composer PSR-4 autoloading.
4. Keep routes declarative but split them by concern: `routes/web.php`, `routes/actions.php`, and named route constants/URL generation to prevent literal-path drift.
5. Define request DTOs/view models for high-risk flows rather than passing unrestricted arrays from database to templates.
6. Move reusable identity resolution (`currentStudent`, `currentCompanySupervisor`, `currentTeacher`) to an `ActorContextService`.

### Phase 3: Make data access reliable and efficient

1. Wrap `SupabaseClient` with repositories or query services by bounded context (`InternshipRepository`, `AttendanceRepository`, etc.). Controllers must not compose raw query strings.
2. Because service-role bypasses RLS, enforce ownership at the service boundary for every row-level operation and add negative authorization tests.
3. Replace dashboard N+1 loops with PostgREST joins, database views, or RPC endpoints tailored to dashboard read models.
4. Add timeouts/retry policy only for safe idempotent reads; log request IDs and upstream response context securely.
5. Consolidate storage integration and ensure the Apps Script shared secret is rotated and never logged.

### Phase 4: Quality gates and deployment discipline

1. Add `composer.json`, PHPStan/Psalm, PHP_CodeSniffer or PHP-CS-Fixer, and a test runner such as PHPUnit/Pest.
2. Add unit tests for state transitions, GPS distance/checkout rules, ownership checks, CSRF failure, and controller JSON responses.
3. Add integration tests using a disposable Supabase project/schema for the authentication and service-role data-access flows.
4. Add CI to run syntax lint, static analysis, tests, and migration checks on every change.
5. Replace the PHP built-in-server Docker image with production PHP-FPM plus a web server, non-root user, health check, environment validation, and external/shared session storage for multi-instance deployment.
6. Update `README.md` to describe current behavior, setup secrets, migration order, test commands, and known limitations.

## Suggested Delivery Order

1. Repair Attendance end-to-end: choose schema, use the service, submit real geolocation with CSRF, and verify errors in the browser.
2. Repair Daily Logs end-to-end: remove duplicate controller schema, enforce ownership/state transitions, then wire reviewer actions.
3. Apply and verify Profile migration 004, then add validation tests.
4. Convert Dashboard queries to read models and expose unavailable states.
5. Add Composer, static analysis, tests, and CI before expanding remaining screens.

## Verification Gap

No PHP executable was available from this workspace, so PHP syntax linting and automated runtime tests could not be run. The findings above are static analysis of the current files and the Git diff. Before merging, run at minimum `php -l` across all PHP files, exercise the attendance and daily-log flows with real Supabase data, and verify each role cannot access another actor's records.
