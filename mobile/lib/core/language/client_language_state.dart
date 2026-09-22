import 'package:wsa_enterprise/core/language/supported_language.dart';

/// Holds UI locale and Accept-Language preference separately.
///
/// - [uiLocale]: Flutter chrome / Directionality preference.
/// - [acceptLanguage]: value sent as HTTP `Accept-Language` (API locale header).
///
/// Scientific answer language is NOT set here — it is produced by the backend
/// from the question and returned as Stage 5 `language`.
class ClientLanguageState {
  ClientLanguageState({
    String uiLocale = SupportedLanguage.arabic,
    String? acceptLanguage,
  })  : uiLocale = SupportedLanguage.normalize(uiLocale),
        _acceptLanguage = SupportedLanguage.normalize(
          acceptLanguage ?? uiLocale,
        );

  String uiLocale;
  String _acceptLanguage;

  /// Explicit Accept-Language preference (wins over UI locale when set via [setAcceptLanguage]).
  String get acceptLanguage => _acceptLanguage;

  void setUiLocale(String code) {
    uiLocale = SupportedLanguage.normalize(code);
  }

  /// Explicit answer/API language preference for the Accept-Language header.
  void setAcceptLanguage(String code) {
    _acceptLanguage = SupportedLanguage.normalize(code);
  }

  /// Align Accept-Language with UI locale (default coupling when user has not set an override).
  void syncAcceptLanguageToUi() {
    _acceptLanguage = uiLocale;
  }
}
