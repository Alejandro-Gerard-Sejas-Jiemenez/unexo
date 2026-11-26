# Pagos con Stripe, Fase 1
Branch: feat/adjustments-and-review · Delivery: ask-on-risk, no commits yet · Tests: `php artisan test`

## Specs
Source of truth: `specs/010-pagos-stripe/spec.md` (RF-01..RF-10, CA-01..CA-10).

## Tasks
- T1 (RF-01..10) failing tests for CA-01..CA-10 · inline · done, uncommitted
- T2 (RF-01..03, RF-08..10) charge rules and simulated mode · inline · done, uncommitted
- T3 (RF-04..07) Stripe Checkout, recovery and webhook · inline · done, uncommitted
- T4 (RF-04, RF-05, RF-09) payment screens · inline · done, uncommitted
- T5 full suite, Pint, frontend build · inline · done, uncommitted

## Log
L1 (2026-11-26) User request: "de una vez aplicalas". Read as: implement the Fase 1 rules now.
- Gate 1 (team approval), D-02, D-08 and the constitution update are still pending; implementation goes ahead on the user's order only.
- Engram mirror: pending (auto mode could not check the memory write earlier because a hook changes its input).
- Constraints: no commit, no real Stripe calls, no migration on the local or server database.
- Evidence: 13 new tests failed before implementation; full suite 446 passed, 1 failed (ReportesDescargaTest, from the pre-existing uncommitted pdf.blade.php change). Frontend build OK. Not tested in a browser, against real Stripe, or on PostgreSQL.
- Not done: independent code review, commits.
