import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class StoreScreen extends StatefulWidget {
  const StoreScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<StoreScreen> createState() => _StoreScreenState();
}

class _StoreScreenState extends State<StoreScreen> {
  List<dynamic> rows = [];
  String? error;
  bool loading = true;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      rows = await widget.client.publicApi.marketListings();
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
      rows = [];
    } catch (_) {
      error = ArStrings.networkError;
      rows = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PublicAsyncBody(
      loading: loading,
      error: error,
      empty: !loading && rows.isEmpty,
      onRetry: load,
      child: ListView(
        children: [
          for (final row in rows)
            ListTile(
              title: Text('${(row as Map)['title'] ?? row['name'] ?? 'منتج'}'),
              subtitle: Text('${row['summary'] ?? row['description'] ?? ''}'),
            ),
        ],
      ),
    );
  }
}
