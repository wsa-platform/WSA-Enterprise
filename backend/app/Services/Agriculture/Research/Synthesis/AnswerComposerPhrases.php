<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Platform-locale composer chrome and explanatory templates (en|ar|tr|fr).
 * Evidence snippets stay in citations; this is not LLM translation.
 */
final class AnswerComposerPhrases
{
    /**
     * @param  array<string, string>  $replace
     */
    public static function get(string $language, string $key, array $replace = []): string
    {
        $language = in_array($language, ['en', 'ar', 'tr', 'fr'], true) ? $language : 'en';
        $catalog = self::catalog();
        $text = $catalog[$key][$language] ?? $catalog[$key]['en'] ?? $key;
        foreach ($replace as $search => $value) {
            $text = str_replace($search, (string) $value, $text);
        }

        return $text;
    }

    /** @return array<string, array<string, string>> */
    private static function catalog(): array
    {
        return [
            'insufficient_direct' => [
                'en' => 'Insufficient direct scientific evidence was found for a definitive answer.',
                'ar' => 'لم يتم العثور على دليل علمي مباشر كافٍ للإجابة بشكل مؤكد.',
                'tr' => 'Kesin bir yanıt için yeterli doğrudan bilimsel kanıt bulunamadı.',
                'fr' => "Aucune preuve scientifique directe suffisante n'a été trouvée pour une réponse certaine.",
            ],
            'explanatory_evidence' => [
                'en' => 'Validated direct scientific evidence supports a conclusion for this question. Extracted facts are listed below; source details remain in the citations.',
                'ar' => 'تشير الأدلة العلمية المباشرة المعتمدة إلى استنتاج مدعوم لهذا السؤال. تُعرض الحقائق المستخرجة أدناه، وتبقى تفاصيل المصادر في الاستشهادات.',
                'tr' => 'Doğrulanmış doğrudan bilimsel kanıtlar bu soru için desteklenen bir sonuca işaret eder. Çıkarılan olgular aşağıdadır; kaynak ayrıntıları atıflarda kalır.',
                'fr' => 'Des preuves scientifiques directes validées étayent une conclusion pour cette question. Les faits extraits figurent ci-dessous ; les détails des sources restent dans les citations.',
            ],
            'range_evidence' => [
                'en' => 'Direct scientific evidence indicates that the :label is :values.',
                'ar' => 'تشير الأدلة العلمية المباشرة إلى أن :label هو :values.',
                'tr' => 'Doğrudan bilimsel kanıtlar, :label değerinin :values olduğunu göstermektedir.',
                'fr' => 'Les preuves scientifiques directes indiquent que :label est :values.',
            ],
            'list_evidence' => [
                'en' => 'Direct scientific evidence identifies the following:',
                'ar' => 'تشير الأدلة العلمية المباشرة إلى ما يلي:',
                'tr' => 'Doğrudan bilimsel kanıtlar aşağıdakileri tanımlar:',
                'fr' => 'Les preuves scientifiques directes identifient les éléments suivants :',
            ],
            'process_evidence' => [
                'en' => 'Direct scientific evidence supports the following steps:',
                'ar' => 'تدعم الأدلة العلمية المباشرة الخطوات التالية:',
                'tr' => 'Doğrudan bilimsel kanıtlar aşağıdaki adımları destekler:',
                'fr' => 'Les preuves scientifiques directes étayent les étapes suivantes :',
            ],
            'comparison_evidence' => [
                'en' => 'Direct scientific evidence supports the following comparison:',
                'ar' => 'تدعم الأدلة العلمية المباشرة المقارنة التالية:',
                'tr' => 'Doğrudan bilimsel kanıtlar aşağıdaki karşılaştırmayı destekler:',
                'fr' => 'Les preuves scientifiques directes étayent la comparaison suivante :',
            ],
            'heading_answer' => [
                'en' => 'Answer', 'ar' => 'الإجابة', 'tr' => 'Yanıt', 'fr' => 'Réponse',
            ],
            'heading_family_members' => [
                'en' => 'Plant family members', 'ar' => 'أفراد العائلة النباتية', 'tr' => 'Bitki familyası üyeleri', 'fr' => 'Membres de la famille botanique',
            ],
            'heading_land_types' => [
                'en' => 'Land types', 'ar' => 'أنواع الأراضي', 'tr' => 'Arazi türleri', 'fr' => 'Types de terres',
            ],
            'heading_planting_date' => [
                'en' => 'Planting date', 'ar' => 'موعد الزراعة', 'tr' => 'Ekim zamanı', 'fr' => 'Date de plantation',
            ],
            'heading_varieties' => [
                'en' => 'Varieties', 'ar' => 'الأصناف', 'tr' => 'Çeşitler', 'fr' => 'Variétés',
            ],
            'heading_plant_growth' => [
                'en' => 'Crop growth', 'ar' => 'نمو النبات', 'tr' => 'Bitki gelişimi', 'fr' => 'Croissance des cultures',
            ],
            'heading_seed_germination' => [
                'en' => 'Seed germination', 'ar' => 'إنبات البذور', 'tr' => 'Tohum çimlenmesi', 'fr' => 'Germination des semences',
            ],
            'heading_water_requirement' => [
                'en' => 'Water requirement', 'ar' => 'الاحتياج المائي', 'tr' => 'Su ihtiyacı', 'fr' => 'Besoin en eau',
            ],
            'heading_plant_nutrition' => [
                'en' => 'Plant nutrition', 'ar' => 'تغذية النبات', 'tr' => 'Bitki beslenmesi', 'fr' => 'Nutrition des plantes',
            ],
            'step' => [
                'en' => 'Step', 'ar' => 'الخطوة', 'tr' => 'Adım', 'fr' => 'Étape',
            ],
            'label_optimal_range' => [
                'en' => 'Optimal value/range', 'ar' => 'القيمة/النطاق الأمثل', 'tr' => 'Optimal değer/aralık', 'fr' => 'Valeur/plage optimale',
            ],
            'label_requirement' => [
                'en' => 'Requirement', 'ar' => 'الاحتياج', 'tr' => 'İhtiyaç', 'fr' => 'Besoin',
            ],
            'label_supported_value' => [
                'en' => 'Supported value', 'ar' => 'القيمة المدعومة', 'tr' => 'Desteklenen değer', 'fr' => 'Valeur étayée',
            ],
            'aspect' => [
                'en' => 'Aspect', 'ar' => 'جانب', 'tr' => 'Yön', 'fr' => 'Aspect',
            ],
            'sources' => [
                'en' => '### Sources', 'ar' => '### المصادر', 'tr' => '### Kaynaklar', 'fr' => '### Sources',
            ],
            'sources_disclaimer' => [
                'en' => 'The answer is based on aggregating available scientific evidence from the following sources.',
                'ar' => 'الإجابة مبنية على تجميع الأدلة العلمية المتاحة من المصادر التالية.',
                'tr' => 'Yanıt, aşağıdaki kaynaklardan elde edilen bilimsel kanıtların birleştirilmesine dayanır.',
                'fr' => 'La réponse est basée sur l’agrégation des preuves scientifiques disponibles provenant des sources suivantes.',
            ],
            'primary_source' => [
                'en' => '### Primary source', 'ar' => '### المصدر الأساسي', 'tr' => '### Birincil kaynak', 'fr' => '### Source principale',
            ],
            'primary_sources' => [
                'en' => '### Primary sources', 'ar' => '### المصادر الأساسية', 'tr' => '### Birincil kaynaklar', 'fr' => '### Sources principales',
            ],
            'title' => [
                'en' => 'Title', 'ar' => 'العنوان', 'tr' => 'Başlık', 'fr' => 'Titre',
            ],
            'authors' => [
                'en' => 'Authors', 'ar' => 'المؤلفون', 'tr' => 'Yazarlar', 'fr' => 'Auteurs',
            ],
            'journal' => [
                'en' => 'Journal', 'ar' => 'المجلة', 'tr' => 'Dergi', 'fr' => 'Revue',
            ],
            'organization' => [
                'en' => 'Organization', 'ar' => 'الجهة', 'tr' => 'Kuruluş', 'fr' => 'Organisation',
            ],
            'year' => [
                'en' => 'Year', 'ar' => 'السنة', 'tr' => 'Yıl', 'fr' => 'Année',
            ],
            'original_url' => [
                'en' => 'Original URL', 'ar' => 'الرابط الأصلي', 'tr' => 'Orijinal URL', 'fr' => 'URL d’origine',
            ],
            'supporting_info' => [
                'en' => 'Supporting/contextual information',
                'ar' => 'معلومة داعمة/سياقية',
                'tr' => 'Destekleyici/bağlamsal bilgi',
                'fr' => 'Information complémentaire/contextuelle',
            ],
            'source' => [
                'en' => 'Source', 'ar' => 'المصدر', 'tr' => 'Kaynak', 'fr' => 'Source',
            ],
            'additional_information' => [
                'en' => '### Additional information', 'ar' => '### معلومات إضافية', 'tr' => '### Ek bilgiler', 'fr' => '### Informations supplémentaires',
            ],
            'additional_supporting_intro' => [
                'en' => 'The following is supporting/contextual only and is not a confident direct answer:',
                'ar' => 'المعلومات التالية داعمة فقط وليست إجابة مباشرة مؤكدة:',
                'tr' => 'Aşağıdakiler yalnızca destekleyici/bağlamsaldır ve kesin doğrudan yanıt değildir:',
                'fr' => 'Ce qui suit est uniquement complémentaire/contextuel et n’est pas une réponse directe certaine :',
            ],
            'additional_related_intro' => [
                'en' => 'Related useful information from non-primary evidence:',
                'ar' => 'معلومات مرتبطة مفيدة من أدلة غير مباشرة:',
                'tr' => 'Birincil olmayan kanıtlardan ilgili yararlı bilgiler:',
                'fr' => 'Informations utiles connexes provenant de preuves non primaires :',
            ],
            'conflict_note' => [
                'en' => 'Conflict note: ', 'ar' => 'ملاحظة حول التعارض: ', 'tr' => 'Çelişki notu: ', 'fr' => 'Note de conflit : ',
            ],
            'uncertainty_prefix' => [
                'en' => 'Uncertainty: ', 'ar' => 'درجة اليقين: ', 'tr' => 'Belirsizlik: ', 'fr' => 'Incertitude : ',
            ],
            'conflict_message' => [
                'en' => 'Conflicting scientific evidence exists for this topic; values or conclusions may differ by source and conditions.',
                'ar' => 'توجد أدلة علمية متعارضة لهذا الموضوع؛ القيمة أو الاستنتاج قد يختلف حسب المصدر والظروف.',
                'tr' => 'Bu konuda çelişkili bilimsel kanıtlar vardır; değerler veya sonuçlar kaynağa ve koşullara göre değişebilir.',
                'fr' => 'Des preuves scientifiques contradictoires existent pour ce sujet ; les valeurs ou conclusions peuvent varier selon la source et les conditions.',
            ],
            'limited_conflicting' => [
                'en' => 'Limited or conflicting scientific evidence is available; review details and sources.',
                'ar' => 'تتوفر أدلة علمية محدودة أو متعارضة؛ راجع التفاصيل والمصادر.',
                'tr' => 'Sınırlı veya çelişkili bilimsel kanıtlar mevcuttur; ayrıntıları ve kaynakları inceleyin.',
                'fr' => 'Des preuves scientifiques limitées ou contradictoires sont disponibles ; consultez les détails et les sources.',
            ],
            'limitation_rejected' => [
                'en' => ':count source(s) were excluded for failing scientific validation.',
                'ar' => 'تم استبعاد :count مصدرًا لعدم اجتياز التحقق العلمي.',
                'tr' => 'Bilimsel doğrulamayı geçemediği için :count kaynak hariç tutuldu.',
                'fr' => ':count source(s) ont été exclues pour échec de la validation scientifique.',
            ],
            'limitation_partial' => [
                'en' => ':count source(s) provide partial support only.',
                'ar' => ':count مصدرًا يقدم دعمًا جزئيًا فقط.',
                'tr' => ':count kaynak yalnızca kısmi destek sağlar.',
                'fr' => ':count source(s) n’apportent qu’un soutien partiel.',
            ],
            'limitation_supporting' => [
                'en' => 'Evidence is supporting rather than fully direct; it must not be treated as a confident scientific answer.',
                'ar' => 'الأدلة داعمة جزئيًا وليست مباشرة بالكامل للسؤال؛ لا تُعامل كإجابة علمية مؤكدة.',
                'tr' => 'Kanıtlar tam doğrudan değil destekleyicidir; kesin bilimsel yanıt olarak ele alınmamalıdır.',
                'fr' => 'Les preuves sont complémentaires plutôt que pleinement directes ; elles ne doivent pas être traitées comme une réponse scientifique certaine.',
            ],
            'limitation_disagreement' => [
                'en' => 'Some validated scientific sources disagree.',
                'ar' => 'توجد تعارضات بين بعض المصادر العلمية المعتمدة.',
                'tr' => 'Bazı doğrulanmış bilimsel kaynaklar birbiriyle çelişir.',
                'fr' => 'Certaines sources scientifiques validées sont en désaccord.',
            ],
            'uncertainty_insufficient' => [
                'en' => 'Available scientific evidence is insufficient for a definitive conclusion.',
                'ar' => 'الأدلة العلمية المتاحة غير كافية لإعطاء نتيجة مؤكدة.',
                'tr' => 'Mevcut bilimsel kanıtlar kesin bir sonuç için yetersizdir.',
                'fr' => 'Les preuves scientifiques disponibles sont insuffisantes pour une conclusion définitive.',
            ],
            'uncertainty_secondary_conflicts' => [
                'en' => 'Some secondary sources disagree; the primary conclusion is supported by direct evidence.',
                'ar' => 'توجد بعض التعارضات الثانوية بين المصادر؛ الاستنتاج الرئيسي مدعوم بأدلة مباشرة.',
                'tr' => 'Bazı ikincil kaynaklar çelişir; ana sonuç doğrudan kanıtlarla desteklenir.',
                'fr' => 'Certaines sources secondaires sont en désaccord ; la conclusion principale est étayée par des preuves directes.',
            ],
            'uncertainty_conflicting' => [
                'en' => 'Conflicting evidence exists; a single universal value or conclusion should not be assumed.',
                'ar' => 'توجد أدلة متعارضة؛ لا ينبغي افتراض قيمة أو استنتاج واحد شامل.',
                'tr' => 'Çelişkili kanıtlar vardır; tek bir evrensel değer veya sonuç varsayılmamalıdır.',
                'fr' => 'Des preuves contradictoires existent ; une valeur ou conclusion universelle unique ne doit pas être supposée.',
            ],
            'uncertainty_aggregated' => [
                'en' => 'The answer aggregates multiple supporting sources; no single direct source fully covers the question.',
                'ar' => 'الإجابة مبنية على تجميع أدلة داعمة متعددة؛ لا يوجد مصدر مباشر واحد يغطي السؤال بالكامل.',
                'tr' => 'Yanıt birden fazla destekleyici kaynağı birleştirir; hiçbir tek doğrudan kaynak soruyu tam kapsamaz.',
                'fr' => 'La réponse agrège plusieurs sources complémentaires ; aucune source directe unique ne couvre entièrement la question.',
            ],
            'uncertainty_supporting_only' => [
                'en' => 'Available evidence is supporting-only; it is insufficient for a confident scientific answer or direct optimum.',
                'ar' => 'الأدلة المتاحة داعمة فقط؛ لا تكفي لإجابة علمية مؤكدة أو قيمة مثلى مباشرة.',
                'tr' => 'Mevcut kanıtlar yalnızca destekleyicidir; kesin bilimsel yanıt veya doğrudan optimum için yetersizdir.',
                'fr' => 'Les preuves disponibles sont uniquement complémentaires ; elles sont insuffisantes pour une réponse scientifique certaine ou un optimum direct.',
            ],
            'uncertainty_single_source' => [
                'en' => 'The conclusion relies on a single validated source; practical applications may require additional evidence.',
                'ar' => 'الاستنتاج يعتمد على مصدر علمي واحد معتمد؛ قد تتطلب التطبيقات العملية مصادر إضافية.',
                'tr' => 'Sonuç tek bir doğrulanmış kaynağa dayanır; pratik uygulamalar ek kanıt gerektirebilir.',
                'fr' => 'La conclusion repose sur une seule source validée ; les applications pratiques peuvent nécessiter des preuves supplémentaires.',
            ],
            'insufficient_irrelevant' => [
                'en' => 'Available scientific evidence is not sufficiently relevant for a definitive conclusion.',
                'ar' => 'الأدلة العلمية المتاحة غير ذات صلة كافية أو غير كافية لإعطاء نتيجة مؤكدة.',
                'tr' => 'Mevcut bilimsel kanıtlar kesin bir sonuç için yeterince ilgili değildir.',
                'fr' => 'Les preuves scientifiques disponibles ne sont pas suffisamment pertinentes pour une conclusion définitive.',
            ],
            'rejected_during_validation' => [
                'en' => ':count source(s) were rejected during validation.',
                'ar' => 'تم رفض :count مصدرًا أثناء التحقق.',
                'tr' => 'Doğrulama sırasında :count kaynak reddedildi.',
                'fr' => ':count source(s) ont été rejetées pendant la validation.',
            ],
        ];
    }
}
