# 01-ROOT-CAUSE-ANALYSIS

Confirmed against current code + pre-flight QUS (still present at mission start):

1. **Sense (ISS-AR-003):** `إنتاجية` scored as productivity over irrigation/`ري`; `resolveScientificSense` did not call `asksExplicitIrrigationOrWaterRequirement`.
2. **Executable Arabic leak (ISS-AR-001/006):** CSQ Arabic surfaces entered `compileTerm`/`specExecutable*`/`joinTerms`; AGRIS specializer preferred `crop=القمح`.
3. **FTS AND (ISS-FTS-001):** Arabic tokens ∩ English corpus → empty AGRIS.
4. **OE (ISS-AR-002):** English-only eligibility cues.
5. **Concepts (ISS-AR-004):** No `organic`/`soil` topic factors.
6. **Composer (ISS-AR-005):** English snippets unmarked in Arabic answers.
7. **Status (ISS-EVD-002):** `no_valid_evidence` vs `no_validated_evidence`.
8. **Language capability (ISS-SRC-002):** Absent as explicit contract.
9. **Tests (ISS-TEST-001):** Platform locale → answer_language vs R2.
