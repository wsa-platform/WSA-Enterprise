# 03 — Claim Decomposition

Existing `QuestionClaimExtractor` / `QuestionClaimSynthesisContract` retained.

This mission did **not** replace claim extraction. Multi-property questions already split into `qc-N` claims. Synthesis integrator consumes `ResearchAnswerClaim` rows after Composer binding.

**Status:** CONTRACT_VERIFIED (existing) + synthesis consumption tested.
