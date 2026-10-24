<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16557). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 5:42 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16558; it is archived once. -->

---
أريد إنهاء نقل بيانات Cursor إلى G والاستعداد لاستعادة مساحة C.
نفّذ فحصًا تشخيصيًا فقط، دون حذف أو تعديل أي ملف:
1. تحقق مما إذا كان Junction موجودًا بالفعل، وحدد هدفه الحقيقي.
2. تحقق من المسار الفعلي الذي يستخدمه Cursor حاليًا لقاعدة globalStorage، وليس المسار الظاهر فقط.
3. تحقق من أن المحادثات السابقة ظاهرة وأن البيانات تُحفظ وتستمر بعد إعادة التشغيل، إن كانت هذه الاختبارات قد أُجريت بالفعل. لا تدّعِ نجاح اختبار لم يُنفّذ.
4. حدّد ملفات قاعدة البيانات وWAL وSHM الموجودة على C، وميّز الملفات الأصلية عن أي مسارات تشير إلى G عبر Junction.
5. أعطني قائمة دقيقة بالملفات التي يمكن حذفها بأمان لاستعادة المساحة، مع الحجم المتوقع استرداده.
لا تحذف أو تعِد تسمية أي شيء، ولا تلمس الاحتياطي الأصلي على G. أوقف التنفيذ بعد التقرير وانتظر موافقتي.
