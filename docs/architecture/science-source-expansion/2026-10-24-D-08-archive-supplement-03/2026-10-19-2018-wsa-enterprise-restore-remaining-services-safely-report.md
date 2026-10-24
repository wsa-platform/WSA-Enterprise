<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17333). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير استعادة الخدمات المتبقية

## تقييم المخاطر قبل التشغيل (Queue/Scheduler)

| الفحص | النتيجة |
|---|---|
| Exit 137 السابق | إيقاف قسري متزامن (SIGKILL) في 2026-09-18 مع إغلاق المكدس — ليس انهيار تطبيق بسبب مهام فاسدة |
| سجلات Queue | `queue:work redis`؛ مهام مراقبة `RecordSystemHealthChecksJob` اكتملت بنجاح؛ انقطاع Redis لحظي سابق أثناء الإيقاف |
| سجلات Scheduler | `schedule:work`؛ مهام مراقبة (heartbeat / health-snapshot / ai-analysis) |
| جدول `jobs` / `failed_jobs` | **0** صف معلق |
| مفاتيح Redis `queues:*` | **فارغة** قبل وبعد التشغيل |
| الإعدادات | Compose أصلية، `DB_HOST=postgres`، `REDIS_HOST=redis`، شبكة `wsa-enterprise_wsa` |

**القرار:** لا خطر معلوم من طابور معلق متراكم → تشغيل آمن بـ `docker start` واحدًا تلو الآخر.

---

## ما نُفّذ

1. **Queue** — `docker start wsa-enterprise-queue-1` → Running / **healthy**  
2. **Scheduler** — بعد نجاح Queue → `docker start wsa-enterprise-scheduler-1` → Running / **healthy**  
3. **R7 / backup / orphan** — فُحصت وتُركت متوقفة  
4. الخدمات الخمس السابقة لم تُوقَف؛ `http://127.0.0.1:8079/api/v1/health` → **200** `{"status":"ok"}`

---

## جدول الحاويات

| الحاوية | الوظيفة المثبتة | سابقًا | نهائيًا | الإجراء | صحة / ملاحظات |
|---|---|---|---|---|---|
| `postgres-1` | قاعدة Laravel الرئيسية | Running | Running | لم تُمس | healthy |
| `redis-1` | كاش/طابور/جلسة | Running | Running | لم تُمس | healthy |
| `backend-1` | PHP-FPM API | Running | Running | لم تُمس | healthy |
| `frontend-1` | واجهة SPA | Running | Running | لم تُمس | healthy |
| `nginx-1` | بوابة 8079 | Running | Running | لم تُمس | healthy |
| `queue-1` | عامل طابور Redis | Exited 137 | **Running** | **شُغّلت** | healthy؛ لا مهام معلّقة ظاهرة |
| `scheduler-1` | `schedule:work` | Exited 137 | **Running** | **شُغّلت** | healthy؛ سيُطلق مهام مراقبة دورية (سلوك متوقع) |
| `wsa-r7-store-pg-20261015` | PG اختبار R7 (`wsa_r7_store_test`)، Volume منفصل، `NetworkMode=none` | Exited 255 | Exited | **متوقفة عمدًا** | ليست لازمة للتطبيق على 8079؛ سجلات أخطاء قيود scientific store |
| `wsa-backup-verification-pg` | تحقق نسخ مؤقت (`wsa_backup_test`، منفذ 55432) | Exited 255 | Exited | **متوقفة عمدًا** | ليست مسار تشغيل؛ لا restore |
| `postgres-1-orphan` | جيل قديم لنفس `postgres_data` | Exited | Exited | **متوقفة دائمًا** | خطر فساد إن عملت مع الأصلية |

**العاملة الآن: 7 / 10** (الهدف سلامة البيانات، لا 10/10).

---

## مخاطر وتبعيات متبقية

- **Scheduler/Queue يكتبان** عند تشغيل مهام المراقبة المجدولة (heartbeat، health snapshot، إلخ) — هذا دورهما التشغيلي، وليس migrations/restore.
- جدول **`cghia_capability_cell_records` ما زال غائبًا**؛ لم يُعالَج هنا.
- حاوية Queue تستخدم مسار bind عبر `/run/desktop/mnt/host/g/...`؛ تعمل حاليًا، لكن أي تغيير في مسار Docker Desktop قد يؤثر لاحقًا.
- Windows `postgresql-x64-17` ما زال متوقفًا (StartType=Automatic) — خارج هذه المهمة.

لا compose up/down، لا recreate، لا تعديل ملفات، لا تشغيل R7/backup/orphan.
