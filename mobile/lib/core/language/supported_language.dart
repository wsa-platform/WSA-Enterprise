/// Supported client language codes (UI chrome + Accept-Language).
/// Scientific answer language remains server-authoritative (question → response.language).
class SupportedLanguage {
  static const arabic = 'ar';
  static const english = 'en';
  static const french = 'fr';
  static const turkish = 'tr';

  static const List<String> all = [arabic, english, french, turkish];

  static String normalize(String? raw, {String fallback = arabic}) {
    final code = (raw ?? '').trim().toLowerCase();
    if (code.length >= 2) {
      final primary = code.substring(0, 2);
      if (all.contains(primary)) return primary;
    }
    return fallback;
  }
}
