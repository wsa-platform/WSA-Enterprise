# تقرير استئناف Revision 4.2 — فحص مفتاح GBIF وإعادة تحقق واجهة Home
## Revision 11.0.21

## 0. فحص `.env` ومفتاح GBIF (إلزامي أولًا)
| البند | النتيجة |
|---|---|
| `.env` (جذر المشروع) | موجود؛ **لا** مفاتيح/متغيرات GBIF |
| `backend/.env` | موجود؛ **لا** `GBIF_API_KEY` / username / password |
| `backend/.env.example` | يذكر فقط `# GBIF_ENABLED=true` (اختياري) |
| ربط Laravel | `config/agricultural_intelligence.php` → `gbif.enabled` فقط؛ **لا** حقل `api_key` لـ GBIF |
| Adapter | `RestJsonSourceProfile` لـ `species/search` **بدون** `credential` |
| الوثائق الرسمية | [techdocs.gbif.org/en/openapi](https://techdocs.gbif.org/en/openapi/) — معظم الاستخدام لا يتطلب مصادقة؛ Basic Auth لحساب GBIF لعمليات download/كتابة وليس لمسار البحث المستخدم |
| اتصال حي بدون مفتاح | HTTP **200** على `species/search`؛ نتائج + `key` + `scientificName` حاضرة |
| قرار | **لا يُنشأ متطلب مفتاح**؛ لا تُختلق بيانات اعتماد؛ لا يُطلب تسجيل من المالك لهذا المسار |
| قيم سرية | **لم تُكشف** في هذا التقرير |

أثر منظم: `2026-11-19-GBIF-API-KEY-ENV-VERIFICATION.v11.0.21.json`.

---

## A. Executive Summary
استُؤنفت Revision 4.2 من نقطة **P. NEXT** في تقرير 11.0.20 (إعادة تحقق UI بعد الافتراضي التسلسلي)، بعد إغلاق فحص المفتاح.

| المسار | حالة سابقة (11.0.20) | حالة حالية (11.0.21) |
|---|---|---|
| **A — GBIF G5-04** | `ACCEPTED_WITH_LIMITATIONS` | **دون تغيير**؛ C7 N/A مؤكد بفحص env + اتصال عام |
| **B — استعادة البحث** | `PARTIALLY_REPAIRED` / UI pending | **Wheat UI + API**: إجابة علمية معروضة؛ لم يعد `no_search_results` لهذا السيناريو |

لا `SCIENTIFIC_INTEGRATION_VERIFIED`؛ لا إغلاق Phase 1؛ لا بدء BHL؛ لا خدمة مدفوعة.

---

## B. Baseline and Git
| الحقل | القيمة |
|---|---|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Resume-from HEAD | `e6b073f` (pin بعد `97045a6` feat) |
| WIP | محمي خارج Staging |

---

## C. Workstream A (GBIF) — بدون إعادة تنفيذ
عقود 11.0.20 تبقى حاكمة. تأكيد إضافي فقط:
- لا مفتاح في env.
- المسار العام يعمل.
- C7 يبقى `NOT_APPLICABLE_WITH_RATIONALE`.

---

## D. Workstream B — إعادة تحقق Home UI
### API path
`POST /api/v1/public/research-agent/query` — سؤال القمح الإنجليزي:
| المقياس | القيمة |
|---|---|
| status | `scientific_generated` |
| search | `search_completed` |
| raw → dedup | 60 → 22 |
| validated / rejected | 22 / 0 |
| claims / citations | 2 / 4 |
| human_status | `answered` |
| successful_sources | openalex, crossref |
| failed_sources | [] |

### UI path (`http://127.0.0.1:8079/`)
سؤال: `ما هو تأثير درجة الحرارة على نمو القمح؟`
- لوحظ التحميل: «جاري البحث في المصادر العلمية...»
- عُرضت **الإجابة** + قائمة حقائق + قسم **المصادر** مع DOI
- الحالة المعروضة: `scientific_generated`
- **لم** تُعرض `no_search_results`

أثر: `2026-11-19-HOME-UI-REVERIFY-AFTER-SEQUENTIAL.v11.0.21.json`.

---

## E. Tests
```
docker compose exec -T backend php artisan test --filter="GbifG504AcceptanceHarnessTest|NoSearchResultsRelevanceRejectionClassificationTest|PlantGrowthTemperatureQueryAndRelevanceTest"
Tests: 7 passed (37 assertions)
```

---

## F. Data Safety / Architecture
لا migrations؛ لا تغيير عقد ADR-022/023؛ لا اختلاق إجابات؛ Validation غير مُخفَّف. SQLite/المشروع محميان.

---

## G. Final Classification
| المحور | التصنيف |
|---|---|
| GBIF G5-04 | `ACCEPTED_WITH_LIMITATIONS` |
| GBIF API key gate | `NOT_REQUIRED_PUBLIC_SPECIES_SEARCH` |
| Search restoration (wheat) | `BACKEND_AND_UI_VERIFIED` |
| Ginger / process-pool opt-in | متبقي جزئي (خارج هذه الموجة) |
| Cumulative five-source | `BLOCKED` / `scientific_integration_verified=false` |
| Phase 1 | `PHASE_1_GAP_CLOSURE_PARTIAL` |

---

## H. NEXT RECOMMENDED ENGINEERING STEP
### Proposed Task Name
**BHL G6-20 — Source Acceptance Kickoff under ADR-023 (credential-aware; no Phase close)**

### Reason
مقاعد AGRIS / Organic Eprints / Dr. Duke / GBIF مغلقة بحدودها؛ المقعد المتبقي في المصادر الخمسة هو BHL. فحص env أظهر أن `BHL_API_KEY` موجود كمتغير (القيمة غير مكشوفة) بينما GBIF لا يحتاج مفتاحًا — لذا الخطوة التالية منطقًا BHL مع مسار اعتماد مفتاحه الفعلي دون اختلاق.

### Evidence
- مصفوفة Five-Source: G6-20 unchanged.
- هذا التقرير: UI wheat مُعاد إثباته؛ مفتاح GBIF غير مطلوب.
- `agricultural_intelligence.bhl.api_key` مربوط بـ env.

### Scope
استعادة عقد G6-20؛ التحقق من صلاحية/استخدام المفتاح الموجود دون كشفه؛ C1–C15 لـ BHL فقط؛ لا إعلان VERIFIED تراكمي.

### Dependencies
ADR-023؛ مفتاح BHL الموجود في env؛ عدم المساس بأدلة المقاعد السابقة إلا عند Regression مشترك.

### Acceptance Criteria
مصفوفة C1–C15 لـ BHL بأدلة مباشرة؛ تقرير عربي + أدلة؛ Commit/Push path-specific.

### Risks of Postponement
بقاء التكامل التراكمي محجوبًا رغم جاهزية أربعة مقاعد محدودة.

### Authorization Boundary
هذا التقرير **لا يبدأ** BHL تلقائيًا؛ توصية فقط حتى أمر تنفيذ مستقل.
