class AppConfig {
  const AppConfig._();

  static const apiUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://localhost:8081/api/v1',
  );

  static const adminPermissions = {'access.manage', 'services.supervise'};
}
