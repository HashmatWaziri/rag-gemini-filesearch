# GLC Domain Glossary (CONTEXT)

Domain vocabulary for the GLC AI Platform Phase 1. Referenced by
[`GLC_PRD_Phase1.md`](GLC_PRD_Phase1.md) and
[`docs/GLC_Phase1_Issues.md`](docs/GLC_Phase1_Issues.md). Update this glossary
when terminology changes.

## Users and roles

Every account has exactly one role. Placement candidates are not accounts at all
(see below).

| Role | Description |
|------|-------------|
| Admin | Operates the platform: all Academic Supervisor capabilities plus user management, access codes, guardian consent flags, exports, audit log, and settings. |
| Academic Supervisor | All Teacher capabilities plus curriculum content management, chat history visibility for all enrolled students, and review of flagged placements. |
| Teacher | Reviews assigned placement submissions, assigns Course/Level/Unit to enrolled students, and monitors chat history and writing submissions of assigned students. |
| Student | Enrolled GLC student; uses the AI Tutor and submits writing for correction. No upload or staff capabilities. |
| Placement candidate | Not a role and not a user. A prospective student who enters the placement test with an access code; no login, no account. |

### One role per user

Each account holds exactly one of the four roles (Admin, Academic Supervisor,
Teacher, Student); roles cannot be stacked or switched without Admin action.
Broader roles are supersets by design (Admin > Academic Supervisor > Teacher),
not the result of holding multiple roles.

## Placement Test terms

### Placement candidate

A prospective student taking the AI Placement Test via access code with a
minimal profile: name, required email, and age (used for minor safeguards).
Candidates have no persistent account, portal, or login, and are never RBAC
users. Candidates under 12 are blocked from starting the test.

### Access code

A staff-issued code that admits one candidate into one placement attempt.
Codes are single-use, optionally expiring, revocable by Admin, and move through
unused -> in progress -> completed. A retake always requires a new access code;
prior attempts are preserved.

### Placement attempt

One candidate's pass through the test, created when a valid access code starts
a session. An attempt supports continuous auto-save, 24-hour same-device
pause/resume (device token match), per-section timers that pause after 30
minutes of inactivity, and integrity flags (tab switches, dual-device use).

### Fixed-form test

Every candidate receives the same items in the same order: no adaptive logic,
no shuffling, no item banks in Phase 1. Listening clips play exactly once (no
replay) and paste is blocked in the Writing area.

### Section

One of the five test parts, always in this fixed order: Reading,
Grammar/Vocabulary, Listening, Writing, Speaking. Each section has its own
configurable time limit (planning defaults 15/12/10/25/8 minutes) and
contributes an equal 20% weight to the composite score.

### GLC level

GLC's seven-step proficiency scale: Starter, Beginner, Elementary,
Pre-Intermediate, Intermediate, Upper-Intermediate, Advanced. The composite
percentage maps to a level using ~15% planning bands (Starter 0-14% through
Advanced 90-100%). Staff may override the mapped level with a logged reason.

## Review and results terms

### Provisional AI draft

A staff-only AI evaluation generated after submission: Writing is scored on
five dimensions (grammar, vocabulary, structure, coherence, task completion,
1-5 internally) and Speaking is evaluated from the recording/transcript.
Drafts are never shown to candidates or parents, and a failed draft never
blocks staff review or releases anything automatically.

### Reviewer narrative

Structured, staff-edited fields that become the parent-facing text on the
Placement Test Result PDF. An optional AI narrative draft may pre-fill the
fields as a staff-only starting point, but staff must edit and approve before
PDF generation: no unreviewed AI prose appears under the GLC logo.

### Staff-triggered PDF delivery

Results are delivered only when a teacher or supervisor explicitly triggers
send after review approval; nothing is sent automatically on approval. Delivery
is a single transactional email containing a secure result link. No WhatsApp
and no automatic parent emails in Phase 1.

### Secure result link

A time-limited tokenized link (30 days) from which the candidate can view or
download the approved "Placement Test Result" PDF without an account. Expired
links show a clear message to contact GLC.

## Tutor and curriculum terms

### Enrolled student

A GLC student with a Student-role account created by Admin (manually or via
bulk import; no public self-signup). Only enrolled students can use the AI
Tutor, after a teacher assignment exists and, for ages 12-17, guardian consent
is confirmed.

### Course -> Level -> Unit -> Lesson

The four-level hierarchy organizing all curriculum content. Every document is
tagged with its full hierarchy path on upload; student assignments and tutor
retrieval operate on this hierarchy.

### Draft / Published / Archived

The curriculum document lifecycle. Uploads start as Draft (never retrievable by
the tutor), publishing requires a mandatory extracted-text preview, and
Archived content is removed from tutor retrieval. Replacing a file updates the
version and refreshes the searchable index.

### Tutor scope

The teacher-assigned Course/Level/Unit that bounds the tutor: it retrieves only
Published materials within the assignment and may combine lessons inside that
scope. The tutor is English-only, never gives direct homework answers (hints,
explanations, Socratic guidance only), and refuses speaking/listening practice
in Phase 1.

### Violation categories

The six categories of logged tutor guardrail events: (1) direct answer seeking,
(2) off-topic, (3) personal/social, (4) inappropriate, (5) future/unassigned
unit, (6) speaking/listening practice request. Each violation is logged with
student, timestamp, and category; persistent direct-answer seeking notifies the
student's teacher.

### Conversation rotation

When an active tutor conversation exceeds 40 message pairs, the oldest 20 pairs
are summarized to keep the working context manageable while preserving
continuity. Full chat history remains stored and visible to staff.

## Platform and compliance terms

### Guardian consent

For users aged 12-17, guardian name and email are collected and an Admin
manually sets a consent-confirmed flag. Without the flag, tutor access is
blocked for the student and placement results cannot be sent for a minor
candidate. There is no automated consent email workflow in Phase 1.

### Audit log

The append-only record of sensitive staff actions (score/level overrides,
result sends, consent changes, deletions, curriculum publishes, exports,
settings changes) capturing who did what to which record and when. Supports
accountability and PDPA compliance.
