import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class BeekeepingScreen extends StatefulWidget {
  const BeekeepingScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<BeekeepingScreen> createState() => _BeekeepingScreenState();
}

class _BeekeepingScreenState extends State<BeekeepingScreen> {
  List<dynamic> topics = [];
  String? error;
  bool loading = false;
  bool unavailable = false;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    if (widget.client.token == null) {
      setState(() {
        unavailable = true;
        loading = false;
        error = null;
      });
      return;
    }
    setState(() {
      loading = true;
      error = null;
      unavailable = false;
    });
    try {
      topics = await widget.client.fetchList('/beekeeping/knowledge/topics');
    } on ApiException catch (e) {
      if (e.statusCode == 404 || e.statusCode == 403) {
        unavailable = true;
      } else {
        error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
      }
      topics = [];
    } catch (_) {
      error = ArStrings.networkError;
      topics = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (unavailable) {
      return const UnavailableServiceScreen(
        title: ArStrings.honeyBees,
        detail:
            'نحل العسل متاح للحسابات المسجّلة عبر واجهات النحل المحمية، ولا توجد واجهة عامة مستقلة.',
      );
    }
    return PublicAsyncBody(
      loading: loading,
      error: error,
      empty: !loading && topics.isEmpty,
      onRetry: load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          for (final row in topics)
            ListTile(
              title: Text(
                  '${(row as Map)['title_ar'] ?? row['title'] ?? row['name'] ?? 'موضوع'}'),
            ),
        ],
      ),
    );
  }
}
