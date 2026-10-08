# Session Sync

## Active State
**Current Task:** Implementing Gemini Audio Testing Playground.
**Status:** Implementation plan documented in `backlog.md`. Awaiting user sign-off.

## Decisions (The "Why")
- Isolated to a single controller and view to adhere strictly to the non-persistent playground scope.
- Using AJAX/fetch on the frontend to allow repeated submissions with the same audio without page reload or re-uploading, saving bandwidth and improving DX.
- Direct model invocation, no abstractions or DB persistence, per the constraint of keeping it purely experimental.

## Unresolved Questions
- Which Gemini SDK/HTTP approach should be preferred? (Will use Laravel `Http` facade to avoid unnecessary dependencies, unless requested otherwise).
- Specific models to list in the dropdown (e.g., `gemini-1.5-pro`, `gemini-1.5-flash`).
