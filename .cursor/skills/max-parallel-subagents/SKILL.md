---
name: max-parallel-subagents
description: Dispatch the maximum number of parallel subagents for GLC Phase 1 work while avoiding write conflicts. Use when parallelizing implementation across domains, splitting tasks for concurrent execution, or when the user asks for maximum parallel dispatch without file conflicts.
---

# Max Parallel Subagents (GLC)

Dispatch as many parallel subagents as possible without two agents writing the same files.

Authoritative territory map: [`docs/glc-implementation-contract.md`](../../../docs/glc-implementation-contract.md) and [`AGENTS.md`](../../../AGENTS.md) (File ownership section).

## Rules

1. **One domain per subagent.** Each subagent owns exactly one row in the file-ownership table below.
2. **Shared foundation is read-only.** Never assign writes to shared enums, models, migrations, middleware, `User.php`, `config/glc.php`, `glc-layout.tsx`, or `DashboardController.php`. Extend via domain services in owned territory.
3. **Cross-domain reads only.** Subagents may read models, enums, and services from other domains; cross-domain writes are forbidden.
4. **Launch in one message.** Send all independent Task tool calls in a single parent turn so they run concurrently.
5. **Integrate sequentially.** After parallel domains finish, the parent agent handles cross-domain wiring (route registration already exists; focus on `class_exists()` guards and interface contracts).

## Parallelizable domains (no write overlap)

These six territories can run **simultaneously** when each subagent stays inside its paths:

| Domain | Issue IDs | Write territory |
|--------|-----------|-----------------|
| **admin** | GLC-002, 003, 026 | `routes/glc/admin.php`, `app/Http/Controllers/Glc/Admin/`, `app/Services/Glc/Admin/`, `resources/js/pages/glc/admin/`, `tests/Feature/Glc/Admin/` |
| **placement-candidate** | GLC-005–012, 018 | `routes/glc/placement.php`, `app/Http/Controllers/Glc/Placement/`, `app/Services/Glc/Placement/`, `resources/js/pages/glc/placement/`, `tests/Feature/Glc/Placement/` |
| **placement-staff** | GLC-004, 013–017 | `routes/glc/staff.php`, `routes/glc/results.php`, `app/Http/Controllers/Glc/Staff/`, `app/Services/Glc/Review/`, `app/Jobs/Glc/Placement/`, `app/Mail/Glc/`, `resources/js/pages/glc/staff/`, `resources/views/glc/`, `database/seeders/GlcPlacementContentSeeder.php`, `tests/Feature/Glc/Staff/` |
| **curriculum** | GLC-019, 020 | `routes/glc/curriculum.php`, `app/Http/Controllers/Glc/Curriculum/`, `app/Services/Glc/Curriculum/`, `app/Jobs/Glc/Curriculum/`, `resources/js/pages/glc/curriculum/`, `database/seeders/GlcCurriculumSeeder.php`, `tests/Feature/Glc/Curriculum/` |
| **tutor** | GLC-021–025, 027 | `routes/glc/tutor.php`, `app/Http/Controllers/Glc/Tutor/`, `app/Services/Glc/Tutor/`, `app/Jobs/Glc/Tutor/`, `app/Notifications/Glc/`, `app/Console/Commands/Glc/`, `resources/js/pages/glc/tutor/`, `tests/Feature/Glc/Tutor/`, `tests/Fixtures/Glc/` |
| **guidelines** | docs | `.ai/guidelines/*`, `CONTEXT.md`, `README.md` |

**Maximum safe parallelism: 6** (all domains above at once), provided no two agents share a row.

## Do NOT parallelize these together

| Conflict | Why |
|----------|-----|
| **guidelines + any implementation domain** | `CONTEXT.md` / README edits can race with agents reading glossary mid-session — run guidelines alone or before implementation. |
| **Two agents on the same domain row** | Obvious write collision on routes, controllers, pages, tests. |
| **Shared file edits** | `bootstrap/app.php`, `routes/web.php`, `composer.json`, `package.json`, Wayfinder regen — parent agent only, one at a time. |
| **placement-candidate submit + placement-staff scoring** | Candidate agent dispatches staff jobs via `class_exists()` guards; staff agent must land scoring services **before** candidate submit integration is tested end-to-end. Parallel implementation is fine; **integration test** is sequential. |

## Dispatch pattern

For each domain slice, launch a Task subagent with:

- **subagent_type:** `generalPurpose` (or a domain specialist if configured)
- **Prompt must include:** domain name, issue IDs, owned file paths (from table), link to `docs/GLC_Phase1_Issues.md` acceptance criteria, and explicit "do not write outside territory" constraint
- **Tests:** `php artisan test tests/Feature/Glc/<Domain>/ --compact`
- **Format:** `vendor/bin/pint --dirty` before reporting done

Example parent turn (3 parallel agents):

```
Task(admin): Implement GLC-002, GLC-003 per docs/GLC_Phase1_Issues.md. Territory: routes/glc/admin.php, ...
Task(placement-candidate): Implement GLC-005–008. Territory: routes/glc/placement.php, ...
Task(curriculum): Implement GLC-019. Territory: routes/glc/curriculum.php, ...
```

Wait for all to complete, then parent runs targeted tests and handles any cross-domain gaps.

## When fewer agents is better

- **Single vertical slice** spanning one domain → one subagent, no parallelism needed.
- **HITL slices** (architecture sign-off, client content) → block AFK parallel work that depends on the decision.
- **Schema changes** → only the owning domain agent may add migrations for its tables; never parallelize migrations touching the same table.

## Definition of done (per subagent)

1. All assigned issue acceptance criteria implemented end-to-end.
2. Pest feature tests green for that domain directory.
3. `vendor/bin/pint --dirty` clean.
4. Report: files created, criteria coverage map, deferred items.

Parent agent verifies no territory violations before merging or continuing.
