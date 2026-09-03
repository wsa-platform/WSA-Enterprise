import 'package:flutter/material.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';

class PublicAsyncBody extends StatelessWidget {
  const PublicAsyncBody({
    super.key,
    required this.loading,
    this.error,
    this.empty = false,
    this.emptyMessage = ArStrings.empty,
    this.onRetry,
    required this.child,
  });

  final bool loading;
  final String? error;
  final bool empty;
  final String emptyMessage;
  final VoidCallback? onRetry;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return AsyncState(
      loading: loading,
      error: error,
      empty: empty,
      emptyMessage: emptyMessage,
      retryLabel: ArStrings.retry,
      onRetry: onRetry,
      child: child,
    );
  }
}

class UnavailableServiceScreen extends StatelessWidget {
  const UnavailableServiceScreen({super.key, required this.title, this.detail});

  final String title;
  final String? detail;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        Text(title, style: Theme.of(context).textTheme.headlineSmall),
        const SizedBox(height: 12),
        Text(detail ?? ArStrings.unavailable),
        const SizedBox(height: 12),
        const Text(ArStrings.noFakeData),
      ],
    );
  }
}
