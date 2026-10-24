<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16472). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 12:27 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16473; it is archived once. -->

---
# FINAL EXECUTION DIRECTIVE — Cursor Database Recovery, Migration to G:, and C: Space Recovery
## 1. Mission
Act as a senior Windows application engineer, SQLite recovery specialist, and data-preservation engineer.
Resolve the Cursor database size problem end to end. Do not stop at an audit or theoretical plan if the required safety conditions can be satisfied.
The desired final state is:
1. Cursor uses a verified, stable database located on `G:`.
2. Existing conversations, settings, agent state, and required workspace data remain accessible.
3. Cursor can save new changes and reopen successfully using the database on `G:`.
4. The original large database on `C:` is removed only after successful migration and validation.
5. The recovered C: space is measured and reported.
6. A verified recovery copy remains on G:.
7. No project files or WSA-Enterprise repository data are changed.
**The G: copy becomes the primary working database only after actual application-level validation. Matching file sizes is not proof of equivalence or usability.**
## 2. Known environment
Windows user: `C:\Users\ahmed`
Original database:
`C:\Users\ahmed\AppData\Roaming\Cursor\User\globalStorage\state.vscdb`
Backup directory:
`G:\CursorBackups`
Known surviving backup:
`G:\CursorBackups\state.vscdb.backup-20261016-220630.sqlite`
Reported backup size: approximately 45.44 GiB.
A second backup named:
`state.vscdb.backup-20261016-234740.sqlite`
was deleted by the user. Do not attempt to restore or recreate that deleted backup.
Associated SQLite files may include:
- `state.vscdb-wal`
- `state.vscdb-shm`
- backup-specific `-wal`, `-shm`, and `-journal` files
Historical observations:
- Python SQLite is available, version previously reported as `3.50.4`.
- The original database was approximately 45.396 GiB.
- A previous read-only inspection reported page size 4096, page count 11,990,522, free-list count 0, and tables `ItemTable`, `composerHeaders`, and `cursorDiskKV`.
- A full `quick_check` on the large backup ran for a long time and was interrupted.
- The remaining G: backup has not yet been conclusively verified.
- The user previously had very little free space on C:. Recheck all disk-space figures.
- Cursor and Python processes were stopped during previous troubleshooting, but check current state again.
These are historical observations, not guaranteed current conditions. Verify the current system before acting.
## 3. User authorization
The user authorizes a complete, safe resolution of the problem, including deleting the original C: database **if and only if** all migration gates below pass.
The user does NOT authorize:
- Deleting conversations or selected database records.
- Guessing that old records are disposable.
- Deleting required WAL, SHM, or journal files by name alone.
- Using unsupported relocation tricks without establishing compatibility and reversibility.
- Modifying WSA-Enterprise source files or repositories.
- Claiming successful migration without testing it.
## 4. Non-negotiable safety requirements
1. Never delete or modify the C: original before the G: database has passed all required checks and Cursor has been proven to work from G:.
2. Never overwrite the only verified backup.
3. Never assume equal file size, equal schema, or equal table counts prove complete database equivalence.
4. Never copy an active SQLite main database without properly accounting for WAL and journal state.
5. Never remove sidecar files while a database may depend on them.
6. Do not run `VACUUM`, `VACUUM INTO`, record deletion, migrations, or compaction against the original database.
7. Do not use a symlink or junction unless the method is verified as compatible with this installed Cursor version, tested, and reversible.
8. Do not make destructive changes while Cursor is running.
9. Never delete unrelated data to free space.
10. If a required safety condition cannot be established, stop before the destructive action and report the exact blocker.
## 5. Phase A — Current inventory and process state
Inspect and report:
- Free space on C: and G:.
- Exact file sizes and timestamps for the original, surviving backup, and associated sidecars.
- All existing database copies in the known Cursor database directory and `G:\CursorBackups`.
- Current Cursor and related process state.
- Whether any database or sidecar is currently open or changing, where safely ascertainable.
- Whether sufficient free space exists on G: for any required recovery artifact.
Do not terminate processes automatically if unsaved work could be lost. If Cursor is running, ask the user to save work and close it gracefully before proceeding.
## 6. Phase B — Verify and compare databases
Perform read-only inspection wherever possible.
For the C: original and G: backup, determine:
- Whether each database opens.
- SQLite schema and table names.
- Page size, page count, and free-list count.
- `PRAGMA quick_check(1)` result where feasible.
- Sidecar presence and relevance.
- Safe comparisons of non-sensitive metadata and record identities/counts when useful.
Do not expose conversation contents, prompts, tokens, credentials, or personal values.
A previous long-running check must not simply be restarted without estimating the cost and choosing an appropriate verification method.
If complete integrity validation is infeasible, distinguish between:
- Confirmed facts
- Partial validation
- Unknowns
- Risks that remain
Do not label an uncompleted check as PASS.
### Gate B
Do not proceed to migration unless:
- The G: candidate is readable.
- Its structure is compatible with the installed Cursor version.
- Its integrity is sufficiently established for the proposed recovery method.
- Its data completeness is adequately established.
- A rollback plan exists.
If any condition fails, preserve the C: original and report the blocker.
## 7. Phase C — Establish a supported G: operating path
Inspect the installed Cursor version and its supported configuration.
Determine whether Cursor can safely use a database on G: through:
- An official or documented configuration mechanism, or
- A verified, reversible filesystem redirection method compatible with this version.
Do not assume direct path configuration is supported.
If no safe, supported method is available, present the best alternative and stop before altering the original.
### Gate C
Before changing any active path, document:
- The exact method.
- Why it is compatible with the installed Cursor version.
- How it will be reversed.
- Which exact files or configuration entries will change.
- How the original C: database remains recoverable.
## 8. Phase D — Prepare the migration
Only after Gates B and C pass:
1. Ask the user to save all work and close Cursor gracefully.
2. Confirm relevant Cursor processes have exited.
3. Ensure a verified recovery copy exists on G:.
4. Preserve the surviving backup and do not overwrite it.
5. If a new active G: database is required, create it under a new unique filename.
6. Use SQLite's backup API or another SQLite-consistent method as appropriate.
7. Account for WAL and journal consistency.
8. Verify the destination before switching the active path.
9. Create a migration manifest with source path, destination path, sizes, timestamps, checks, and rollback instructions.
Do not delete or rename the original C: database in this phase.
## 9. Phase E — Switch and test actual Cursor operation
Use the verified supported method to make the G: database active.
Test all of the following:
1. Cursor launches successfully.
2. Expected conversations and agent history are accessible.
3. Required settings and workspace state are present.
4. Cursor can save a new harmless change.
5. Cursor closes gracefully.
6. Cursor reopens and retains that change.
7. The active database path is demonstrably on G:, not merely a backup stored there.
8. No relevant database errors appear in available logs.
9. C: and G: paths resolve as intended.
Do not invent a test result or report that Cursor uses G: without evidence.
### Gate E
If any test fails:
- Stop the migration.
- Preserve all files.
- Restore the original C: configuration using the documented rollback method.
- Verify that Cursor returns to its prior working state.
- Do not delete the C: original.
If all tests pass, proceed to the next phase.
## 10. Phase F — Final space recovery on C:
Only after Gate E passes:
1. Identify the exact original C: database files and sidecars that can safely be removed or archived.
2. Ensure the G: active database and separate recovery copy remain intact.
3. Ensure Cursor is fully closed before deleting database files.
4. Determine whether the original database's WAL, SHM, or journal files must be preserved.
5. Report the exact file paths, sizes, and expected recovered space.
6. Delete only the explicitly identified original database files that are no longer needed.
7. Do not delete unrelated files or folders.
8. Verify the deleted files are absent.
9. Recheck free space on C:.
10. Reopen Cursor and confirm it still uses G: successfully after the C: database is gone.
11. Keep the verified G: recovery copy intact.
If deleting the original requires a separate confirmation, present the exact target paths and ask for that confirmation before deletion.
## 11. Rollback requirements
Maintain a reversible path until final validation succeeds.
If Cursor fails after switching to G::
- Close Cursor gracefully.
- Reverse only the migration-specific configuration.
- Restore the prior active path to the original C: database.
- Preserve the G: candidate and all sidecars.
- Verify that Cursor launches and previous data is accessible.
- Do not attempt speculative database repair.
## 12. Final report
Produce one comprehensive report in Arabic with these sections:
1. **الملخص التنفيذي**
2. **الحالة الفعلية للقرصين C وG**
3. **نتائج فحص ومقارنة قاعدة البيانات**
4. **طريقة جعل G أساس العمل ودليل دعمها**
5. **نتائج اختبار تشغيل Cursor والحفظ وإعادة الفتح**
6. **المسار الفعلي لقاعدة البيانات النشطة**
7. **الملفات التي حُذفت من C، إن وجدت**
8. **المساحة المستعادة فعليًا**
9. **حالة النسخة الاحتياطية والاستعادة**
10. **المخاطر أو الاختبارات غير المكتملة**
For every check, report one of:
- `PASS`
- `FAIL`
- `NOT RUN`
- `INCONCLUSIVE`
Do not report success unless the corresponding test actually ran and completed.
## Final execution instruction
Start with Phase A and continue through each phase when its safety gate passes.
The desired outcome is a working Cursor instance using a verified database on G:, with the C: database deleted only after actual successful operation from G: is proven.
If any gate cannot be satisfied, do not perform the destructive action. Explain the precise blocker and the safest available next step.
Do not modify WSA-Enterprise source code, Git history, or repository configuration at any point.
