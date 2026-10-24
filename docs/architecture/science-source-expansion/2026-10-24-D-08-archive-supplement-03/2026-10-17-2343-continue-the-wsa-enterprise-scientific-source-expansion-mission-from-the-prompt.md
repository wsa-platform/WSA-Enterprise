<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16766). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 11:43 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16767; it is archived once. -->

---
Continue the WSA-Enterprise Scientific Source Expansion mission from the latest reported state. Work in `G:\WSA-Enterprise` and preserve all existing work.
Use the current 110.v1 membership register and the latest mission artifacts as the baseline. Do not repeat completed audits unless a new inconsistency or defect requires it.
### Immediate execution priorities
1. **G1-02 / Organic Eprints**
   - Inspect the actual implementation and tests.
   - Verify the official OAI-PMH contract and relevant license/access conditions.
   - Test an appropriate record-retrieval operation, not only `Identify`.
   - Verify initial/incremental ingestion, metadata mapping, stable identifiers, provenance, pagination, error handling, and LocalIndex publication.
   - Prove that records can be retrieved through the actual application search path while preserving CSQ semantics.
   - Do not mark ACTIVE while the required index is empty, unpublished, or otherwise ineligible.
2. **Capability Store integration**
   - Review the changes to `Stage3CghiaCoexistenceBoundary.php` and all relevant tests.
   - Prove that CURRENT facts are loaded correctly and that missing, historical, invalid, or unauthorized facts fail closed.
   - Ensure no arbitrary caller can assign VERIFIED without the approved authority path.
   - Do not fabricate facts, bypass CapVer, or treat DI binding as proof of operational correctness.
   - Add and run focused regression tests.
3. **G5-04 / GBIF**
   - Preserve its existing ACTIVE status unless concrete evidence justifies a governed status change.
   - Verify the real adapter-to-orchestrator-to-normalizer path, source identity, provenance, error handling, and meaningful result behavior.
   - Clearly distinguish live HTTP checks from end-to-end application verification.
4. **G1-01, G1-17, G6-20, and other ready candidates**
   - Continue independently wherever the work is authorized and technically feasible.
   - For BHL, do not use fake credentials as proof of readiness. Verify official authentication requirements and document the remaining dependency if no valid key is available.
   - Assess other entries individually; do not infer readiness from membership or registration alone.
### Execution requirements
- Research official source documentation online without asking the user for routine research approval.
- Implement authorized code and tests; do not return a plan-only answer.
- Preserve CSQ, source identity, provenance, licensing, evidence validation, R1–R8, and ADR-023 constraints.
- Do not create a new persistence layer unnecessarily.
- Keep source-by-source evidence and status in the existing mission artifacts.
- Do not change the membership from 110.v1 to 109.v1.
- Do not claim activation without all applicable gates being satisfied.
- Preserve the existing worktree and all user changes.
- No commit, push, deploy, Docker, reset, clean, stash, or destructive operations.
Continue until the authorized work is implemented and verified or a genuine blocker remains. Then produce one comprehensive Arabic report with exact files changed, tests and outputs, live-probe scope, per-source status, activation evidence, blockers, branch, HEAD, and confirmation that no commit/push/deploy occurred. Stop before commit/push.
