<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15521). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
I completed R1 stabilization and R2 (capability enforcement) and verified both with isolated Docker test runs, with no regressions. §8.35 is appended to ADR-023 with the original content unchanged. R3 to R8 are not started; R3, the adapter contract v2, is next. Nothing was committed, staged or deleted, and there was no network or database access.

**Test results** (compared per test against the pre-R1 baseline):

| Suite | Tests | Result |
|---|---|---|
| Unit | 1,304 | 0 regressions; 61 new tests, all pass |
| Feature | 950 | 0 regressions |

Every remaining failure message is identical to the pre-R1 baseline. The 29 Unit and 84 Feature failures existed before R1 and were not investigated.

**R1: error handling fixed.** R1's isolation caught everything, including PHPUnit assertion failures, blocked-request errors from `Http::fake`, and genuine code bugs. Now only expected source failures are recorded as a failed result for that source:
- HTTP connection and request errors;
- a new `ScientificSourceFailure` exception that adapters can throw.

Everything else propagates as it did before R1. This removed the changed FAOSTAT failure message: that test fails exactly as on the baseline again. The concurrent search path still catches everything and logs exception messages; I left it for R3.

**The 23-versus-25 test count.** Three of the B-1 Projection files were changed at about 20:00 tonight, before my work. That revision adds two test methods, and no ADR section records it. I've marked it unresolved in §8.35.3.

**R2: capability enforcement, as you decided.**
- **Declarations:** each source in the catalog now declares what it can do, per operation (search, pagination, filtering, full text, bulk), in reviewed code.
- **Deny by default:** an operation is allowed only with verified support whose evidence hasn't expired, or with the legacy basis. An undeclared operation, expired evidence, or any other status is denied with a stated reason.
- **Where it applies:** both the production selector and explicitly requested sources in the orchestrator. Denials appear in the search diagnostics and the log.
- **The five live sources:** each holds the disclosed legacy basis for search only, so their behavior is unchanged and they are never shown as verified.
- **Not done:** collecting and verifying real evidence needs live probes, which aren't authorized, so no source is marked verified. There is also no runtime signal yet for a temporarily unavailable source.

The legacy 62-entry register is unchanged and kept; the deletion manifest is still empty. Your mission text was cut off partway through §9, item 9 ("Verify that unrelated"). I applied every rule I received.

**Decisions for you:**
- Should I record the unexplained B-1 revision as accepted, or leave it open?
- Live probes would be needed before any source can be marked verified. Do you want to authorize them?

I should wrap up with a concise milestone summary, noting R3 through R8 haven't been started yet this turn, with R3 next up, and flagging that the prompt was truncated.
