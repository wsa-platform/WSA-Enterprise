<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16453). Message timestamp as recorded in the conversation: Friday, Oct 16, 2026, 9:17 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16454; it is archived once. -->

---
# CURSOR AGENT — FORENSIC DATABASE AUDIT & SAFE CLEANUP

## 1. Role and Mission

Act as a senior Windows desktop application engineer, SQLite database specialist, and forensic data-recovery engineer.

Your mission is to investigate why Cursor's local state database has grown to approximately **45.396 GB**, determine which data accounts for this size, identify safe cleanup opportunities, and restore normal Cursor operation while preserving user data.

This is an existing production-like user environment. Data preservation is more important than aggressive disk-space recovery.

Perform actual inspection and verification using available local tools. Do not merely provide a theoretical plan.

## 2. Confirmed Environment and Evidence

Operating system: Windows  
Windows user directory: `C:\Users\ahmed`

Primary database:

`C:\Users\ahmed\AppData\Roaming\Cursor\User\globalStorage\state.vscdb`

Associated files observed:

- `state.vscdb` — approximately 45.396 GB
- `state.vscdb-wal` — approximately 51.1 MB
- `state.vscdb-shm` — approximately 0.1 MB

SQLite availability:

- Python is installed.
- Python's built-in SQLite module is available.
- Reported SQLite version: `3.50.4`
- A standalone `sqlite3.exe` was not found in the paths previously searched.

A read-only SQLite inspection reported:

- Database page size: `4096` bytes
- Page count: `11,990,522`
- Free-list page count: `0`
- Tables observed:
  - `ItemTable`
  - `composerHeaders`
  - `cursorDiskKV`

Cursor processes were terminated during troubleshooting, and a subsequent process check returned no Cursor processes at that time. Recheck the current process state before any database operation.

Disk-space history:

- C: initially had approximately `0.14 GB` free.
- Temporary files and npm/Python download caches were cleaned.
- The last reported free space was approximately `8.06 GB`. Recheck current free space.

Other findings:

- Cursor's ordinary cache directories were small, approximately 156 MB combined.
- Agent runtime versions were found, but their aggregate size did not explain the 45 GB database.
- No database backup was confirmed.
- The database has not intentionally been deleted, recreated, compacted, or modified during this investigation.

Treat these as observed historical results, not as guarantees about the current machine state. Verify before acting.

## 3. Non-Negotiable Data Protection Rules

### 3.1 Never do the following

- Never delete, truncate, replace, rename, or recreate `state.vscdb`.
- Never manually delete `state.vscdb-wal` or `state.vscdb-shm`.
- Never clear `ItemTable`, `composerHeaders`, or `cursorDiskKV` wholesale.
- Never delete conversation history, agent history, workspace state, project files, or user settings merely to free space.
- Never run `VACUUM`, `VACUUM INTO`, a migration, or any write operation against the original database during the initial audit.
- Never modify SQLite schema or records based on guessed meanings of keys or values.
- Never assume that old timestamps, large records, or old runtime versions mean that data is obsolete.
- Never disable SQLite locking, bypass database locks, or copy an active database without considering WAL consistency.
- Never install packages, download tools, or create a large database copy on C: without verifying available disk space.
- Never claim a backup or cleanup succeeded without verifying the result.

### 3.2 Preserve the user's work

Cursor may contain valuable conversations, agent sessions, prompts, workspace state, and data needed to continue WSA-Enterprise.

Preserve these by default.

If a proposed cleanup might remove or change any of these, do not execute it without explicit user approval after explaining the expected consequences.

## 4. Phase A — Read-Only Forensic Audit

Perform this phase first. Do not modify any data.

### A1. Verify the current environment

Report:

- Current free space on C: and G:, if G: exists.
- Whether Cursor processes are running.
- The exact sizes and modification times of:
  - `state.vscdb`
  - `state.vscdb-wal`
  - `state.vscdb-shm`
- Whether the primary database and associated files are accessible.
- Whether other existing backups or copies of the database can be found without scanning unrelated user data unnecessarily.

Do not terminate Cursor automatically if unsaved work could be lost. Ask the user to save work and close Cursor if necessary.

### A2. Inspect SQLite safely

Use Python's built-in `sqlite3` module.

Open the database in read-only mode and enable query-only behavior.

Do not use a writable SQLite connection to the original database.

Inspect:

- Database integrity using an appropriate read-only-safe check, subject to time and resource limits.
- SQLite schema and object definitions.
- Tables, indexes, triggers, and views.
- Page size, page count, free-list pages, and relevant database metadata.
- WAL presence and size.
- Any available metadata that can reliably indicate whether large portions of the database are actively used.

If an integrity check would take excessive time or resource consumption, report the limitation rather than interrupting the user's system.

### A3. Investigate table sizes

Determine how much storage is attributable to each table and its indexes.

SQLite does not expose reliable per-table byte sizes through a simple universal query. Use a suitable read-only method and clearly explain its limitations.

Potential approaches include:

- Inspecting `dbstat` availability and using it only if supported in read-only mode.
- Using page-level metadata to estimate storage attribution.
- Reporting table row counts only if these can be collected without excessive cost.

Do not run expensive full-table scans without estimating their cost first.

### A4. Understand the schema before inspecting values

Inspect the schema of:

- `ItemTable`
- `composerHeaders`
- `cursorDiskKV`

Determine column names, types, primary keys, indexes, and relevant relationships.

Do not assume any particular schema beyond what the database actually contains.

Do not dump conversation contents, authentication data, secrets, or personal information into the report.

### A5. Identify the largest records

If schema inspection supports it, determine which records or value classes account for the largest storage usage.

Start with metadata and aggregate statistics.

Only inspect record payloads when necessary, and then inspect the minimum amount required.

Redact secrets and sensitive user content from all output.

Distinguish between:

- Large but legitimate user data.
- Duplicated data, if demonstrably duplicated.
- Obsolete data, only when obsolescence can be established.
- Potentially corrupt or anomalous records.
- Data whose purpose cannot be established.

Do not classify data as disposable solely because it is large.

## 5. Phase B — Establish a Verified Recovery Plan

After the audit, produce a cleanup proposal containing:

1. Confirmed root cause or the remaining uncertainty.
2. Storage attributed to each table or identifiable data category.
3. The expected reclaimable space, if measurable.
4. The risk to conversations, agent sessions, workspaces, settings, and project data.
5. A recovery method.
6. Required backup space and destination.
7. Verification and rollback procedures.
8. The precise commands or operations proposed.

Separate findings from hypotheses.

If the 45 GB consists mostly of legitimate active records, state this clearly. Do not invent a cleanup opportunity.

## 6. Phase C — Backup Requirements

Do not begin destructive cleanup until a valid recovery strategy exists.

First determine whether an existing backup is suitable.

If a new backup is necessary:

- Confirm that the destination has enough free space.
- Prefer a separate drive with sufficient capacity.
- Use a SQLite-consistent backup method, such as Python's SQLite backup API, when applicable.
- If the source is active, account for WAL consistency and SQLite locking.
- Verify the backup independently, including its ability to open and pass an appropriate integrity check.
- Record source and backup paths, file sizes, timestamps, and verification results.
- Keep the original database untouched.

Do not assume that copying only `state.vscdb` is sufficient when WAL data may contain committed transactions not yet checkpointed.

If sufficient backup space is unavailable, stop before any destructive operation and report what capacity is required.

## 7. Phase D — Controlled Cleanup

This phase is not automatically authorized merely because the audit is complete.

Only propose cleanup operations supported by the forensic findings.

Possible actions may include:

- Cleaning confirmed disposable cache data outside the database.
- Removing specific records only when their purpose, ownership, and obsolescence are established.
- Rebuilding or compacting a database only after a verified backup, a demonstrated technical reason, and an explicit recovery plan.

For any database modification:

1. Work on a separate copy or verified recovery candidate where feasible.
2. Preserve the original database and its associated files.
3. Never guess record meanings.
4. Do not delete data that might be needed for Cursor history or agent continuity.
5. Verify integrity and application compatibility before considering replacement.
6. Obtain explicit user approval for operations that can delete or change user data.

If the required cleanup could destroy history or cannot be performed safely, do not execute it. Explain the blocker and provide the safest alternatives.

## 8. Phase E — Validation

After any user-approved cleanup, verify:

- The resulting database opens successfully.
- SQLite integrity checks pass, where applicable.
- Required tables and schema remain present.
- The database and associated files have consistent state.
- The resulting database size and recovered disk space.
- Cursor can start normally.
- Existing conversations, agent sessions, settings, and workspace state remain available to the extent that they were preserved.
- No project files or WSA-Enterprise repository files were modified by the cleanup.

Do not claim success based solely on a smaller file size.

If validation fails, preserve the original and follow the rollback plan.

## 9. Required Execution and Reporting Behavior

Work in this order:

1. Complete the read-only audit.
2. Report the confirmed findings.
3. Present the proposed cleanup and risk assessment.
4. Identify any approval or backup prerequisites.
5. Wait for explicit approval before destructive database operations.
6. Execute only the approved operations.
7. Verify the result and report actual measurements.

The audit itself should be performed using available tools where practical. Do not ask the user to repeat information already included in this specification.

Do not create commits, modify source repositories, or touch WSA-Enterprise project files. This task concerns Cursor's local application data only.

## 10. Final Report Format

Provide one comprehensive report in Arabic, with these sections:

1. **الملخص التنفيذي**
2. **الحالة الفعلية للقرص وعمليات Cursor**
3. **نتائج فحص SQLite**
4. **مصادر استهلاك مساحة قاعدة البيانات**
5. **البيانات التي يجب الحفاظ عليها**
6. **المساحة التي يمكن استعادتها فعليًا**
7. **خطة التنظيف والنسخ الاحتياطي**
8. **المخاطر والقيود**
9. **الاختبارات التي نُفذت ونتائجها الفعلية**
10. **الخطوة التالية المطلوبة من المستخدم**

For every result, state whether it is confirmed, estimated, or unknown.

Do not claim that anything was deleted, backed up, repaired, or tested unless the corresponding operation actually ran and its result was verified.

**Start with Phase A only. No deletion, database modification, compaction, or replacement is authorized until the audit report is complete and the user explicitly approves the proposed cleanup.**
