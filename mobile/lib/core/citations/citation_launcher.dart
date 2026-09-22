import 'package:url_launcher/url_launcher.dart';

/// Opens citation URLs directly (no Google/search intermediary).
typedef CitationLaunchFn = Future<bool> Function(
  Uri uri, {
  LaunchMode mode,
});

class CitationLauncher {
  CitationLauncher({CitationLaunchFn? launchFn}) : _launchFn = launchFn ?? launchUrl;

  final CitationLaunchFn _launchFn;

  /// Returns true when a valid http(s) URL was handed to the platform launcher unchanged.
  Future<CitationLaunchResult> openDirectUrl(String? rawUrl) async {
    final trimmed = rawUrl?.trim() ?? '';
    if (trimmed.isEmpty) {
      return const CitationLaunchResult(ok: false, reason: 'missing_url');
    }

    final uri = Uri.tryParse(trimmed);
    if (uri == null || !uri.hasScheme || uri.host.isEmpty) {
      return const CitationLaunchResult(ok: false, reason: 'invalid_url');
    }

    final scheme = uri.scheme.toLowerCase();
    if (scheme != 'http' && scheme != 'https') {
      return const CitationLaunchResult(ok: false, reason: 'unsupported_scheme');
    }

    // Pass the original URI unchanged — never rewrite to a search engine.
    final launched = await _launchFn(uri, mode: LaunchMode.externalApplication);
    return CitationLaunchResult(
      ok: launched,
      reason: launched ? null : 'launch_failed',
      launchedUri: uri,
    );
  }
}

class CitationLaunchResult {
  const CitationLaunchResult({
    required this.ok,
    this.reason,
    this.launchedUri,
  });

  final bool ok;
  final String? reason;
  final Uri? launchedUri;
}
