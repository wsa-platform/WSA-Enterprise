/// Compile-time / environment configuration. No secrets.
class AppConfig {
  const AppConfig({
    required this.apiBaseUrl,
    required this.publicOrganizationSlug,
    required this.requestTimeout,
    required this.researchTimeout,
    required this.diagnosisTimeout,
  });

  final String apiBaseUrl;
  final String publicOrganizationSlug;
  final Duration requestTimeout;
  final Duration researchTimeout;
  final Duration diagnosisTimeout;

  static const AppConfig current = AppConfig(
    apiBaseUrl: String.fromEnvironment(
      'API_URL',
      defaultValue: 'http://localhost:8081/api/v1',
    ),
    publicOrganizationSlug: String.fromEnvironment(
      'PUBLIC_ORG_SLUG',
      defaultValue: 'wsa-demo',
    ),
    requestTimeout: Duration(seconds: 30),
    researchTimeout: Duration(seconds: 60),
    diagnosisTimeout: Duration(seconds: 60),
  );
}
