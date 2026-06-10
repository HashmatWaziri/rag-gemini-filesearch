# GLC AI Platform ù Phase 1 Product Requirements Document

**Version:** 1.0 (post-grill)  
**Date:** 10 June 2025  
**Status:** Authoritative for Phase 1 build  
**Sources:** `client-briefs/Proposal_Brief_AI_Placement_Test_24-7_AI_Tutor_GLC.md`, `client-briefs/GLC_Client_Scope_Clarifications_Follow_Up.md` (authoritative where they differ), `CONTEXT.md` (domain glossary), grill session Q1ùQ48  
**Supersedes:** `GLC_PRD_latest.md` for Phase 1 scope and behavior  

---

## Problem Statement

Greats Language Center (GLC) serves primarily Arabic-speaking English learners in Kuala Lumpur, from school-age through adult. Today, placement testing is slow and manual: candidates visit the center, sit paper-based assessments, and wait for staff to grade and communicate a level. Enrolled students lack structured English support outside class hours and often turn to unvetted tools that may contradict GLC's curriculum.

GLC needs a **standalone, mobile-first web platform** with two separately scoped modules on shared infrastructure:

1. **AI Placement Test** ù prospective students complete a fixed-form, multi-skill assessment via access code; staff review AI-assisted drafts before any parent-facing result is released.
2. **24/7 AI Tutor** ù enrolled students practise with a curriculum-grounded tutor that guides homework without giving direct answers; staff can review full chat history and writing submissions.

Many users are minors. GLC requires staff control over results, content, and data export; no unreviewed AI prose on reports bearing the GLC logo; and clear separation of Phase 1 lean scope from optional Phase 2 add-ons.

---

## Solution

Deliver a **lean Phase 1** web platform that:

- Lets **placement candidates** enter via **access code** (no persistent account), complete five sections in fixed order (Reading, Grammar/Vocabulary, Listening, Writing, Speaking), and see only **"submitted, pending GLC review"** until staff approve.
- Gives **staff** a unified review workflow: AI provisional drafts for Writing and Speaking, mandatory human confirmation of final GLC level, structured reviewer narrative (with optional staff-only AI draft as a starting point), and **staff-triggered** delivery of the **Placement Test Result** via secure time-limited link.
- Gives **enrolled students** an authenticated **English-only** tutor scoped to **teacher-assigned course/level/unit**, with homework guidance, writing correction, lesson explanation, and stored chat history.
- Lets **Admin and Academic Supervisor** upload, preview, publish, and archive curriculum materials (PDF, DOCX, TXT) tagged **Course ? Level ? Unit ? Lesson** without developer involvement for routine updates.
- Runs on **shared infrastructure** (authentication, hosting, admin, user management, data store) quoted separately from each module.

Build proceeds with **brief-aligned placeholders** where GLC has not yet supplied final content (test items, rubrics, branding assets). Client briefs are the complete Phase 1 requirements; development is not blocked waiting for content deliveries.

---

## User Stories

### Placement candidate

1. As a placement candidate, I want to enter the test using a link or access code, so that I can begin assessment without creating a full account.
2. As a placement candidate, I want to provide my name, email, and age, so that GLC can deliver results and apply minor safeguards.
3. As a placement candidate, I want to see a privacy notice at entry, so that I understand how my data is used and that it is not used to train AI models.
4. As a placement candidate, I want to be blocked from starting the test if I am under 12, so that GLC's age policy is enforced.
5. As a placement candidate, I want clear instructions before the test (sections, estimated time, speaking recording guidance), so that I know what to expect on a mobile device.
6. As a placement candidate, I want the test sections in a fixed order (Reading, Grammar/Vocabulary, Listening, Writing, Speaking), so that the experience is consistent for everyone.
7. As a placement candidate, I want my answers auto-saved, so that I do not lose work if I lose connection briefly.
8. As a placement candidate, I want to pause and resume within 24 hours on the same device, so that I can complete the test if interrupted.
9. As a placement candidate, I want per-section timers that pause when I am inactive, so that I am not penalized for necessary breaks.
10. As a placement candidate, I want to complete two reading passages with multiple-choice questions, so that my reading level can be assessed.
11. As a placement candidate, I want to complete standalone Grammar/Vocabulary multiple-choice items, so that my language accuracy can be assessed.
12. As a placement candidate, I want to listen to real approved audio clips once per clip (no replay), so that listening assessment is fair and fixed-form.
13. As a placement candidate, I want to write one essay within the required word count, so that my writing can be assessed.
14. As a placement candidate, I want paste blocked in the writing area, so that externally prepared text cannot be inserted.
15. As a placement candidate, I want to record one speaking response with up to three attempts (failed quality checks not counting), so that I can submit a clear recording on my phone.
16. As a placement candidate, I want helpful messages if my recording is too quiet, silent, or distorted, so that I can fix technical issues.
17. As a placement candidate, I want to see only "submitted, pending GLC review" after finishing, so that I am not shown unreviewed AI scores or levels.
18. As a placement candidate, I want to receive a secure link to my approved Placement Test Result only after staff send it, so that results are official and staff-controlled.
19. As a placement candidate, I want to view or download my approved PDF within the link validity period, so that I can share it with GLC for enrollment.
20. As a placement candidate, I want to retake only when staff issue a new access code, so that retakes are deliberate and prior attempts are preserved.

### Teacher

21. As a teacher, I want to log in with a single assigned role, so that my permissions are clear.
22. As a teacher, I want to assign course and unit to my enrolled students, so that the tutor uses the correct materials.
23. As a teacher, I want to review placement submissions assigned to me, so that I can confirm or adjust levels before results go out.
24. As a teacher, I want to see AI provisional writing and speaking drafts plus transcripts and recordings, so that I can make informed review decisions.
25. As a teacher, I want to edit and approve structured reviewer narrative fields, so that parent-facing PDF text is mine or explicitly approved by me.
26. As a teacher, I want to override AI-suggested scores and levels with a logged reason, so that borderline cases are handled professionally.
27. As a teacher, I want to add internal review notes visible to staff only, so that I can document context for supervisors.
28. As a teacher, I want a single pending-review list with filters, so that I can work through placements efficiently.
29. As a teacher, I want to explicitly trigger result delivery after approval, so that sending is intentional.
30. As a teacher, I want to view full tutor chat history for my assigned students, so that I can monitor how they use the tutor.
31. As a teacher, I want to view tutor writing submissions and corrections for my students, so that I can support their learning.
32. As a teacher, I want to see basic activity (last active date, conversation count), so that I know which students are engaging.
33. As a teacher, I want to be notified when guardrail patterns suggest a student is persistently seeking direct answers, so that I can intervene.

### Academic Supervisor

34. As an Academic Supervisor, I want all teacher placement review capabilities, so that I can cover review workload.
35. As an Academic Supervisor, I want to see full chat history for all enrolled students, so that I can audit tutor quality.
36. As an Academic Supervisor, I want to upload and manage curriculum content, so that materials stay current without developers.
37. As an Academic Supervisor, I want to preview extracted text before publishing curriculum PDFs, so that the tutor receives accurate content.
38. As an Academic Supervisor, I want Draft, Published, and Archived states for content, so that students only see ready materials.
39. As an Academic Supervisor, I want to replace document versions and have the system refresh searchable content, so that updates propagate correctly.
40. As an Academic Supervisor, I want to review flagged placements (borderline scores, variance, integrity flags), so that sensitive cases get senior attention.

### Admin

41. As an Admin, I want all Academic Supervisor capabilities plus user management, so that I operate the platform day to day.
42. As an Admin, I want to create staff and student accounts manually or via bulk upload, so that onboarding does not require public signup.
43. As an Admin, I want exactly one role per user (Admin, Academic Supervisor, Teacher, or Student), so that permissions stay simple.
44. As an Admin, I want to issue and manage placement access codes, so that candidates can enter controlled test sessions.
45. As an Admin, I want to mark guardian consent confirmed for users aged 12ù17, so that minor policy is enforced before tutor access or result send.
46. As an Admin, I want to export placement items, curriculum, student records, recordings, transcripts, chat history, and reports, so that GLC owns its data without vendor lock-in.
47. As an Admin, I want audit logs of staff actions, so that accountability and PDPA compliance are supported.
48. As an Admin, I want to configure system settings within defined bounds, so that the center can operate independently after handover.

### Enrolled student

49. As an enrolled student, I want to log in securely, so that only I access my tutor and history.
50. As an enrolled student, I want the tutor to respond in English only, so that practice matches GLC's classroom language policy.
51. As an enrolled student, I want answers grounded in my assigned course/level/unit materials, so that help matches what I am studying.
52. As an enrolled student, I want lesson explanations and curriculum Q&A, so that I can understand concepts outside class.
53. As an enrolled student, I want homework guidance that never gives the direct answer, so that I learn rather than copy.
54. As an enrolled student, I want off-topic requests redirected firmly to my lesson, so that the tutor stays on task.
55. As an enrolled student, I want to submit writing for correction with inline highlights and dimension feedback, so that I see how to improve.
56. As an enrolled student, I want to resume prior tutor conversations, so that I can continue learning across sessions.
57. As an enrolled student, I want to see my own chat history, so that I can review past help.
58. As an enrolled student, I want the tutor to refuse speaking/listening practice in Phase 1, so that expectations match what GLC offers today.

### Shared / cross-cutting

59. As GLC leadership, I want the Placement Test and AI Tutor quoted and delivered as separate modules on shared infrastructure, so that costs and scope are transparent.
60. As GLC leadership, I want a staging environment for UAT before go-live, so that staff can validate workflows safely.
61. As GLC leadership, I want daily backups and Southeast Asiaùappropriate data hosting with PDPA advisory, so that minor student data is handled responsibly.
62. As GLC leadership, I want 30 days post-launch bug-fix support, so that launch issues are addressed.
63. As GLC leadership, I want application source code transferred on completion, so that GLC controls its bespoke investment.

---

## Implementation Decisions

### Authority and phasing

- **Client briefs are the complete Phase 1 specification.** Scope Clarifications override the original proposal brief where they differ.
- **Phase 1 is lean and fixed-form.** Adaptive placement, auto-delivery integrations, quizzes, analytics dashboards, and native apps are Phase 2 (priced separately).
- **Placeholders during build:** Default section counts, rubrics, level descriptors, branding assets, and sample content may use brief-aligned placeholders until GLC supplies finals; build must not wait on deliveries.
- **Commercial structure:** Separate line items for shared infrastructure, Placement Test module, AI Tutor module, hosting, usage, maintenance, and Phase 2 options.

### Shared infrastructure

- Standalone platform: no integration with greatscenter.com, Google Workspace, legacy student databases, or public marketing site in Phase 1.
- Simple access-code entry page for placement (private or code-gated, not a full public website).
- Role-based access: Admin, Academic Supervisor, Teacher, Student; one role per account; placement candidates are not RBAC users.
- Production plus staging/UAT environment; daily backups; standalone accounts (no SSO in Phase 1).
- Capacity: up to **150 enrolled students**; **50 concurrent** active sessions normal peak, **75** during placement intake bursts; tutor responses under **3 seconds**, page loads under **2 seconds** under normal conditions.
- Modern mobile browsers supported; mobile-first layout is primary.

### Module A ù AI Placement Test

**Entry and session**

- Access code + minimal profile (name, **email required**, age for minor checks).
- No candidate portal or persistent candidate login.
- Session: 24-hour same-device pause/resume; continuous auto-save (immediate for objective items, every five seconds for Writing); reconnect resumes exactly where left off; 30-minute inactivity pauses timers.
- Per-section time limits (configurable): Reading 15 min, Grammar/Vocabulary 12 min, Listening 10 min, Writing 25 min, Speaking 8 min; no separate overall cap beyond 24-hour window.
- Anti-integrity: tab-switch warnings and flags, paste block in Writing, simultaneous access-code use from two devices terminates sessions and flags for staff.
- Retake: new access code issued by staff; prior attempts retained.

**Fixed-form content structure (planning defaults)**

| Section | Default structure |
|---------|-------------------|
| Reading | 2 passages (~250ù400 words); 5ù8 MCQs per passage (default 6) ? 12 MCQs |
| Grammar/Vocabulary | 20ù25 standalone MCQs (default 22); fill-blank, error ID, sentence correction; all auto-scored |
| Listening | 2 clips (~2ù3 min); real approved audio; 4ù6 MCQs per clip (default 5) ? 10 MCQs; **one play, no replay**; accept MP3/WAV upload, deliver web-safe audio for playback |
| Writing | 1 essay; 150ù250 words (min 150 enforced, max 250 soft warning); GLC rubric; paste blocked |
| Speaking | 1 prompt; up to 3 min per recording; up to 3 attempts (failed quality checks excluded); recorded only (no live AI voice) |

- Same items, same order for every candidate (no adaptive logic, no shuffle).
- Staff add placement content via text entry or PDF upload with **preview before use**.

**Scoring and levels**

- Objective sections: auto-scored percentage per section.
- Writing/Speaking: **provisional AI draft** (five writing dimensions scored 1ù5 internally); staff-only until review.
- Composite: **equal 20% weight** per section; maps to seven **GLC proficiency levels** (Starter through Advanced) using ~15% bands (Starter 0ù14% ù Advanced 90ù100%) as planning default.
- **No automated section minimums**; high cross-section variance flags supervisor review.
- **Mandatory staff review** for every submission before any parent-facing outputùnot borderline-only.
- Staff may override scores/levels with **mandatory audit log** and internal notes.

**Results and PDF**

- Post-submit: candidate sees **only** pending-review messageùno scores, levels, or AI confidence.
- PDF title: **"Placement Test Result"** (not certificate); GLC logo; **no director signature** in Phase 1.
- **Reviewer narrative:** structured staff-edited fields; **AI narrative draft** is staff-only helperùnever unreviewed on PDF.
- Delivery: **staff-triggered** transactional email with **secure result link (30 days)**; no automatic send on approval; no WhatsApp/auto parent emails in Phase 1.
- Unified **staff review queue** with filters.

### Module B ù AI Tutor

**Access and scope**

- Authenticated enrolled students only; teacher manually assigns **course/level/unit**.
- Tutor is **English-only**; text chat for **Reading, Writing, Grammar, Vocabulary** onlyùno speaking/listening practice in Phase 1.
- Retrieval limited to **Published** materials tagged within the student's assigned scope; may combine across lessons within that scope when relevant.

**Curriculum content management**

- Upload: single file and bulk; formats **PDF, DOCX, TXT**; GLC-created summaries, worksheets, notes, selected approved PDFsùnot full textbook ingestion.
- Hierarchy: Course ? Level ? Unit ? Lesson.
- Lifecycle: **Draft ? Published ? Archived**; mandatory **extracted-text preview** before publish.
- Versioning: **replace on update**; refresh searchable index when content changes; conversation rotation when active sessions exceed **40 message pairs** (summarize oldest 20).
- Launch corpus planning default: **~30ù50 documents / ~300ù500 pages** across one or two courses.

**Tutor behavior and guardrails**

- **Never** give direct homework answers; use hints, explanations, and Socratic guidance.
- **Firm redirect** for off-topic, personal/social, inappropriate, future/unassigned unit, or speaking/listening practice requests.
- **Log** off-topic and policy violations for staff review.
- **Writing correction:** evaluate grammar, vocabulary, structure, coherence, task completion; **inline highlights**; save submissions for teachers; **1ù5 dimension feedback** (no single letter grade).
- **Guardrail UAT:** 50 real GLC homework questions before Phase 1C; ?2 direct-answer failures (5%); signed acceptance checklist before go-live.

**Visibility**

- **Full chat history:** teachers (assigned students), Academic Supervisors and Admin (all students).
- Basic activity: last active date and conversation count (no usage-time analytics in Phase 1).

### Minors, privacy, ownership

- Ages 12ù17: guardian name and email collected; **Admin manual consent flag** before tutor access or result send; no automated consent email workflow in Phase 1.
- PDPA-aligned privacy notice at placement entry and student onboarding.
- Data hosted in **Southeast Asia** region suitable for Malaysia; business-tier AI usage with no training on student content where available.
- GLC owns all data and exports; **code transfers** on project completion and final payment.
- Audit logging for sensitive staff actions.

### Branding and UX deliverables

- Mobile-first **wireframes** for placement candidate flow, student tutor flow, and staff/admin flows before build (not a marketing website).
- GLC provides logo, letterhead/footer design, contact details, and brand colors when available; system uses placeholders until received.
- Logo on Placement Test Result PDF and staff/student login surfaces.

### Delivery phases (behavioral milestones)

1. **Requirements and wireframes** ù brief-aligned spec confirmed (this document).
2. **Placement Test build** ù access-code flow through staff review with placeholders.
3. **Integration testing** ù real speaking pack, rubrics, and calibration samples integrated when GLC supplies them.
4. **AI Tutor build** ù content pipeline, tutor chat, writing correction, guardrails.
5. **Guardrail UAT** ù 50-question acceptance test.
6. **Go-live** ù production with 30-day bug-fix support.

---

## Testing Decisions

### What makes a good test

- Test **observable behavior** from the user perspective: what candidates, students, and staff can do and see.
- Do **not** assert internal implementation details (storage format, prompt text, indexing mechanics).
- Prefer **end-to-end flows** across module boundaries (e.g., complete placement ? staff approve ? secure link works).
- Use **realistic mobile viewport** for candidate and student flows.

### Testing seams (highest practical level)

| Seam | What to verify |
|------|----------------|
| **Access-code placement journey** | Code entry ? all five sections ? pending review screen ? no leaked scores |
| **Staff approval ? result delivery** | Review ? narrative approval ? staff-triggered send ? secure link ? PDF content |
| **Fixed-form integrity** | Same order/items; listening single-play; writing word limits; speaking retry rules |
| **Tutor curriculum boundary** | Assigned unit only; refusal of direct homework answers; off-topic redirect |
| **Guardrail acceptance** | 50 GLC homework questions; ?5% direct-answer failure rate |
| **Role boundaries** | Teacher vs supervisor visibility; no student upload; one role per user |
| **Content publish pipeline** | Upload ? preview ? publish ? tutor cites published material |
| **Export and audit** | Admin export produces usable bundles; override actions logged |

### Modules covered by acceptance testing

- Placement Test (candidate + staff review + PDF/link)
- AI Tutor (student chat + writing correction + staff visibility)
- Content management (upload, lifecycle, preview, replace)
- User management and consent gates
- Shared authentication and role enforcement

### Prior art

- This repository is specification-only; no application test suite exists yet. Acceptance tests should be defined alongside first implementation and re-run in staging before each release.

---

## Out of Scope (Phase 1)

- Adaptive placement testing and item-bank psychometrics
- Live AI voice conversation (placement or tutor)
- AI-generated listening audio for placement
- Speaking/listening practice inside the tutor
- AI-generated quizzes and revision exercises
- Weak-area analytics, usage-time tracking, AI progress summaries
- Full licensed textbook ingestion (Beehive, Oxford, English Hub at scale)
- WhatsApp integration and automatic email/WhatsApp PDF delivery
- Google Workspace integrations (Sheets, Forms, Drive, Gmail)
- Payment integration
- Native iOS/Android apps
- Parent dashboard and gamification
- Public marketing website, homepage, SEO, self-signup
- Candidate portal or persistent candidate accounts
- SSO / external identity integration
- Director signature on Placement Test Result PDF
- Automated parental consent emails
- Weekly/monthly automated staff summary emails
- Developer-authored assessment rubrics or test items (GLC owns content design)
- IELTS-style 1ù9 band scoring on tutor writing feedback

All above may be quoted separately as **Phase 2** optional add-ons per client brief ù9.

---

## Further Notes

### Document maintenance

- Domain vocabulary lives in `CONTEXT.md`; update glossary when terminology changes.
- Gap register at `docs/gap-register.md` tracks PRD-vs-brief reconciliation history.
- `GLC_PRD_latest.md` is retained for reference but **this document** is authoritative for Phase 1.

### Issue tracker

**Implementation issues (standalone markdown):** [`docs/GLC_Phase1_Issues.md`](docs/GLC_Phase1_Issues.md) ó 28 vertical slices (`GLC-001`ñ`GLC-028`) derived from this PRD. Portable to any issue tracker; references client briefs and this PRD. Copy to GitHub/GitLab/Jira as needed and label `ready-for-agent`.

### Proceed-now assumptions (GLC to provide later)

The items below are **not blockers** for Phase 1 build. Sensible defaults apply until GLC supplies final requirements. This PRD is **approved to proceed as-is**.

| Topic | Proceed-now assumption | GLC provides later |
|-------|------------------------|-------------------|
| **Skill-by-skill summary on PDF** | Approved Placement Test Result includes per-skill levels (Reading, Grammar/Vocabulary, Listening, Writing, Speaking) plus overall GLC level | Exact PDF layout and field labels |
| **Staff-only AI confidence** | Review screen may show internal draft confidence or review flags; never shown to candidate/parent | Whether confidence is numeric, categorical, or omitted |
| **Data deletion** | Admin can delete or anonymize student records on request; deletion is audited | Retention periods and deletion policy detail |
| **Deployment, docs, training** | Included in delivery: production deploy, staging env, basic admin/teacher guide, handover session | Depth of training and documentation format |
| **PDF branding footer** | GLC logo on PDF; placeholder contact/footer until assets arrive | Logo file, letterhead, footer text, contact details, brand colors |
| **Tutor adapts to level** | Tutor stays within teacher-assigned course/level/unit and uses Published materials at that scope | Any extra adaptation rules beyond assignment |
| **Curriculum remove** | **Archived** state removes content from tutor retrieval; hard delete optional for Admin | Whether hard delete is required or archive-only is enough |
| **Content & rubrics** | Brief-aligned placeholders for test items, rubrics, level guide, curriculum corpus | Final items, rubrics, calibration samples, homework UAT questions |
| **Privacy policy** | PDPA-aligned notice at entry/onboarding | Formal privacy policy text for consent references |
| **AI usage & hosting costs** | GLC owns usage account; usage-based costs accepted | Commercial/hosting line items live in proposal, not this PRD |

### Assumptions requiring GLC input (non-blocking)

- Final placement item counts, rubric text, level descriptor document, branding assets, and curriculum inventory may refine planning defaults without changing Phase 1 behavior contracts above.
- GLC maintains its own AI usage account and accepts usage-based costs (advisory only in this PRD).
- Privacy policy text for consent references to be supplied by GLC legal/compliance.

### Grill traceability

All Phase 0 decisions from grill Q1ùQ48 are incorporated. Key client-alignment rules: staff-triggered results, no candidate-facing AI output before approval, full tutor chat for staff, fixed-form test, homework never direct-answer, English-only tutor, standalone platform, four roles one per user.
