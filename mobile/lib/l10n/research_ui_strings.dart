import 'package:wsa_enterprise/core/language/supported_language.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';

/// Research chrome labels for ar/en/fr/tr without inventing a parallel l10n system.
/// Scientific answer body is never translated here.
class ResearchUiStrings {
  static String confidence(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Confidence',
        SupportedLanguage.french => 'Confiance',
        SupportedLanguage.turkish => 'Güven',
        _ => ArStrings.confidence,
      };

  static String limitations(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Limitations',
        SupportedLanguage.french => 'Limites',
        SupportedLanguage.turkish => 'Sınırlamalar',
        _ => ArStrings.limitations,
      };

  static String uncertainty(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Uncertainty',
        SupportedLanguage.french => 'Incertitude',
        SupportedLanguage.turkish => 'Belirsizlik',
        _ => 'عدم اليقين',
      };

  static String conflicts(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Conflicts',
        SupportedLanguage.french => 'Conflits',
        SupportedLanguage.turkish => 'Çelişkiler',
        _ => 'التعارضات',
      };

  static String sources(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Sources',
        SupportedLanguage.french => 'Sources',
        SupportedLanguage.turkish => 'Kaynaklar',
        _ => ArStrings.sources,
      };

  static String answer(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Answer',
        SupportedLanguage.french => 'Réponse',
        SupportedLanguage.turkish => 'Yanıt',
        _ => 'الإجابة',
      };

  static String answerLanguage(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Answer language',
        SupportedLanguage.french => 'Langue de la réponse',
        SupportedLanguage.turkish => 'Yanıt dili',
        _ => 'لغة الإجابة',
      };

  static String openSource(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Open source',
        SupportedLanguage.french => 'Ouvrir la source',
        SupportedLanguage.turkish => 'Kaynağı aç',
        _ => 'فتح المصدر',
      };

  static String citationUnavailable(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Source link unavailable.',
        SupportedLanguage.french => 'Lien de source indisponible.',
        SupportedLanguage.turkish => 'Kaynak bağlantısı kullanılamıyor.',
        _ => 'رابط المصدر غير متاح.',
      };

  static String status(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Status',
        SupportedLanguage.french => 'Statut',
        SupportedLanguage.turkish => 'Durum',
        _ => 'الحالة',
      };

  static String claims(String uiLang) => switch (SupportedLanguage.normalize(uiLang)) {
        SupportedLanguage.english => 'Claims',
        SupportedLanguage.french => 'Affirmations',
        SupportedLanguage.turkish => 'İddialar',
        _ => 'الادعاءات',
      };
}
