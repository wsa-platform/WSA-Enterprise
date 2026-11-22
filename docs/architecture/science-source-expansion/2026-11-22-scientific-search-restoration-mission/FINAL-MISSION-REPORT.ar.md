# تقرير المهمة النهائي — استعادة البحث العلمي متعدد المصادر

## أ. الملخص التنفيذي

**التصنيف:** `SCIENTIFIC_SEARCH_MULTI_SOURCE_VERIFICATION_PARTIAL`

**ما أُصلح فعليًا:**
1. Early-stop الواعي بالقدرات (`mayEarlyStopSource`) مع تصحيح نوع معرفة AGRIS/OE إلى `agricultural_practice`.
2. دفتر إسناد الادعاءات L7 (`ClaimAttributionLedger`) مع `source_keys` / `attributions` على الادعاءات وتعريض `claim_attribution_ledger` في استجابة API العامة.
3. ختم أدلة GBIF الهيكلية (`structured_taxonomic`) دون إجبار شكل الورقة العلمية.
4. حالات جاهزية LocalIndex الصريحة (`READY|COLD|STALE|EMPTY|FAILED|UNAVAILABLE`) دون إعادة بناء متزامنة.

**ما تُحقق:** اختبارات وحدة B/G/C ناجحة؛ Live قمح `scientific_generated` مع ledger=2؛ Live Mentha مع GBIF+BHL وledger=1.

**ما يبقى ناقصًا:** LocalIndex (AGRIS/Duke/OE) محظور بخطأ I/O على SQLite؛ FAOSTAT/Consensus حسب السياسة؛ Browser E2E؛ إثبات L7 لكل مصدر على حدة في بيئة دافئة.

**أهم المخاطر:** فهرس محلي تالف/غير مقروء؛ حدود معدل Semantic Scholar؛ عدم اعتبار `successful_sources` كافيًا لـ L7.

---

## ب. Baseline وGit

| | |
|--|--|
| الفرع | `phase-18-m18-ai-marketing-communications` |
| HEAD البداية | `5c3e2a17b6e63f5745ec18223ca9afdc4e6fc4a8` |
| Working tree | WIP سابق محفوظ — لم يُضمَّن في commits المهمة |
| Commits المهمة | تُسجَّل بعد البوابة (انظر قسم ك) |

---

## ج. حل الأسباب الجذرية

انظر `ROOT-CAUSE-RESOLUTION-MATRIX.md`.

ملخص: ISS-RC-01 وISS-RC-09 وISS-RC-06 عولجت في الكود؛ ISS-RC-02 جزئي (مراقبة + محظور بيانات)؛ ISS-RC-07 سياسة محفوظة؛ ISS-RC-03/04 لم تُعاد فتحها.

---

## د. تكامل المصادر العشرة

انظر `SOURCE-INTEGRATION-MATRIX.md`.

| المصدر | أعلى مستوى مثبت هذه المهمة |
|--------|------------------------------|
| OA/CR | L7 مسار (ledger + scientific_generated) |
| SS | L4–L6+ في successful_sources |
| GBIF/BHL | L4+ Live؛ L7 PARTIAL عبر ledger |
| AGRIS/Duke/OE | ≤L2؛ BLOCKED LocalIndex |
| FAOSTAT/Consensus | سياسة؛ NOT_RUN مسارات مفعّلة/صريحة |

---

## هـ. إسناد الادعاءات

Live: `claim_attribution_ledger_count` = 2 (قمح)، 1 (Mentha).  
العقد: Claim → Evidence → source_key → provider_record_id → citation_id عندما توجد استشهادات.

لا يُمنح L7 من قائمة المصادر وحدها.

---

## و. LocalIndex

`scientific:diagnose-persistence`: LocalIndex `reachable=false` — `disk I/O error` على `scientific_record_store.sqlite`.  
الإصلاح البرمجي: جاهزية صريحة + منع rebuild متزامن (سابقًا).  
الإصلاح التشغيلي المطلوب: إصلاح/استبدال ملف الفهرس دون حذف أحجام Docker، ثم ingest offline.

---

## ز. GBIF وBHL

- GBIF: abstract تصنيفي + metadata هيكلية مختومة.
- Mentha Live: كلاهما في `successful_sources`.
- مساهمة الادعاء: ledger موجود؛ إثبات L7 لكل مصدر على حدة PARTIAL.

---

## ح. الاختبارات

```
phpunit CapabilityAwareEarlyStopTest PrioritySourceEarlyStopProtectionTest
         ClaimAttributionLedgerTest RestJsonSourceFamilyTest
         ScientificKnowledgeTypeRelevancePolicyTest
→ OK (40 tests, 164 assertions)

phpunit GbifStructuredEvidenceContractTest → OK (1 test)
```

Browser E2E: **NOT_RUN**. Full suite: **NOT_RUN**.

---

## ط. API والواجهة

API: `claim_attribution_ledger` على استجابة stage-5 العامة — Live مثبت.  
Frontend عرض الدفتر: **NOT_RUN** (Browser). الحالات الفاشلة تبقى عبر `status` / `failure_class` القائمة.

---

## ي. العمل المتبقي

1. إصلاح I/O لفهرس LocalIndex ثم إثبات AGRIS/Duke/OE حتى L4–L7 على أسئلة مناسبة.
2. مسار FAOSTAT enabled معزول؛ Consensus صريح.
3. Browser E2E للاقتباسات والدفتر.
4. استقلال الأدلة DOI (ISS-RC-10) تحقيق أعمق.
5. عدم منح «عشرة مصادر خضراء» قبل دفء LocalIndex.

---

## ك. حالة الإصدار

تُحدَّث بعد commit/push المؤهل مع إثبات مزامنة Remote.
