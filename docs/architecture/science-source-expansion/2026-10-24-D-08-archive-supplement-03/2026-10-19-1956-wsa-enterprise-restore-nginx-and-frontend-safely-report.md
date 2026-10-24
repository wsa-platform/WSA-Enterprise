<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17327). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير استعادة Nginx وFrontend

## ما فُحص ونُفّذ

### Phase 1 — Preflight
- الخدمات الأساسية الثلاثة: **Running / healthy**.
- الحاويتان الأصليتان موجودتان على شبكة `wsa-enterprise_wsa`.
- Nginx: منفذ مضيف **8079→80**، mounts: `nginx/default.conf` + `backend`؛ Health يفحص `/api/v1/health`.
- Frontend: لا منافذ مضيف؛ يخدم على 80 داخليًا؛ Nginx يعمل `proxy_pass http://frontend:80` لـ `/`.
- المنفذ **8079 كان حرًا**.
- **PREFLIGHT_PASS** — بلا إعادة إنشاء أو تعديل ملفات.

### Phase 2 — Frontend
- `docker start wsa-enterprise-frontend-1` ناجح.
- Health → **healthy**.
- `wget http://127.0.0.1/` داخل الحاوية → **HTTP 200**، HTML بعنوان `WSA Enterprise` مع أصول `/assets/...`.

### Phase 3 — Nginx
- `docker start wsa-enterprise-nginx-1` ناجح.
- نشر: `0.0.0.0:8079` و `:::8079`.
- `nginx -t` → **syntax ok / test successful**.
- Health → **healthy**.
- لا أخطاء upstream/DNS في سجلات الإقلاع.

### Phase 4 — تحقق HTTP فعلي
| URL | Status | ملاحظة |
|---|---|---|
| `http://127.0.0.1:8079/` | **200** | HTML الواجهة |
| `http://127.0.0.1:8079/api/v1/health` | **200** | `{"status":"ok"}` |
| `http://127.0.0.1:8079/up` | **200** | Laravel up |
| `/assets/index-B4xcm8Rs.js` | **200** | JS |
| `/assets/index-1VTEnhFE.css` | **200** | CSS |

---

## الحالة النهائية

| Container | الحالة | Health | منافذ |
|---|---|---|---|
| postgres-1 | Running | healthy | 5432 |
| redis-1 | Running | healthy | 6379 |
| backend-1 | Running | healthy | (داخلي 9000) |
| frontend-1 | **Running** (استُعيد) | healthy | داخلي 80 |
| nginx-1 | **Running** (استُعيد) | healthy | **8079** |
| queue / scheduler / R7 / backup / orphan | متوقفة عمدًا | — | — |

**عاملة الآن: 5** من 10.

---

## الخلاصة وحدود الإثبات

- الوصول عبر المتصفح على **`http://127.0.0.1:8079/`** يعمل فعليًا (صفحة + أصول + health API + `/up`).
- هذا يثبت **مسار الويب الأساسي**، وليس كل وظائف التطبيق (مصادقة، تدفقات زراعية، CapVer، طوابير…).
- جدول `cghia_capability_cell_records` ما زال غائبًا (لم يُعالَج هنا).
- لم يُمس Compose، ولم يُنفَّذ migrate/restore، ولم تُشغَّل الخدمات المحظورة.

**خطوات متبقية اختيارية (بموافقة لاحقة):** queue/scheduler إن لزم، أو قرار بشأن مخطط CapVer.
