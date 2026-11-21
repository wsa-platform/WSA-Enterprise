# Git Delivery — Gate 9

## Pre-push baseline

| Field | Value |
|---|---|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Starting HEAD | `052305b02efebee5ef27b35d8d89a0301e11a735` |
| Remote | `origin/phase-18-m18-ai-marketing-communications` |

## Implementation commit

| Field | Value |
|---|---|
| Commit | `1af45144625c56c49de740f3b03a654217e7bbd5` |
| Message | `fix(science): restore multi-source Stage-3 selection for taxonomy queries` |
| Force push | **never** |

## Docs delivery commit

| Field | Value |
|---|---|
| Commit | `7aa039183f3b31f7b24f38763589c6a0a9b223ad` |
| Message | `docs(science): record multi-source restoration R2 git delivery SHA` |

## Verification after push

| Field | Value |
|---|---|
| Final `HEAD` (branch tip after delivery docs) | `7acb6cfe599e098e6ff2914645e7ef47477b5978` |
| Local=Remote | **YES** (verified after push) |
| Implementation SHA | `1af45144625c56c49de740f3b03a654217e7bbd5` |
| Delivery-doc SHAs | `7aa0391…`, `7acb6cf…` |

## Scope

In-scope only: Universal Stage-3 merge, taxonomy variants, BHL protection, GBIF specializer cues, multi-source tests, mission docs under this directory. Unrelated WIP not staged.
