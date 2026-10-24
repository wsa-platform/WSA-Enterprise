<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16565). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 5:50 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16566; it is archived once. -->

---
# CURSOR — FINAL VALIDATION AND CONDITIONAL DELETION OF A + B
The user has changed the cleanup authorization: delete BOTH folder A and folder B on C:, but ONLY after successful runtime and persistence validation.
## TARGETS
Active globalStorage Junction:
`%APPDATA%\Cursor\User\globalStorage` → expected target `G:\CursorData\Staging`
Folder A:
`%APPDATA%\Cursor\User\globalStorage.pre-junction-20261017-142822`
Folder B:
`%APPDATA%\Cursor\User\globalStorage.pre-migration-20261017-152454`
Protected live storage:
`G:\CursorData\Staging\*`
Protected Runtime hard-link path:
`G:\CursorData\Runtime\globalStorage\state.vscdb`
Protected original backup:
`G:\CursorBackups\state.vscdb.backup-20261016-220630.sqlite` and any associated files.
## GATE 1 — PATH AND IDENTITY CHECKS
Before any deletion:
1. Verify the active path is a directory Junction targeting exactly `G:\CursorData\Staging`.
2. Verify the live database resolves to the expected G: file identity.
3. Verify A and B are ordinary physical directories on C:, not reparse points, and are not active runtime targets.
4. Verify the G: live data, Runtime hard-link path, and original backup remain present.
5. Record current free space on C: and G:.
6. If any path, file identity, or ownership is ambiguous, STOP without deletion.
## GATE 2 — VALIDATE CURSOR
Do not delete anything yet.
1. Verify previous conversations are visible in Cursor.
2. Perform a harmless persistence test if safe and supported.
3. Close Cursor normally and verify all relevant Cursor processes have exited.
4. Reopen Cursor normally.
5. Verify previous conversations remain visible and the test item persists.
6. Verify the active globalStorage path still resolves to G:.
Do not claim a test passed if it was not actually performed.
If any test fails or remains inconclusive, STOP and preserve BOTH A and B.
## GATE 3 — PRE-DELETE CHECK
Only if all validation tests pass:
1. Close Cursor normally again and verify all relevant processes have exited.
2. Reconfirm the Junction target.
3. Reconfirm that A and B are ordinary directories on C: and not active paths or reparse points.
4. Enumerate their exact contents and sizes.
5. Confirm the live G: database and original backup are still present.
If any check is ambiguous, STOP and preserve both folders.
## GATE 4 — DELETE A AND B ONLY
Only after every previous gate passes, delete these exact two directories on C::
A:
`%APPDATA%\Cursor\User\globalStorage.pre-junction-20261017-142822`
B:
`%APPDATA%\Cursor\User\globalStorage.pre-migration-20261017-152454`
Delete only those physical directories. Never delete them through the active Junction path.
Do NOT delete or modify:
- `%APPDATA%\Cursor\User\globalStorage` (the active Junction)
- Any files under `G:\CursorData\Staging`
- `G:\CursorData\Runtime\globalStorage\state.vscdb`
- The original backup on G:
- Any other files or directories
## GATE 5 — VERIFY AFTER DELETION
1. Verify A and B no longer exist at their exact C: paths.
2. Verify the Junction still targets `G:\CursorData\Staging`.
3. Verify the live G: database and original backup remain present.
4. Reopen Cursor normally and verify it launches.
5. Measure actual free space on C: and G: after cleanup.
## FINAL REPORT
Return one consolidated Arabic report with:
- Junction verification evidence.
- Conversation visibility and persistence test results.
- Confirmation that Cursor processes exited before deletion.
- Exact sizes of A and B before deletion.
- Whether each folder was deleted.
- Actual free space before and after.
- Confirmation that live G: storage and the original backup remain protected.
- Final Cursor launch result.
- Final status: PASS, PARTIAL, or STOPPED.
The user authorizes deletion of A and B only after successful validation. If any gate fails or is inconclusive, stop and preserve both folders. No VACUUM, repair, arbitrary SQL DELETE, sidecar cleanup, or unrelated deletion.
