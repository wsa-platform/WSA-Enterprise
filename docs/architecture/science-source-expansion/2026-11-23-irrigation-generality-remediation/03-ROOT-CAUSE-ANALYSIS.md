# 03 — Root Cause Analysis

## GAP A — Incomplete multilingual irrigation-act detection

**Cause:** Helper listed a small EN/AR set and treated `drip irrigation` / `irrigation system` as positive requirement acts. Causal path ignored disease context. TR/FR coverage was thin.

**Effect:** Systems inventory and disease+irrigation questions could falsely signal explicit irrigation requirement; some legitimate TR/FR CWR phrases missed.

## GAP B — Over-broad `$explicitIrrigation` promotion

**Cause:** After prior fix, named-crop+water required `factorAskedAsAttribute`, but disease/systems paths could still score as irrigation and get promoted from productivity/general_knowledge when helper returned true (or when systems phrases were in the helper).

**Effect:** Incidental irrigation lexicon could still force irrigation intent.

## GAP C — Sense OR-gate

**Cause:** `resolveScientificSense` returned `crop_water_requirement` when **any** of:
- requested water factor, **or**
- `researchIntent === 'irrigation'`, **or**
- helper true

Intent alone was sufficient — systems/tech questions classified as irrigation intent became CWR without a requirement act.

**Effect:** False CWR for systems, some disease/extension-adjacent cases, and over-promotion of bare irrigation intent.

## GAP D — Unsafe English process fallback

**Cause:** Prior conditional still coarse; Arabic CWR process historically collapsed toward deficit irrigation. Quantity/scheduling/efficiency surfaces needed distinct English concepts.

**Effect:** Retrieval variants could inject unrelated deficit-irrigation concepts.
