import 'package:flutter/material.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/async_state.dart';
import 'package:wsa_admin/presentation/widgets/partial_failure_banner.dart';

/// Shared layout for admin module screens: title, search, filters, async states.
class ModuleScreenLayout extends StatelessWidget {
  const ModuleScreenLayout({
    super.key,
    required this.title,
    required this.loading,
    this.error,
    this.empty = false,
    this.emptyMessage = Ar.emptyData,
    this.partialFailure = false,
    required this.onRetry,
    this.searchHint,
    this.onSearchChanged,
    this.filterChips = const [],
    required this.body,
  });

  final String title;
  final bool loading;
  final String? error;
  final bool empty;
  final String emptyMessage;
  final bool partialFailure;
  final VoidCallback onRetry;
  final String? searchHint;
  final ValueChanged<String>? onSearchChanged;
  final List<Widget> filterChips;
  final Widget body;

  @override
  Widget build(BuildContext context) {
    return AsyncState(
      loading: loading,
      error: error,
      empty: empty,
      emptyMessage: emptyMessage,
      onRetry: onRetry,
      child: RefreshIndicator(
        onRefresh: () async => onRetry(),
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
                  ),
                ),
                TextButton.icon(
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh),
                  label: const Text(Ar.refresh),
                ),
              ],
            ),
            if (partialFailure) ...[
              const SizedBox(height: 12),
              PartialFailureBanner(onRetry: onRetry),
            ],
            if (searchHint != null || filterChips.isNotEmpty) ...[
              const SizedBox(height: 16),
              if (searchHint != null)
                TextField(
                  decoration: InputDecoration(
                    hintText: searchHint,
                    prefixIcon: const Icon(Icons.search),
                    border: const OutlineInputBorder(),
                    isDense: true,
                  ),
                  onChanged: onSearchChanged,
                ),
              if (filterChips.isNotEmpty) ...[
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: filterChips,
                ),
              ],
            ],
            const SizedBox(height: 20),
            body,
          ],
        ),
      ),
    );
  }
}
