# GLC Phase 1 — Implementation Issues

**Version:** 1.0  
**Date:** 10 June 2025  
**Derived from:** [`GLC_PRD_Phase1.md`](../GLC_PRD_Phase1.md) (authoritative Phase 1 PRD)

---

## How to use this document

This file is a **standalone issue backlog**. It can be imported into any issue tracker (GitHub, GitLab, Jira, Linear, etc.) or used as-is for agent/human implementation.

**Dependencies:** This document depends only on the requirement sources listed below. It does not assume a tech stack, repository layout, or CI configuration.

**Issue IDs** (`GLC-001`, `GLC-002`, …) are stable local identifiers. When publishing to a tracker, map each ID to the tracker’s native issue number and keep the `GLC-###` prefix in the title or body for traceability.

**Slice type**

| Type | Meaning |
|------|---------|
| **AFK** | Can be implemented and verified without human gate (architecture sign-off, design review, or client content delivery). |
| **HITL** | Requires human decision, review, or client-supplied material before merge or go-live. |

**Vertical slices:** Each issue cuts through all layers end-to-end (data, behavior, staff/candidate/student UI, verifiable acceptance). Prefer completing slices in dependency order.

---

## Requirements references

| Document | Path | Role |
|----------|------|------|
| **Phase 1 PRD** | [`GLC_PRD_Phase1.md`](../GLC_PRD_Phase1.md) | Authoritative behavior, user stories, implementation decisions, testing seams, proceed-now assumptions |
| **Scope clarifications** | [`client-briefs/GLC_Client_Scope_Clarifications_Follow_Up.md`](../client-briefs/GLC_Client_Scope_Clarifications_Follow_Up.md) | **Authoritative** over proposal where they differ |
| **Original proposal brief** | [`client-briefs/Proposal_Brief_AI_Placement_Test_24-7_AI_Tutor_GLC.md`](../client-briefs/Proposal_Brief_AI_Placement_Test_24-7_AI_Tutor_GLC.md) | Commercial structure and background; superseded by clarifications for Phase 1 scope |
| **Domain glossary** | [`CONTEXT.md`](../CONTEXT.md) | Terminology (placement candidate, staff-triggered PDF delivery, GLC level, etc.) |
| **Gap / assumption log** | [`docs/gap-register.md`](gap-register.md) | PRD approval status and reconciliation history |
| **Proceed-now assumptions** | [`GLC_PRD_Phase1.md` § Proceed-now assumptions](../GLC_PRD_Phase1.md#proceed-now-assumptions-glc-to-provide-later) | Non-blocking defaults until GLC supplies finals |

**PRD sections to cite per slice:** Problem Statement, User Stories (1–63), Implementation Decisions, Testing Decisions (seams), Out of Scope, Proceed-now assumptions.

**Explicitly out of scope for all issues:** See PRD § Out of Scope (Phase 1) — adaptive placement, auto-delivery, quizzes, native apps, SSO, candidate portal, etc.

---

## Issue index

| ID | Title | Type | Blocked by | User stories |
|----|-------|------|------------|--------------|
| GLC-001 | Shared platform shell, authentication, and role enforcement | AFK | — | 21, 41–43, 49, 59–61 |
| GLC-002 | Admin user provisioning, bulk import, and guardian consent gate | AFK | GLC-001 | 41–45 |
| GLC-003 | Placement access code issuance and lifecycle | AFK | GLC-001 | 1, 20, 44 |
| GLC-004 | Placement content management with preview | AFK | GLC-001 | (staff content; PRD Module A) |
| GLC-005 | Placement entry, profile, privacy notice, and age gate | AFK | GLC-003 | 1–5 |
| GLC-006 | Placement session persistence, timers, and auto-save | AFK | GLC-005 | 7–9 |
| GLC-007 | Reading section — fixed-form completion | AFK | GLC-004, GLC-006 | 6, 10 |
| GLC-008 | Grammar/Vocabulary section — fixed-form completion | AFK | GLC-007 | 6, 11 |
| GLC-009 | Listening section — single-play audio | AFK | GLC-008 | 6, 12 |
| GLC-010 | Writing section — word limits and paste block | AFK | GLC-009 | 13–14 |
| GLC-011 | Speaking section — recording, quality checks, retries | AFK | GLC-010 | 15–16 |
| GLC-012 | Placement submit and candidate pending-review state | AFK | GLC-011 | 17–18 |
| GLC-013 | Objective scoring and composite GLC level calculation | AFK | GLC-012 | (PRD Scoring and levels) |
| GLC-014 | Provisional AI drafts for Writing and Speaking (staff-only) | AFK | GLC-013 | 24 |
| GLC-015 | Staff review queue, override, and internal notes | AFK | GLC-014 | 23–28, 40 |
| GLC-016 | Structured reviewer narrative with staff-only AI draft helper | AFK | GLC-015 | 25 |
| GLC-017 | Placement Test Result PDF and staff-triggered secure link | AFK | GLC-016 | 18–20, 29 |
| GLC-018 | Placement integrity signals (tab switch, dual device) | AFK | GLC-006 | (PRD Anti-integrity) |
| GLC-019 | Curriculum hierarchy, upload, and Draft state | AFK | GLC-001 | 36–38 |
| GLC-020 | Curriculum preview, publish, archive, and version replace | AFK | GLC-019 | 37–39 |
| GLC-021 | Teacher assignment of course, level, and unit to students | AFK | GLC-002, GLC-020 | 22, 51 |
| GLC-022 | Student tutor chat — English-only, scoped retrieval | AFK | GLC-021 | 49–52, 56–58 |
| GLC-023 | Homework guidance guardrails and violation logging | AFK | GLC-022 | 53–54, 33 |
| GLC-024 | Tutor writing correction with inline highlights | AFK | GLC-022 | 55, 31 |
| GLC-025 | Staff tutor visibility — chat history, writing, activity | AFK | GLC-023, GLC-024 | 30–32, 34–35 |
| GLC-026 | Admin data export and staff audit logging | AFK | GLC-001, GLC-015 | 46–47, 48 |
| GLC-027 | Guardrail acceptance test harness (50 homework questions) | HITL | GLC-023 | (PRD Guardrail UAT) |
| GLC-028 | Staging UAT, production deploy, backups, and handover | HITL | GLC-017, GLC-025, GLC-026 | 59–63 |

---

## Issues

### GLC-001 — Shared platform shell, authentication, and role enforcement

**Type:** AFK  
**Blocked by:** None — can start immediately  
**Requirements:** PRD § Shared infrastructure, User Stories 21, 41–43, 49; CONTEXT § Users and roles, One role per user

## What to build

Deliver the standalone platform foundation: staff and enrolled-student authentication, exactly one role per account (Admin, Academic Supervisor, Teacher, Student), mobile-first layout shell, and environment separation (production + staging/UAT). Placement candidates are not RBAC users in this slice.

Unauthenticated users hitting staff or student routes are redirected to login. Each role sees only routes and actions permitted for that role. No SSO or external identity in Phase 1.

## Acceptance criteria

- [ ] Admin, Academic Supervisor, Teacher, and Student can log in with standalone credentials
- [ ] Each account has exactly one role; role cannot be stacked or switched without Admin action
- [ ] Unauthorized role access to another role’s routes is denied
- [ ] Mobile-first responsive shell loads in under 2 seconds under normal conditions (planning target)
- [ ] Staging/UAT environment exists separately from production
- [ ] Placement candidate entry route is reachable without staff/student login (shell only; full flow in later slices)

## Blocked by

None — can start immediately

---

### GLC-002 — Admin user provisioning, bulk import, and guardian consent gate

**Type:** AFK  
**Blocked by:** GLC-001  
**Requirements:** PRD § Minors, User Stories 41–45; CONTEXT § Enrolled student, Guardian consent

## What to build

Admin can create staff and student accounts manually and via bulk upload. For users aged 12–17, collect guardian name and email and require an Admin **manual consent confirmed** flag before the student may use the AI Tutor or before placement results may be sent to a minor candidate. No automated consent email workflow in Phase 1.

## Acceptance criteria

- [ ] Admin creates individual staff and student accounts with one role each
- [ ] Admin bulk-imports users with validation errors surfaced per row
- [ ] Guardian fields are captured for ages 12–17
- [ ] Tutor access is blocked until consent flag is set for applicable students
- [ ] Placement result send is blocked until consent flag is set for applicable minor candidates
- [ ] PDPA-aligned privacy notice is shown at student onboarding (placeholder text acceptable per PRD proceed-now assumptions)

## Blocked by

- GLC-001

---

### GLC-003 — Placement access code issuance and lifecycle

**Type:** AFK  
**Blocked by:** GLC-001  
**Requirements:** PRD § Entry and session, User Stories 1, 20, 44; CONTEXT § Placement candidate

## What to build

Admin issues placement access codes. Each code is single-use (or explicitly revocable), may expire, and starts a new test session when validated. Retakes require a **new** access code; prior attempts remain on record.

## Acceptance criteria

- [ ] Admin creates, lists, revokes, and optionally expires access codes
- [ ] Valid unused code starts a new placement session
- [ ] Used, expired, or revoked codes show a clear error and do not start a session
- [ ] Re-entering with a new code after a completed attempt creates a new attempt without deleting the prior one
- [ ] Admin can see code status (unused, in progress, completed, revoked)

## Blocked by

- GLC-001

---

### GLC-004 — Placement content management with preview

**Type:** AFK  
**Blocked by:** GLC-001  
**Requirements:** PRD § Fixed-form content structure, Staff add placement content; PRD proceed-now: Content & rubrics placeholders

## What to build

Staff (Admin / Academic Supervisor) add and maintain fixed-form placement content: reading passages and MCQs, Grammar/Vocabulary items, listening clips and MCQs, writing prompt, speaking prompt. Support text entry and PDF upload with **preview before use**. Use brief-aligned placeholders until GLC supplies final items.

## Acceptance criteria

- [ ] Staff can add and edit placement content for all five sections
- [ ] PDF (or equivalent) upload shows extracted/parsed preview before content is active
- [ ] Active placement form uses one fixed item set in fixed order for all candidates (no shuffle, no adaptive logic)
- [ ] Listening clips accept MP3/WAV upload and are stored for web-safe playback
- [ ] Placeholder content pack is sufficient to run an end-to-end test in staging

## Blocked by

- GLC-001

---

### GLC-005 — Placement entry, profile, privacy notice, and age gate

**Type:** AFK  
**Blocked by:** GLC-003  
**Requirements:** PRD § Entry and session, User Stories 1–5; CONTEXT § Placement candidate; PRD proceed-now: Privacy policy

## What to build

Placement candidate enters via link or access code, sees a privacy notice (PDPA-aligned; placeholder acceptable), submits minimal profile (name, **email required**, age), and receives pre-test instructions (sections, estimated time, speaking recording guidance). Candidates under 12 are blocked with a message to contact GLC. Device capability check verifies audio playback and microphone before proceeding.

## Acceptance criteria

- [ ] Valid access code leads to privacy notice then profile form
- [ ] Email is required; submission fails without valid email
- [ ] Under-12 candidates cannot proceed
- [ ] Instructions explain five sections, timing, and mobile speaking guidance
- [ ] Unsupported browser or missing microphone blocks start with clear messaging
- [ ] Privacy notice states data is not used to train AI models (per PRD)

## Blocked by

- GLC-003

---

### GLC-006 — Placement session persistence, timers, and auto-save

**Type:** AFK  
**Blocked by:** GLC-005  
**Requirements:** PRD § Entry and session, User Stories 7–9; Testing seam: Access-code placement journey (partial)

## What to build

After profile completion, the placement session supports continuous auto-save (immediate for objective answers, every five seconds for Writing), 24-hour same-device pause/resume, per-section time limits (Reading 15, G/V 12, Listening 10, Writing 25, Speaking 8 minutes — configurable), and 30-minute inactivity pauses section timers.

## Acceptance criteria

- [ ] Objective answers persist immediately on selection/entry
- [ ] Writing text auto-saves at least every five seconds
- [ ] Candidate can leave and resume within 24 hours on the same device and continues where they left off
- [ ] Per-section timers enforce configured limits
- [ ] 30 minutes of inactivity pauses active section timer until candidate returns
- [ ] Brief disconnect does not lose saved answers

## Blocked by

- GLC-005

---

### GLC-007 — Reading section — fixed-form completion

**Type:** AFK  
**Blocked by:** GLC-004, GLC-006  
**Requirements:** PRD § Reading, User Stories 6, 10; Testing seam: Fixed-form integrity

## What to build

Candidate completes the Reading section as the first test section: two passages with multiple-choice questions (default six per passage). Fixed order, no skip of section order. Answers tie into session auto-save.

## Acceptance criteria

- [ ] Reading is the first section after instructions
- [ ] Two passages render with configured MCQ count (default 12 total)
- [ ] Candidate cannot advance to G/V without completing or explicitly finishing Reading per UX rules
- [ ] Responses are saved and restored on resume
- [ ] Section timer applies to Reading only

## Blocked by

- GLC-004
- GLC-006

---

### GLC-008 — Grammar/Vocabulary section — fixed-form completion

**Type:** AFK  
**Blocked by:** GLC-007  
**Requirements:** PRD § Grammar/Vocabulary, User Stories 6, 11

## What to build

Second section: standalone Grammar/Vocabulary MCQs (default 22), mixed formats (fill-blank, error identification, sentence correction), all auto-scored.

## Acceptance criteria

- [ ] G/V follows Reading in fixed order
- [ ] Configured item count and formats render correctly
- [ ] All responses auto-save and restore on resume
- [ ] Section timer applies to G/V only

## Blocked by

- GLC-007

---

### GLC-009 — Listening section — single-play audio

**Type:** AFK  
**Blocked by:** GLC-008  
**Requirements:** PRD § Listening, User Stories 6, 12; Testing seam: Fixed-form integrity

## What to build

Third section: two audio clips with MCQs per clip. Each clip plays **once** with no replay. Real approved audio from placement content management.

## Acceptance criteria

- [ ] Listening follows G/V in fixed order
- [ ] Each clip allows exactly one play attempt per candidate per clip
- [ ] UI clearly indicates single-play rule before playback
- [ ] MCQs per clip match configured content (default five per clip)
- [ ] Audio plays on mobile browsers supported by PRD

## Blocked by

- GLC-008

---

### GLC-010 — Writing section — word limits and paste block

**Type:** AFK  
**Blocked by:** GLC-009  
**Requirements:** PRD § Writing, User Stories 13–14; Testing seam: Fixed-form integrity

## What to build

Fourth section: one essay prompt, minimum 150 words enforced, maximum 250 with soft warning, paste blocked in the writing area.

## Acceptance criteria

- [ ] Writing follows Listening in fixed order
- [ ] Candidate cannot submit below 150 words
- [ ] Soft warning appears above 250 words; submission still allowed per PRD
- [ ] Paste into writing area is blocked
- [ ] Essay text auto-saves and restores on resume

## Blocked by

- GLC-009

---

### GLC-011 — Speaking section — recording, quality checks, retries

**Type:** AFK  
**Blocked by:** GLC-010  
**Requirements:** PRD § Speaking, User Stories 15–16; PRD proceed-now: Content & rubrics (calibration samples)

## What to build

Fifth section: one speaking prompt, up to three minutes per recording, up to three submission attempts where failed quality checks (too quiet, silent, distorted) do not count against the attempt limit. Recorded audio only — no live AI voice.

## Acceptance criteria

- [ ] Speaking follows Writing in fixed order
- [ ] Candidate can record up to three valid attempts after quality check failures
- [ ] Clear messages for too quiet, silent, or distorted recordings
- [ ] Final submission stores audio for staff review
- [ ] Placeholder prompt and rubric reference suffice until GLC delivers calibration pack (HITL content swap before Phase 1B integration testing)

## Blocked by

- GLC-010

---

### GLC-012 — Placement submit and candidate pending-review state

**Type:** AFK  
**Blocked by:** GLC-011  
**Requirements:** PRD § Results, User Stories 17–18; Testing seam: Access-code placement journey

## What to build

On completion of all sections, candidate sees only a **submitted, pending GLC review** message. No scores, levels, skill breakdowns, or AI confidence are shown to the candidate.

## Acceptance criteria

- [ ] Successful completion shows pending-review messaging only
- [ ] No provisional scores or GLC levels are visible to the candidate on completion or via URL guessing
- [ ] Submission enters staff review queue as pending
- [ ] Candidate cannot self-serve download PDF before staff send

## Blocked by

- GLC-011

---

### GLC-013 — Objective scoring and composite GLC level calculation

**Type:** AFK  
**Blocked by:** GLC-012  
**Requirements:** PRD § Scoring and levels; CONTEXT § GLC level

## What to build

Auto-score objective sections (Reading, G/V, Listening) as percentage per section. Compute composite placement score with equal 20% weight per section (Writing and Speaking use provisional values when available). Map composite to seven GLC proficiency levels using planning default bands (~15% per level). Flag high cross-section variance for supervisor attention. No automated section minimums.

## Acceptance criteria

- [ ] Objective sections produce per-section percentage scores
- [ ] Composite uses equal 20% weights across five sections
- [ ] Mapped GLC level uses seven levels (Starter through Advanced)
- [ ] High variance across sections flags submission for supervisor review
- [ ] Scores remain staff-only until review workflow releases them

## Blocked by

- GLC-012

---

### GLC-014 — Provisional AI drafts for Writing and Speaking (staff-only)

**Type:** AFK  
**Blocked by:** GLC-013  
**Requirements:** PRD § Scoring and levels, User Story 24; PRD proceed-now: Staff-only AI confidence

## What to build

Generate provisional AI evaluation drafts for Writing (five dimensions, 1–5 internally) and Speaking from essay text and audio/transcript. Drafts and any internal confidence or review flags are visible to staff only, never to candidates or parents.

## Acceptance criteria

- [ ] Writing submission triggers provisional AI draft with five dimension scores
- [ ] Speaking submission triggers provisional AI draft from recording/transcript
- [ ] Drafts appear on staff review screen, not candidate UI
- [ ] AI confidence display (if any) is staff-only per proceed-now assumption
- [ ] Failure to generate draft does not auto-release results to candidate

## Blocked by

- GLC-013

---

### GLC-015 — Staff review queue, override, and internal notes

**Type:** AFK  
**Blocked by:** GLC-014  
**Requirements:** PRD § Scoring and levels, User Stories 23–28, 40; Testing seam: Staff approval ? result delivery (partial)

## What to build

Unified staff review queue with filters. Teachers review assigned submissions; Academic Supervisors cover full teacher capabilities plus flagged cases. Staff see AI drafts, transcripts, and recordings. Staff may override scores and levels with mandatory reason and audit log. Internal review notes are staff-only.

## Acceptance criteria

- [ ] Single pending-review list with filters (status, assignee, flags, date)
- [ ] Teacher sees submissions assigned to them; supervisor sees broader queue including flagged items
- [ ] Staff can confirm or override provisional scores and final GLC level with logged reason
- [ ] Internal notes are not included on parent-facing PDF
- [ ] Mandatory staff review is required for every submission before release (not borderline-only)
- [ ] Flagged placements (borderline, variance, integrity) surface to Academic Supervisor

## Blocked by

- GLC-014

---

### GLC-016 — Structured reviewer narrative with staff-only AI draft helper

**Type:** AFK  
**Blocked by:** GLC-015  
**Requirements:** PRD § Results and PDF, User Story 25; PRD proceed-now: Skill-by-skill summary on PDF

## What to build

Staff edit structured reviewer narrative fields for the Placement Test Result. Optional staff-only AI narrative draft may pre-fill fields; staff must edit and approve before PDF generation. No unreviewed AI prose on the PDF.

## Acceptance criteria

- [ ] Review UI provides structured narrative fields (not one uncontrolled blob)
- [ ] AI narrative draft, if shown, is clearly staff-only helper text
- [ ] PDF cannot generate until narrative fields are staff-approved
- [ ] Approved PDF includes per-skill levels plus overall GLC level (layout refinements deferred per proceed-now assumptions)

## Blocked by

- GLC-015

---

### GLC-017 — Placement Test Result PDF and staff-triggered secure link

**Type:** AFK  
**Blocked by:** GLC-016  
**Requirements:** PRD § Results and PDF, User Stories 18–20, 29; CONTEXT § Staff-triggered PDF delivery, Secure result link; PRD proceed-now: PDF branding footer

## What to build

Generate **Placement Test Result** PDF with GLC logo (placeholder branding until assets arrive). After staff approval, teacher or supervisor **explicitly triggers send** — no automatic send on approval. System emails one transactional message with secure link valid **30 days** for view/download. Retake only via new access code.

## Acceptance criteria

- [ ] PDF title is "Placement Test Result" (not certificate); no director signature in Phase 1
- [ ] Send action is explicit staff click after approval
- [ ] Email contains secure time-limited link (30 days)
- [ ] Candidate can view/download PDF within validity period without a persistent account
- [ ] No WhatsApp or auto parent email in Phase 1
- [ ] Expired link shows clear message to contact GLC

## Blocked by

- GLC-016

---

### GLC-018 — Placement integrity signals (tab switch, dual device)

**Type:** AFK  
**Blocked by:** GLC-006  
**Requirements:** PRD § Anti-integrity; User Story 40 (integrity flags)

## What to build

During placement, warn on tab switches and record flags. Detect simultaneous use of the same access code from two devices: terminate sessions and flag for staff. Integrate flags into review queue.

## Acceptance criteria

- [ ] Tab-switch events warn candidate and create review flag
- [ ] Second device using same in-progress code terminates active session(s) and flags submission
- [ ] Integrity flags visible to staff in review queue
- [ ] Paste block in Writing covered in GLC-010; this slice covers tab/device rules

## Blocked by

- GLC-006

---

### GLC-019 — Curriculum hierarchy, upload, and Draft state

**Type:** AFK  
**Blocked by:** GLC-001  
**Requirements:** PRD § Curriculum content management, User Stories 36, 38; CONTEXT § Course ? Level ? Unit ? Lesson

## What to build

Admin and Academic Supervisor upload curriculum files (PDF, DOCX, TXT) tagged to Course ? Level ? Unit ? Lesson. Support single and bulk upload. New uploads start in **Draft** state. Students and tutor do not see Draft content.

## Acceptance criteria

- [ ] Content is tagged with full hierarchy path
- [ ] PDF, DOCX, and TXT uploads accepted
- [ ] Bulk upload reports per-file success/failure
- [ ] Draft content is not retrievable by tutor
- [ ] Teachers and students cannot upload curriculum in Phase 1

## Blocked by

- GLC-001

---

### GLC-020 — Curriculum preview, publish, archive, and version replace

**Type:** AFK  
**Blocked by:** GLC-019  
**Requirements:** PRD § Curriculum content management, User Stories 37–39; Testing seam: Content publish pipeline; PRD proceed-now: Curriculum remove

## What to build

Mandatory extracted-text preview before publish. Lifecycle: Draft ? Published ? Archived. Replace document on update and refresh searchable index. Archived content is removed from tutor retrieval; optional Admin hard delete per proceed-now assumption.

## Acceptance criteria

- [ ] Publish requires preview of extracted text
- [ ] Only Published content within scope is tutor-retrievable
- [ ] Archived content no longer appears in tutor retrieval
- [ ] Replacing a file updates version and searchable index
- [ ] Launch planning default (~30–50 documents) can be loaded in staging

## Blocked by

- GLC-019

---

### GLC-021 — Teacher assignment of course, level, and unit to students

**Type:** AFK  
**Blocked by:** GLC-002, GLC-020  
**Requirements:** PRD § Access and scope (Module B), User Stories 22, 51

## What to build

Teacher manually assigns each enrolled student to a course, level, and unit. Tutor retrieval is limited to **Published** materials within that assignment. Consent gate from GLC-002 must pass before student can open tutor.

## Acceptance criteria

- [ ] Teacher assigns course/level/unit per student
- [ ] Student without assignment sees prompt to contact teacher/admin
- [ ] Tutor scope matches assignment only
- [ ] Student blocked from tutor when guardian consent not confirmed (ages 12–17)

## Blocked by

- GLC-002
- GLC-020

---

### GLC-022 — Student tutor chat — English-only, scoped retrieval

**Type:** AFK  
**Blocked by:** GLC-021  
**Requirements:** PRD § Tutor behavior, User Stories 49–52, 56–58; Testing seam: Tutor curriculum boundary (partial)

## What to build

Authenticated student engages in English-only text chat for Reading, Writing, Grammar, and Vocabulary help. Tutor retrieves only Published materials in assigned scope; may combine lessons within scope when relevant. Student can resume prior conversations. Tutor refuses speaking/listening practice requests with clear Phase 1 messaging.

## Acceptance criteria

- [ ] Tutor responds in English only
- [ ] Answers cite or ground in Published materials within assigned course/level/unit
- [ ] Student can continue previous conversation threads
- [ ] Student can view own chat history
- [ ] Requests for speaking/listening practice are refused per Phase 1 scope
- [ ] Tutor response target under 3 seconds under normal conditions (planning target)
- [ ] Conversation rotation summarizes oldest 20 pairs when session exceeds 40 message pairs

## Blocked by

- GLC-021

---

### GLC-023 — Homework guidance guardrails and violation logging

**Type:** AFK  
**Blocked by:** GLC-022  
**Requirements:** PRD § Tutor behavior and guardrails, User Stories 53–54, 33; Testing seam: Tutor curriculum boundary

## What to build

Tutor never gives direct homework answers — hints, explanations, and Socratic guidance only. Firm redirect for off-topic, personal/social, inappropriate, future/unassigned unit, or speaking/listening requests. Log off-topic and policy violations for staff review. Notify teacher when guardrail patterns suggest persistent direct-answer seeking.

## Acceptance criteria

- [ ] Direct homework answer requests receive guidance without revealing the answer
- [ ] Off-topic and policy violations receive firm redirect back to lesson scope
- [ ] Violations are logged with student, timestamp, and category
- [ ] Teacher receives notification for persistent direct-answer seeking patterns
- [ ] Six refusal categories from PRD are distinguishable in logs

## Blocked by

- GLC-022

---

### GLC-024 — Tutor writing correction with inline highlights

**Type:** AFK  
**Blocked by:** GLC-022  
**Requirements:** PRD § Writing correction, User Stories 55, 31

## What to build

Student submits writing for tutor correction. System evaluates grammar, vocabulary, structure, coherence, and task completion; shows inline highlights and 1–5 feedback per dimension (no single letter grade). Submissions are stored for teacher review.

## Acceptance criteria

- [ ] Student can submit text for correction flow distinct from general chat
- [ ] Response includes inline highlights on issues
- [ ] Five dimensions each receive 1–5 feedback
- [ ] Teacher can view stored submissions and corrections for assigned students
- [ ] No IELTS-style 1–9 band on tutor writing feedback

## Blocked by

- GLC-022

---

### GLC-025 — Staff tutor visibility — chat history, writing, activity

**Type:** AFK  
**Blocked by:** GLC-023, GLC-024  
**Requirements:** PRD § Visibility, User Stories 30–32, 34–35; Testing seam: Role boundaries

## What to build

Teachers view full tutor chat history and writing submissions for **assigned** students only. Academic Supervisors and Admins view full chat history for **all** enrolled students. Basic activity: last active date and conversation count (no usage-time analytics).

## Acceptance criteria

- [ ] Teacher cannot view chat history of unassigned students
- [ ] Academic Supervisor and Admin can view all enrolled students’ chat history
- [ ] Activity summary shows last active date and conversation count per student
- [ ] No usage-time or weak-area analytics in Phase 1

## Blocked by

- GLC-023
- GLC-024

---

### GLC-026 — Admin data export and staff audit logging

**Type:** AFK  
**Blocked by:** GLC-001, GLC-015  
**Requirements:** PRD § Minors, privacy, ownership, User Stories 46–48; Testing seam: Export and audit; PRD proceed-now: Data deletion

## What to build

Admin exports placement items, curriculum, student records, recordings, transcripts, chat history, and reports in usable bundles. Sensitive staff actions (overrides, sends, consent changes, deletions) write audit log entries. Admin can delete or anonymize student records on request with audit trail (retention policy detail deferred).

## Acceptance criteria

- [ ] Export produces complete, usable data bundles without vendor lock-in
- [ ] Override level/score, result send, consent flag, and deletion actions are audited
- [ ] Admin can delete or anonymize a student record on request
- [ ] Deletion is logged with who/when/what

## Blocked by

- GLC-001
- GLC-015

---

### GLC-027 — Guardrail acceptance test harness (50 homework questions)

**Type:** HITL  
**Blocked by:** GLC-023  
**Requirements:** PRD § Guardrail UAT, Testing seam: Guardrail acceptance; PRD proceed-now: Content & rubrics (homework UAT questions)

## What to build

Runnable acceptance harness that executes 50 real GLC homework questions through the tutor guardrails and records direct-answer failures. Phase 1C go-live requires ?2 failures (5%) and signed staff checklist.

## Acceptance criteria

- [ ] Harness runs a defined set of 50 homework questions (GLC-supplied)
- [ ] Each run records pass/fail with evidence (response text, category)
- [ ] Report shows failure rate; gate fails if more than 2 direct-answer failures
- [ ] Staff can sign acceptance checklist in staging before go-live
- [ ] **HITL:** GLC must supply 50 homework questions before final UAT sign-off

## Blocked by

- GLC-023

---

### GLC-028 — Staging UAT, production deploy, backups, and handover

**Type:** HITL  
**Blocked by:** GLC-017, GLC-025, GLC-026  
**Requirements:** PRD § Shared infrastructure, Delivery phases, User Stories 59–63; PRD proceed-now: Deployment, docs, training

## What to build

Complete staging UAT with GLC staff across placement and tutor flows. Deploy production in Southeast Asia–appropriate hosting with daily backups. Deliver basic admin/teacher guide and handover session. Provide 30-day post-launch bug-fix support window and application source transfer on completion per contract.

## Acceptance criteria

- [ ] Staging UAT covers testing seams in PRD § Testing Decisions
- [ ] Production environment meets capacity targets (150 students, 50–75 concurrent sessions)
- [ ] Daily backups configured and restore tested once
- [ ] Data hosted in SEA region suitable for Malaysia; PDPA advisory documented
- [ ] Admin/teacher guide delivered (depth per GLC input)
- [ ] Handover session completed with GLC staff
- [ ] 30-day post-launch bug-fix period acknowledged
- [ ] Source code transfer on project completion per agreement
- [ ] **HITL:** GLC staff availability for UAT sessions; hosting account decisions; training schedule

## Blocked by

- GLC-017
- GLC-025
- GLC-026

---

## Suggested implementation waves

For planning only — not additional requirements.

| Wave | Issues | Outcome |
|------|--------|---------|
| **Wave 0 — Foundation** | GLC-001, GLC-002, GLC-003, GLC-004 | Platform login, users, codes, placement content |
| **Wave 1 — Placement candidate path** | GLC-005 ? GLC-012, GLC-018 | Full access-code test through pending review |
| **Wave 2 — Placement staff path** | GLC-013 ? GLC-017 | Scoring, review, PDF, secure link |
| **Wave 3 — Tutor content & student path** | GLC-019 ? GLC-024 | Curriculum pipeline, tutor chat, guardrails, writing correction |
| **Wave 4 — Staff oversight & compliance** | GLC-025, GLC-026 | Visibility, export, audit |
| **Wave 5 — Go-live** | GLC-027, GLC-028 | Guardrail UAT, deploy, handover |

---

## Traceability matrix (user stories ? issues)

| User stories | Primary issues |
|--------------|----------------|
| 1–20 (placement candidate) | GLC-003–GLC-017, GLC-018 |
| 21–33 (teacher) | GLC-001, GLC-015–GLC-017, GLC-021, GLC-023–GLC-025 |
| 34–40 (academic supervisor) | GLC-004, GLC-015, GLC-019–GLC-020, GLC-025 |
| 41–48 (admin) | GLC-001–GLC-004, GLC-026 |
| 49–58 (enrolled student) | GLC-001–GLC-002, GLC-021–GLC-024 |
| 59–63 (leadership / delivery) | GLC-001, GLC-028 |

---

## Publishing to an issue tracker (optional)

When copying to GitHub, GitLab, or similar:

1. Create issues in **dependency order** (GLC-001 first).
2. Put `GLC-###` in the title prefix, e.g. `GLC-007 Reading section — fixed-form completion`.
3. Paste the **What to build**, **Acceptance criteria**, and **Blocked by** sections into the issue body.
4. Add labels such as `ready-for-agent`, `phase-1`, `placement`, `tutor`, `hitl` as appropriate.
5. Link blocking issues using native tracker references after IDs exist.
6. Link this document and `GLC_PRD_Phase1.md` in a **Requirements** section on every issue.

Do not close or rewrite the PRD when publishing issues.

---

*Generated from `GLC_PRD_Phase1.md` using vertical-slice (tracer bullet) breakdown. Approved PRD proceed-now assumptions apply to all AFK slices until GLC supplies finals.*
