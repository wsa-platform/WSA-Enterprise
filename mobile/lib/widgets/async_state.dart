import 'package:flutter/material.dart';

class AsyncState extends StatelessWidget {
  const AsyncState({
    super.key,
    required this.loading,
    this.error,
    this.forbidden = false,
    this.empty = false,
    this.emptyMessage = 'No records found.',
    this.onRetry,
    required this.child,
  });

  final bool loading;
  final String? error;
  final bool forbidden;
  final bool empty;
  final String emptyMessage;
  final VoidCallback? onRetry;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    if (loading && empty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (forbidden) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.lock_outline, size: 40, color: Colors.orange),
              const SizedBox(height: 12),
              Text(
                error ?? 'You do not have permission to view these records.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.orange),
              ),
            ],
          ),
        ),
      );
    }

    if (error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(error!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.red)),
              if (onRetry != null) ...[
                const SizedBox(height: 16),
                FilledButton(onPressed: onRetry, child: const Text('Retry')),
              ],
            ],
          ),
        ),
      );
    }

    if (empty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(emptyMessage, textAlign: TextAlign.center),
        ),
      );
    }

    return child;
  }
}
