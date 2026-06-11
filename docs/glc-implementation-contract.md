# GLC Phase 1 — Implementation Contract (agent coordination)

This document is the binding contract for the parallel implementation of
`docs/GLC_Phase1_Issues.md`. The shared foundation (schema, enums, models,
factories, middleware, route stubs, config) is already built. Read this
fully before writing code.

## Authoritative sources

1. `GLC_PRD_Phase1.md` — behavior contract
2. `docs/GLC_Phase1_Issues.md` — acceptance criteria per issue
3. `client-briefs/*` — background (clarifications override proposal)

## Stack conventions (must follow)

- PHP 8.4, Laravel 13, `declare(strict_types=1);`, `final` classes, no inline imports.
- Controllers: `App\Http\Controllers\Glc\<Domain>\...` — thin, validation via FormRequest or inline `$request->validate()`.
- Frontend: **Inertia React 19 + Tailwind 4** pages under `resources/js/pages/glc/<domain>/...`. Use `resources/js/layouts/glc-layout.tsx` for authenticated pages. Mobile-first. Do NOT use Livewire for GLC pages.
- Tests: Pest feature tests under `tests/Feature/Glc/<Domain>/`. Run only your dir: `php artisan test tests/Feature/Glc/<Domain> --compact`. RefreshDatabase is automatic (see `tests/Pest.php`).
- Formatting: `vendor/bin/pint --dirty` before finishing.
- All AI/HTTP calls MUST be faked in tests (`Http::fake`, `Mail::fake`, `Queue::fake`, `Storage::fake`). `GEMINI_API_KEY` is empty locally — production code must degrade gracefully (record `failed` status, never block flows, never auto-release results).
- Gemini HTTP pattern: see `app/Console/Commands/UploadDocumentToGeminiFileSearchCommand.php` (headers `x-goog-api-key`, base URL `config('gemini.base_url')`).
- Audit sensitive actions via `app(App\Services\Glc\AuditLogger::class)->log(AuditAction::..., $actor, $subject, $details)`. Do not edit AuditLogger.
- Admin-provisioned accounts must set `email_verified_at` at creation (User is MustVerifyEmail + prunes unverified accounts).

## Shared foundation (read-only for all agents)

Do NOT edit these (extend via your own services; new migrations allowed only for YOUR tables):

- `app/Enums/Glc/*` (UserRole, GlcLevel, PlacementSection, PlacementItemType, statuses, TutorViolationCategory, WritingDimension, AuditAction)
- `app/Models/Glc/*` + `database/factories/Glc/*` + `database/migrations/2026_06_10_2100*`
- `app/Models/User.php` (role/guardian helpers, relationships: `studentAssignment`, `assignedStudents`, `teachers`, `tutorConversations`, `writingSubmissions`, `canUseTutor()`, `requiresGuardianConsent()`)
- `app/Http/Middleware/Glc/*` (aliases: `glc.role:<roles>`, `glc.tutor`)
- `bootstrap/app.php`, `config/glc.php`, `app/Enums/SettingKey.php`
- `resources/js/layouts/glc-layout.tsx` (nav paths are pinned — implement the pages it links to)
- `app/Http/Controllers/DashboardController.php` (role users redirect to `UserRole::homePath()`)

`UserFactory` role states: `admin()`, `academicSupervisor()`, `teacher()`, `student()`, `minorStudent()`, `withGuardianConsent()`.

## File ownership (NO writes outside your territory)

| Agent | Issues | Owns (create/edit) |
|---|---|---|
| guidelines | docs | `.ai/guidelines/*`, `CONTEXT.md`, `README.md` |
| admin | 002, 003, 026 | `routes/glc/admin.php`, `app/Http/Controllers/Glc/Admin/`, `app/Services/Glc/Admin/`, `resources/js/pages/glc/admin/`, `tests/Feature/Glc/Admin/` |
| placement-candidate | 005–012, 018 | `routes/glc/placement.php`, `app/Http/Controllers/Glc/Placement/`, `app/Services/Glc/Placement/`, `resources/js/pages/glc/placement/`, `tests/Feature/Glc/Placement/` |
| placement-staff | 004, 013–017 | `routes/glc/staff.php`, `routes/glc/results.php`, `app/Http/Controllers/Glc/Staff/`, `app/Services/Glc/Review/`, `app/Jobs/Glc/Placement/`, `app/Mail/Glc/`, `resources/js/pages/glc/staff/`, `resources/views/glc/` (PDF blade), `database/seeders/GlcPlacementContentSeeder.php`, `tests/Feature/Glc/Staff/` |
| curriculum | 019, 020 | `routes/glc/curriculum.php`, `app/Http/Controllers/Glc/Curriculum/`, `app/Services/Glc/Curriculum/`, `app/Jobs/Glc/Curriculum/`, `resources/js/pages/glc/curriculum/`, `database/seeders/GlcCurriculumSeeder.php`, `tests/Feature/Glc/Curriculum/` |
| tutor | 021–025, 027 | `routes/glc/tutor.php`, `app/Http/Controllers/Glc/Tutor/`, `app/Services/Glc/Tutor/`, `app/Jobs/Glc/Tutor/`, `app/Notifications/Glc/`, `app/Console/Commands/Glc/`, `resources/js/pages/glc/tutor/`, `tests/Feature/Glc/Tutor/`, `tests/Fixtures/Glc/` |

Cross-domain READS are fine (models, enums, services); cross-domain WRITES are forbidden.

## Pinned route names / paths (glc-layout nav depends on these)

- `placement.entry` GET `/placement` (public access-code entry)
- `placement.result.show` GET `/placement/result/{token}` (public, 30-day link)
- `staff.review.index` GET `/staff/review`
- `staff.content.index` GET `/staff/placement-content` (supervisor/admin only — scope with `glc.role:academic_supervisor,admin` inside staff.php)
- `curriculum.index` GET `/staff/curriculum`
- `staff.students.index` GET `/staff/students` (tutor agent file, staff middleware)
- `staff.tutor.index` GET `/staff/tutor` (tutor agent file, staff middleware)
- `tutor.index` GET `/tutor`
- `tutor.blocked` GET `/tutor/blocked` (consent-gate redirect target; must NOT require consent middleware)
- `admin.users.index` `/admin/users`, `admin.access-codes.index` `/admin/access-codes`, `admin.exports.index` `/admin/exports`, `admin.audit.index` `/admin/audit`, `admin.settings.edit` `/admin/settings`

## Domain invariants (cross-agent)

- **Candidates are not RBAC users.** Attempt resume = `device_token` cookie/localStorage match + 24h window.
- **Fixed order:** Reading → G/V → Listening → Writing → Speaking (`PlacementSection::ordered()`).
- **Candidates never see scores/levels/AI output.** Candidate-facing payloads must never serialize `PlacementScore`, `PlacementAiDraft`, `PlacementReview`, or `correct_option`.
- **Submission side-effect (candidate agent):** on submit, create `PlacementReview` (status `pending`) and dispatch scoring pipeline if present: `App\Services\Glc\Review\ScoringService` and `App\Jobs\Glc\Placement\GeneratePlacementAiDraftsJob` (staff agent). Guard with `class_exists()` so domains stay decoupled while in-flight.
- **Access code lifecycle (candidate agent updates):** unused → in_progress (session start) → completed (submit). Revocation only by admin agent.
- **Curriculum metadata for Gemini FileSearch** (`customMetadata` on import, used by tutor filter): keys `course_id`, `course_level_id`, `course_unit_id`, `course_lesson_id` (numeric values), `status` (string `published`). Store name lives in `Setting::get(SettingKey::GlcCurriculumStoreName)`; curriculum agent creates the store lazily (display name `config('glc.curriculum.store_display_name')`).
- **Tutor retrieval scope:** Published docs within the student's `StudentAssignment` (course+level+unit) via FileSearch `metadataFilter` AND a DB-side allowlist check. English-only responses; refuse speaking/listening practice (Phase 1).
- **Six violation categories** are `TutorViolationCategory` cases — log via model, notify teacher (database notification) when `>= config('glc.tutor.violation_notification_threshold')` direct-answer violations within `violation_notification_window_days`.
- **Consent gates:** tutor access via `glc.tutor` middleware (already enforces); result send for minor candidates (attempt `isMinor()`) requires the linked candidate... candidates have no account — staff agent must block send when `PlacementAttempt::isMinor()` and no consent record. For Phase 1 the staff send screen requires an explicit "guardian consent confirmed" checkbox persisted on the review `flags` (`guardian_consent_confirmed`) + audit log (`AuditAction::ConsentConfirmed`).
- **Writing/speaking AI drafts:** staff-only; failures set `PlacementAiDraftStatus::Failed` and never block staff review (manual entry path required).
- **PDF:** dompdf (`barryvdh/laravel-dompdf` installed, facade `Barryvdh\DomPDF\Facade\Pdf`). Title "Placement Test Result", GLC logo placeholder, per-skill levels + overall level, NO internal notes/AI confidence/director signature.
- **Text extraction:** `smalot/pdfparser` (PDF), `phpoffice/phpword` (DOCX), raw read (TXT).
- **Uploads:** store on `local` disk under `glc/...` paths; audio for listening under `glc/placement/audio`, speaking recordings under `glc/placement/recordings`, curriculum under `glc/curriculum`. Serve listening audio via a controller route that enforces the single-play rule.

## Definition of done (each agent)

1. All acceptance criteria of your issues implemented end-to-end (routes → UI → persistence).
2. Pest feature tests covering each acceptance criterion (happy path + the critical guard rails), all green.
3. `vendor/bin/pint --dirty` clean. No edits outside your territory.
4. Report: files created, criteria coverage map, anything deferred.
