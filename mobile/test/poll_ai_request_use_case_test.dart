import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_enterprise/data/models/models.dart';
import 'package:wsa_enterprise/domain/use_cases/poll_ai_request_use_case.dart';

void main() {
  group('PollAiRequestUseCase', () {
    test('returns immediately when request is already terminal', () async {
      var calls = 0;
      final useCase = PollAiRequestUseCase.withFetcher(
        fetch: (id) async {
          calls++;
          return ApiAiRequest(
            id: id,
            status: 'completed',
            requestType: 'library_qa',
            output: const {'answer': 'ok'},
          );
        },
        interval: const Duration(milliseconds: 1),
      );

      final result = await useCase.execute(42);
      expect(result.status, 'completed');
      expect(calls, 1);
    });

    test('polls until terminal status', () async {
      var calls = 0;
      final useCase = PollAiRequestUseCase.withFetcher(
        fetch: (id) async {
          calls++;
          if (calls < 3) {
            return ApiAiRequest(
                id: id, status: 'pending', requestType: 'library_qa');
          }
          return ApiAiRequest(
              id: id, status: 'completed', requestType: 'library_qa');
        },
        interval: const Duration(milliseconds: 1),
        timeout: const Duration(seconds: 2),
      );

      final result = await useCase.execute(7);
      expect(result.status, 'completed');
      expect(calls, greaterThanOrEqualTo(3));
    });
  });
}
