# ADR 0001: Tutor usage-time via message-gap heuristic

**Status:** Accepted  
**Date:** 2026-06-16  
**Context:** Phase 2 tutor analytics requires approximate active minutes without invasive client timers or session beacons.

## Decision

Record **active minutes** on each student tutor message by summing gaps between consecutive user messages in the same conversation when the gap is ≤ 5 minutes. The first message in a conversation counts as 1 minute. Daily totals cap at 120 minutes per student.

Persist rollups in `tutor_usage_daily` (`user_id`, `date`, `active_minutes`, `message_count`, `conversation_starts`).

## Consequences

- **Pros:** No frontend changes; privacy-friendly; cheap SQL aggregation; degrades gracefully when analytics flag is off.
- **Cons:** Approximate only (ignores reading time without messages); cross-conversation gaps are not merged into one session.
- **Alternatives rejected:** Browser heartbeat (UX/privacy cost); Laravel AI agent summaries for usage time (wrong tool; cost).

## Feature flag

`GLC_TUTOR_PROGRESS_ANALYTICS=false` by default until client approves Phase 2 scope.
