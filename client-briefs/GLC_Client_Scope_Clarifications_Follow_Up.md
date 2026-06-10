# GLC Client Follow-Up: Scope Clarifications

Thank you for the questions — they helped us clarify the scope.

Before we go further, one important framing point: we see this as two separate modules that may share the same platform/infrastructure, but should be scoped and quoted separately:

1. AI Placement Test
2. AI Tutor for enrolled students

They have different users, workflows, and risk levels, so we would appreciate it if you can separate them in your proposal and cost estimate.

At the same time, we expect both modules to share common infrastructure such as authentication, hosting, admin panel, user management, and database where appropriate. Please quote this shared layer as its own line item so the module costs do not overlap or get double-counted.

---

## 1. Learning Materials

Our materials are currently available in multiple formats. We have high-quality PDFs of student books, workbooks, teacher books, and some official assessment sheets. We also have access to official digital resources through English Hub.

However, for Phase 1, we prefer to start with GLC-created materials, summaries, notes, worksheets, and selected approved PDFs. Full textbook ingestion can be considered later after licensing review.

The content should be organized as:

**Course ? Level ? Unit ? Lesson**

Admin and Academic Supervisor should be able to upload and manage content. Teachers and students should not have upload/edit access in Phase 1.

## 2. Updating Content Without Developer Support

Our goal is for GLC to update content without needing developer support each time.

Ideally, Admin / Academic Supervisor can log in, upload a PDF or document, tag it by course/level/unit/lesson, and make it available to the AI Tutor.

## 3. Current Website / Database

For Phase 1, please assume this is a standalone system.

We do not need deep integration with our website (greatscenter.com), Google Sheets, Google Forms, Gmail, Google Drive, or an existing student database in Phase 1. Those can be considered in Phase 2.

## 4. Public Pages

We do not need a full public website, homepage, about page, etc.

For the Placement Test, we may need a simple entry page. This can be access-code based or private at first, rather than fully public.

## 5. Workflow

### Placement Test

The intended workflow is:

1. Student enters test through link/access code
2. Completes Reading, Grammar/Vocabulary, Listening, Writing, and Speaking sections
3. AI provides provisional scoring where appropriate
4. Teacher / Academic Supervisor reviews
5. GLC confirms or adjusts the final level
6. PDF "Placement Test Result" is generated with GLC logo

GLC will provide the initial placement test content/items for Phase 1. If test-item design, assessment validation, or adaptive item-bank development is needed later, please quote that separately.

The AI result should not be final until reviewed by GLC. Any AI confidence/review status can be visible internally to staff, but not shown to the student/parent.

Any written explanation shown on the final PDF should be written or approved by the reviewing teacher / Academic Supervisor. We do not want unreviewed AI-generated prose to appear on a parent-facing PDF under the GLC logo.

**Speaking (Phase 1):** We prefer recorded responses rather than live AI voice conversation:

1. Student records answers to prompts
2. AI transcribes
3. Teacher / Academic Supervisor reviews recording + transcript
4. Final speaking level is confirmed

**Listening (Phase 1):** We prefer real recorded audio or approved audio files for assessment purposes. AI-generated audio can be considered later for practice, but not as the main placement-test listening assessment in Phase 1.

### AI Tutor

The intended workflow is:

1. Enrolled student logs in
2. Teacher assigns course/unit manually
3. Student practises with the AI Tutor
4. AI answers based on approved GLC materials
5. AI gives guidance, explanations, writing correction, and homework support without simply giving direct answers
6. Teacher/admin can review chat history and writing submissions

For "homework guidance, not direct answers," we would like to agree on how this behavior will be tested and accepted before final delivery.

## 6. Web-Based / Mobile-Friendly

Correct. We want a web app only, mobile-first.

No native iOS or Android app is required in Phase 1.

## 7. Accounts

Placement Test candidates do not need full accounts in Phase 1. A link or access code is enough.

However, AI Tutor users do need accounts because the tutor is only for enrolled students, and teachers need to view student history and assign course/unit. Admin should be able to create accounts manually or by bulk upload. No public self-signup is required in Phase 1.

## 8. Phase 1 Scope We Would Like Quoted

Please quote the following as the lean Phase 1 build:

### A. AI Placement Test — Phase 1

- Access-code entry
- Fixed-form test, not adaptive yet
- Reading
- Grammar/Vocabulary
- Listening using real/approved audio
- Writing with AI provisional score + teacher review
- Speaking via recorded responses + AI transcription + teacher review
- Staff review screen
- Final approved level
- Auto-generated PDF "Placement Test Result"
- GLC logo on PDF
- No director signature

### B. AI Tutor — Phase 1

- Authenticated login for enrolled students
- Teacher manually assigns course/unit
- Curriculum-grounded chat using approved GLC materials
- Lesson explanation
- Curriculum Q&A
- Writing correction
- Homework guidance, not direct answer-giving
- English-only tutor
- Stored chat history
- Teacher/admin can view chat history and writing submissions
- Content upload by Admin / Academic Supervisor only

## 9. Phase 2 / Optional Add-Ons

Please price these separately as optional Phase 2 items:

- Adaptive placement testing
- Live AI voice conversation
- AI-generated audio for practice
- Speaking/listening practice inside the tutor
- AI-generated quizzes
- Weak-area analytics
- Usage-time tracking
- AI progress summaries
- Full textbook ingestion after licensing review
- WhatsApp integration
- Google Workspace integrations
- Automatic email/WhatsApp sending of PDFs
- Payment integration
- Native mobile app
- Parent dashboard
- Gamification

## 10. Privacy, Data, and Ownership

Because many students are minors, please advise on:

- Where student data will be stored
- How voice recordings, writing submissions, and chat history will be protected
- Whether the AI provider uses student data for model training
- Whether zero-retention or business-tier API options are available
- Role-based access controls
- Data export/deletion
- Audit logging
- Code ownership and data ownership

We would also like confirmation that GLC can export placement test items, tagged curriculum content, student records, voice recordings, transcripts, chat history, and reports in a clean, usable format that does not depend on continued access to your internal tooling.

---

## Proposal & Cost Structure Requested

Please separate:

- Shared infrastructure cost
- AI Placement Test module build cost
- AI Tutor module build cost
- Monthly hosting cost
- Monthly AI/API usage cost
- Maintenance/support cost
- Optional Phase 2 add-ons

For now, we would like a lean Phase 1 quote per module, with Phase 2 features priced separately.

Thanks.
