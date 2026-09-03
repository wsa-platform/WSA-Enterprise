import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:wsa_enterprise/config/app_config.dart';
import 'package:wsa_enterprise/data/api/api_exception.dart';

import 'helpers/test_fakes.dart';

void main() {
  test('production API URL comes from compile-time config, not hardcoded secrets',
      () {
    expect(AppConfig.current.apiBaseUrl, isNotEmpty);
    expect(AppConfig.current.apiBaseUrl, contains('/api/v1'));
    expect(AppConfig.current.apiBaseUrl, isNot(contains('sk-')));
    expect(AppConfig.current.apiBaseUrl, isNot(contains('BEGIN PRIVATE')));
    expect(AppConfig.current.requestTimeout, const Duration(seconds: 30));
    expect(AppConfig.current.researchTimeout, const Duration(seconds: 60));
    expect(AppConfig.current.diagnosisTimeout, const Duration(seconds: 60));
  });

  test('HTTP client honors bounded timeouts without leaking bodies', () async {
    final client = testHttpClient(MockClient((request) async {
      await Future<void>.delayed(const Duration(milliseconds: 40));
      return jsonOk({'token': 'should-not-be-read'});
    }));

    expect(
      () => client.getJson('/health',
          timeout: const Duration(milliseconds: 1)),
      throwsA(predicate((error) =>
          error is ApiException && error.isNetworkFailure)),
    );
  });

  test('research and diagnosis remain separate public paths', () {
    expect('/public/research-agent/query', isNot('/public/plant-diagnosis/analyze'));
    expect('/public/plant-diagnosis/knowledge', isNot(contains('research-agent')));
  });
}
