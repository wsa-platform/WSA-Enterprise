<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18263). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 7:42 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18264; it is archived once. -->

---
# WSA-Enterprise — MASTER PROMPT — ROUND 1
## Layers A–D: Archive, Membership, ADR, Mission Folder

**Read first:** `MASTER-ENGINEERING-COMMON.md` and the latest valid checkpoint.  
**Scope:** Layers A–D only.  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Expected starting HEAD:** `fb60b60746ed487e2bfa04e3d335827d91cda446`  
**Final action:** Create the Round 1 checkpoint. Do not start Round 2 automatically.

## 1. Mission and authority

Act as an autonomous senior software engineer, architect, and evidence reviewer. Implement safe, justified changes; do not stop at a plan. Follow all rules in the common file. If baseline branch/HEAD differs, stop before editing and report the discrepancy.

## 2. Gate 0 — Baseline

Verify repository root, branch, HEAD, staged/unstaged/untracked files, existing reports, and protected database hashes where safe. Identify pre-existing work before making changes. Do not overwrite checkpoints or user files.

## 3. Layer A — D-08 Archive and provenance

Inspect the complete archive inventory, manifest, deduplication logic, source records, and provenance. Earlier reports described hundreds of untracked files and five exceptions among records expected to match verbatim; revalidate rather than assuming those numbers remain current.

Required:
1. Validate manifest structure and file references if a manifest exists.
2. Compare actual counts, duplicates, missing/extra files, and hashes where available.
3. Investigate every unexplained mismatch, including previously reported exceptions.
4. Review candidate files for secrets, sensitive content, unrelated files, suspicious size, and provenance gaps.
5. Preserve original content; never silently rewrite or discard exceptions.
6. Make file-by-file decisions; a manifest does not justify committing a whole directory, and a missing manifest does not justify ignoring the whole archive.

Acceptance: inventory and provenance are explainable; unresolved integrity exceptions mean `PARTIAL` or `BLOCKED`.

## 4. Layer B — Membership register and evidence matrices

Inspect existing register versions, schema, source inventory, matrices, and relevant ADR sections. Earlier reports described 109 seats and a proposed 110-seat v2 including G2-26; verify actual current files.

If justified by approved architecture, prepare:
`docs/architecture/ADR-023-CURRENT-MEMBERSHIP-REGISTER.v3.json`

Validate:
- Actual seat count equals declared count.
- All seat identifiers are unique.
- Required fields/schema are valid.
- Existing approved seats are preserved unless a documented decision authorizes a change.
- G2-26 appears exactly once only if supported by the governing ADR.
- Group membership, source identity, matrix, and ADR agree.
- Metadata follows established conventions.
- JSON parses; schema validation runs if available.

Do not insert comments/placeholders into JSON. Do not activate a source merely because it is listed. Membership, discovery, endpoint verification, integration, and production readiness are distinct states.

## 5. Layer C — ADR-023 integrity

Inspect the actual diff and approved history before editing. Preserve §§8.60–8.71 and other protected historical content.

Verify section uniqueness, numbering, dates, cross-references, source counts, statuses, evidence references, and consistency with approved decisions. Inspect the actual diff, not only deletion counts. Preserve established line endings where practical. Do not claim a local change is formally approved/published without evidence.

If a conflict cannot be resolved from approved decisions, preserve state and block dependent work.

## 6. Layer D — Mission folder

Inspect the source-expansion mission folder and its provenance. A prior report described 67 untracked files; count the actual files and inspect each.

For every file, assign:
- Content classification: required architectural evidence; required implementation/test artifact; historical reference/duplicate; temporary generated output; sensitive/irrelevant; unresolved.
- Explicit disposition: `INCLUDE`, `EXCLUDE`, `IGNORE`, or `INVESTIGATE`.
- Reason, evidence, risk, and relevant ADR relationship.

Do not blanket-ignore or automatically include the entire folder. Verify tracked status before applying ignore rules. Do not delete files to force closure.

### Layer D acceptance criteria

Layer D is complete only when:
1. Every file in the actual inventory has been reviewed.
2. Every file has a content classification.
3. Every file has an explicit disposition.
4. The disposition table contains rationale and evidence.
5. No unresolved item remains.
6. No disposition contradicts ADR-023 or required historical provenance.

If any file remains `INVESTIGATE`, mark Layer D `PARTIAL`. If a critical secret, unreadable evidence, or architecture conflict prevents safe disposition, mark `BLOCKED` where appropriate. Never force `CLOSED` to achieve a target.

## 7. Round 1 commands

From the verified repository root:
```powershell
git rev-parse --show-toplevel
git branch --show-current
git rev-parse HEAD
git status --porcelain=v1
git diff --stat
git diff --check
git ls-files --others --exclude-standard
```

Membership inspection example; adapt to actual schema:
```powershell
$path = "docs/architecture/ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json"
if (Test-Path $path) {
    $register = Get-Content $path -Raw | ConvertFrom-Json
    $register | Select-Object total_current_seats
    $register.seats.Count
}
```

Do not run commands that modify protected databases. Do not stage any file.

## 8. Round 1 deliverables

Produce:
1. Layer A–D status matrix with evidence.
2. Complete archive disposition/integrity report.
3. Membership validation report.
4. ADR diff/integrity report.
5. Mission-folder disposition table covering the actual inventory.
6. Candidate-file manifest entries for all affected files.
7. A checkpoint using the common convention, e.g. `round-1-YYYYMMDD-HHMMSS.md`, with actual timestamp and values.

The checkpoint must include actual branch/HEAD before and after, pre-existing changes, changed files, tests and actual results, protected hashes, unresolved risks, candidate/excluded paths, and exact next safe action.

**Stop after the checkpoint. Do not begin Round 2 automatically. Do not stage, commit, or push.**
