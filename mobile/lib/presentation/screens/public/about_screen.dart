import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class AboutScreen extends StatefulWidget {
  const AboutScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends State<AboutScreen> {
  Map<String, dynamic>? catalog;
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
      catalog = await widget.client.publicApi.serviceCatalog();
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
    } catch (_) {
      error = ArStrings.networkError;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PublicAsyncBody(
      loading: loading,
      error: error,
      onRetry: load,
      child: catalog == null
          ? const SizedBox.shrink()
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(ArStrings.about,
                    style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 8),
                Text('${catalog!['platform'] ?? ArStrings.appTitle}'),
                const SizedBox(height: 8),
                Text('${catalog!['description'] ?? ''}'),
              ],
            ),
    );
  }
}
