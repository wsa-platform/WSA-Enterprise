import 'package:wsa_enterprise/data/api/ai_api.dart';
import 'package:wsa_enterprise/data/models/models.dart';

typedef AiRequestFetcher = Future<ApiAiRequest> Function(int id);

/// Polls an AI request until it reaches a terminal state or times out.
class PollAiRequestUseCase {
  PollAiRequestUseCase({
    required AiApi aiApi,
    this.interval = const Duration(milliseconds: 750),
    this.timeout = const Duration(seconds: 30),
  }) : _fetch = aiApi.fetchRequest;

  PollAiRequestUseCase.withFetcher({
    required AiRequestFetcher fetch,
    this.interval = const Duration(milliseconds: 750),
    this.timeout = const Duration(seconds: 30),
  }) : _fetch = fetch;

  final AiRequestFetcher _fetch;
  final Duration interval;
  final Duration timeout;

  Future<ApiAiRequest> execute(int requestId) async {
    final deadline = DateTime.now().add(timeout);
    ApiAiRequest latest = await _fetch(requestId);

    while (latest.isPending && DateTime.now().isBefore(deadline)) {
      await Future<void>.delayed(interval);
      latest = await _fetch(requestId);
    }

    return latest;
  }
}
