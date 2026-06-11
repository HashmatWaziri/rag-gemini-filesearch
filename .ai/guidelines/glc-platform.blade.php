# GLC Platform Guidelines

This codebase is the **GLC AI Platform Phase 1** for Greats Language Center (GLC),
an English language school in Kuala Lumpur serving primarily Arabic-speaking
learners. It delivers two separately scoped modules on shared infrastructure:

1. **AI Placement Test** - prospective students (placement candidates) enter via
   access code, complete a fixed-form multi-skill test, and staff review
   AI-assisted drafts before any result is released.
2. **24/7 AI Tutor** - enrolled students chat with a curriculum-grounded,
   English-only tutor scoped to their teacher-assigned course/level/unit.

Authoritative documents (read before implementing GLC features):

- `docs/glc-implementation-contract.md` - binding coordination contract: file
  ownership, pinned route names, shared foundation, definition of done.
- `GLC_PRD_Phase1.md` - authoritative Phase 1 behavior contract and user stories.
- `docs/GLC_Phase1_Issues.md` - issue backlog GLC-001..GLC-028 with acceptance criteria.
- `CONTEXT.md` - domain glossary.
- `client-briefs/*` - background; scope clarifications override the proposal.

## Legacy code

The app was adopted from "Acara Plate", an open-source Laravel health/nutrition
platform, because of its Google Gemini FileSearch RAG implementation. Legacy
Acara/health code (nutrition, glucose, Telegram, Apple Health sync, etc.) still
coexists in the repository and is being phased out. Do not extend it. All new
work targets the GLC domains below.

## Roles

Four roles, **exactly one role per user**: Admin, Academic Supervisor, Teacher,
Student (`App\Enums\Glc\UserRole`). Admin manages users and settings; Academic
Supervisor covers all teacher capabilities plus curriculum and flagged cases;
Teacher reviews assigned placements and assigned students; Student uses the tutor.

**Placement candidates are NOT users.** They never authenticate; they enter with
an access code and a minimal profile (name, email, age). Attempt resume relies on
a device token and a 24-hour window, not a session login.

## GLC namespaces and paths

- `App\Models\Glc` - GLC Eloquent models (placement, curriculum, tutor, audit).
- `App\Enums\Glc` - roles, levels, sections, statuses, violation categories.
- `App\Http\Controllers\Glc\<Domain>` - thin controllers per domain.
- `App\Services\Glc\<Domain>` - domain logic lives in services here.
- `routes/glc/*` - admin, placement, staff, results, curriculum, tutor route files.
- `resources/js/pages/glc/<domain>/*` - Inertia React pages (use
  `resources/js/layouts/glc-layout.tsx` for authenticated pages, mobile-first,
  no Livewire for GLC pages).
- `tests/Feature/Glc/<Domain>` - Pest feature tests.
- `config/glc.php` - Phase 1 planning defaults (time limits, word counts,
  rotation thresholds, consent ages).

The shared foundation (enums, models, factories, migrations, middleware,
`config/glc.php`, glc-layout) is read-only; extend via your own services. See the
implementation contract for per-agent file ownership.

## Key invariants (never violate)

- **Candidates never see scores, levels, or AI output.** Candidate-facing
  payloads must never serialize `PlacementScore`, `PlacementAiDraft`,
  `PlacementReview`, or `correct_option`. After submit, candidates see only a
  "submitted, pending GLC review" message.
- **Mandatory staff review** for every submission - not borderline-only - before
  any parent-facing output. AI drafts (writing/speaking evaluations, narrative
  drafts) are staff-only helpers; no unreviewed AI prose may appear on the
  parent-facing PDF under the GLC logo.
- **Result delivery is staff-triggered.** No automatic send on approval. The
  email contains a secure result link valid 30 days. PDF title is
  "Placement Test Result" (not certificate), no director signature in Phase 1.
- **Fixed-form test, fixed order:** Reading -> Grammar/Vocabulary -> Listening ->
  Writing -> Speaking (`PlacementSection::ordered()`). Same items, same order for
  every candidate; listening clips play once with no replay; paste is blocked in
  Writing.
- **Tutor is English-only** and scoped to the student's teacher-assigned
  Course -> Level -> Unit (Published materials only). It never gives direct
  homework answers - hints, explanations, and Socratic guidance only - and
  refuses speaking/listening practice in Phase 1.
- **Six violation categories** (`App\Enums\Glc\TutorViolationCategory`):
  direct answer seeking, off-topic, personal/social, inappropriate,
  future/unassigned unit, speaking/listening practice request. Log violations;
  notify the teacher on persistent direct-answer seeking.
- **Guardian consent gate (ages 12-17):** guardian name and email are collected
  and an Admin sets a manual consent flag before tutor access or result send for
  a minor. No automated consent email workflow in Phase 1.
- **Audit logging:** record sensitive staff actions (overrides, sends, consent
  changes, deletions, publishes) via
  `app(App\Services\Glc\AuditLogger::class)->log(AuditAction::..., $actor, $subject, $details)`.
  Do not edit `AuditLogger`.
- **AI failures degrade gracefully.** `GEMINI_API_KEY` is empty locally; record a
  `failed` status, never block staff flows, never auto-release results. Fake all
  AI/HTTP calls in tests.
