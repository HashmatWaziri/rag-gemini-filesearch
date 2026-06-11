# GLC AI Platform (Phase 1)

Phase 1 of the AI platform for Greats Language Center (GLC), an English
language school in Kuala Lumpur serving primarily Arabic-speaking learners.
It delivers two separately scoped modules on shared infrastructure:

1. **AI Placement Test** - prospective students enter with an access code,
   complete a fixed-form five-section test, and staff review AI-assisted
   drafts before any result is released.
2. **24/7 AI Tutor** - enrolled students practise with a curriculum-grounded,
   English-only tutor that guides homework without giving direct answers.

## Heritage

This codebase is built on an open-source Laravel health/nutrition platform
(Acara Plate), adopted for its Google Gemini FileSearch RAG implementation.
Legacy health features still exist in the repository and are being phased out;
all new work targets the GLC domains (`App\*\Glc`, `routes/glc/*`,
`resources/js/pages/glc/*`).

## Key documents

| Document | Purpose |
|----------|---------|
| [`GLC_PRD_Phase1.md`](GLC_PRD_Phase1.md) | Authoritative Phase 1 PRD: behavior contract and user stories |
| [`docs/GLC_Phase1_Issues.md`](docs/GLC_Phase1_Issues.md) | Issue backlog GLC-001..GLC-028 with acceptance criteria |
| [`docs/glc-implementation-contract.md`](docs/glc-implementation-contract.md) | Binding coordination contract: conventions, file ownership, invariants |
| [`CONTEXT.md`](CONTEXT.md) | Domain glossary |
| [`client-briefs/`](client-briefs/) | Client proposal brief and scope clarifications (clarifications override the proposal) |

## Tech stack

- PHP 8.4, Laravel 13 (`declare(strict_types=1)`, final classes)
- Inertia + React 19, Tailwind CSS 4 (mobile-first)
- SQLite by default; MySQL/PostgreSQL supported for production
- Google Gemini API with FileSearch for curriculum-grounded retrieval (RAG)
- Pest for tests, Pint for formatting, dompdf for the result PDF

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
bun install
bun run build
```

`composer setup` runs all of the above in one step. Then start the dev
processes (server, queue, logs, Vite, Reverb):

```bash
composer run dev
```

### Required environment

```env
GEMINI_API_KEY=   # Gemini API key; required for AI features
```

With an empty `GEMINI_API_KEY` the app still boots and all non-AI flows work:
AI calls record a failed status and never block staff workflows or release
results. Tests fake all AI/HTTP calls.

## Roles

One role per user account. Placement candidates are not users; they enter with
an access code only.

| Role | Summary |
|------|---------|
| Admin | All supervisor capabilities plus users, access codes, consent flags, exports, audit log, settings |
| Academic Supervisor | All teacher capabilities plus curriculum management, all-student chat visibility, flagged placements |
| Teacher | Reviews assigned placements, assigns course/level/unit, monitors assigned students' tutor usage |
| Student | Enrolled GLC student; uses the AI Tutor |

## Modules

**Placement Test:** access-code entry with privacy notice and age gate
(under-12 blocked); five sections in fixed order (Reading,
Grammar/Vocabulary, Listening, Writing, Speaking) with auto-save, 24-hour
resume, and integrity flags; candidates see only "submitted, pending GLC
review"; staff review AI provisional drafts, approve a structured reviewer
narrative, and explicitly trigger delivery of the "Placement Test Result" PDF
via a 30-day secure link.

**AI Tutor:** authenticated enrolled students only; English-only text chat
scoped to the teacher-assigned Course -> Level -> Unit over Published
curriculum (PDF/DOCX/TXT, Draft -> Published -> Archived lifecycle with
mandatory text preview); homework guidance without direct answers, six logged
violation categories, writing correction with five-dimension feedback; staff
see full chat history and writing submissions.

## Out of scope (Phase 1)

Adaptive testing, live AI voice, AI-generated listening audio,
speaking/listening practice in the tutor, AI-generated quizzes, usage/weak-area
analytics, full textbook ingestion, WhatsApp and Google Workspace integrations,
automatic result delivery, payments, native apps, parent dashboard,
gamification, public marketing site, self-signup, candidate portal, SSO,
director signature on the PDF, and automated consent emails. See the PRD
"Out of Scope" section for the full list; these may be quoted as Phase 2.

## Quality checks

```bash
composer test        # type coverage, unit tests, lint, static analysis
vendor/bin/pint --dirty
```

## License

See [LICENSE](LICENSE). Application source transfers to GLC on project
completion per the implementation agreement.
