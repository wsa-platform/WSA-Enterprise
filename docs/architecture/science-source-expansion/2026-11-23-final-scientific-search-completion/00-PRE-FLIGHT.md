# 00 — Pre-Flight (Final Scientific Search Completion)

**Mode:** Engineering remediation with commit/push authority  
**Started from HEAD:** `666d86413a87e7e107c0d47956278e18b0e561d7`

## A. Git Baseline

| Item | Value |
|---|---|
| Root | `G:/WSA-Enterprise` |
| Branch | `phase-18-m18-ai-marketing-communications` |
| Local HEAD | `666d86413a87e7e107c0d47956278e18b0e561d7` |
| Remote HEAD | matched |
| HEAD message | `docs(science): sync remediation report SHAs after docs push` |
| `d8f4749` ancestor | YES |
| Staged | none |
| WIP lines (porcelain) | ~229 paths (dirty + untracked) |

WIP includes prior user modifications to many tests (incl. `ScientificUpstreamRetrievalFixTest.php`), CapVer tooling, and docs. **Must not be committed with this mission.**

## B. LocalIndex / DB

- Docker stack healthy: backend, frontend, nginx `:8079`, postgres, redis.
- Project may use SQLite for capability stores and LocalIndex files; postgres container present for other services — **do not switch project DB engine**.
- Probes are search/validate/compose only (no rebuild/migration planned unless proven necessary).

## C. Prior verification gaps (work targets)

1. AR/EN document identity overlap  
2. Real AnswerComposer + claim ledger for Arabic irrigation  
3. OE live path beyond eligibility  
4. Browser E2E answer presentation  
5. Upstream test attribution  
6. TR/FR honest bounds  

## D. Decision

**`BASELINE_READY_WITH_LIMITATIONS`** — proceed; protect WIP; isolate mission commits.
