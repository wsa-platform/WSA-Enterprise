# Source Identity Propagation Matrix (P0)

| Call site | File | Before | After |
|-----------|------|--------|-------|
| Ranker → Gate | ScientificResultRanker | passed source_key | unchanged (already OK) |
| Ranker → Directness | ScientificResultRanker | passed source_key | unchanged |
| Directness → Gate | ScientificEvidenceDirectnessAssessor | passes source_key | unchanged |
| EVL → Directness | EvidenceVerificationLayer | passes source_key | unchanged |
| ClaimEvidenceMatcher → Gate | ClaimEvidenceMatcher::matchCore | **MISSING** | **FIXED** — passes sourceKey + relevanceMetadata |
| Composer isRelevant | AnswerComposer::isSynthesizable | **MISSING** | **FIXED** |
| Composer assess (species relation) | AnswerComposer::evidenceIdentityCompatible | **MISSING** | **FIXED** |
| Composer Directness fallback | AnswerComposer::resolveDirectness | **MISSING** | **FIXED** |

Missing identity: record_type=UNKNOWN → identity_oriented=false → topic hard-reject for taxonomy scholarly (not silent invent).
