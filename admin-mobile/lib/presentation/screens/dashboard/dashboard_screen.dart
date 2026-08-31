import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/widgets/chart_widget.dart';
import 'package:wsa_admin/presentation/widgets/async_state.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/partial_failure_banner.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.client});

  final ApiClient client;

  @visibleForTesting
  static Future<DashboardMetrics> Function(ApiClient client)? debugLoader;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _loading = true;
  String? _error;
  bool _partialFailure = false;
  late DashboardMetrics _metrics;
  Map<String, dynamic> _traffic = {};

  @override
  void initState() {
    super.initState();
    _metrics = DashboardMetrics.empty();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
      _partialFailure = false;
    });

    try {
      final loader = DashboardScreen.debugLoader ?? DashboardMetrics.load;
      final metrics = await loader(widget.client);
      Map<String, dynamic> traffic = {};
      try {
        traffic = await widget.client.adminModules.analyticsTraffic(days: 7);
      } catch (_) {}
      if (!mounted) return;
      if (metrics.allSourcesFailed) {
        setState(() {
          _error = Ar.dashboardLoadFailed;
          _loading = false;
        });
        return;
      }
      setState(() {
        _metrics = metrics;
        _traffic = traffic;
        _partialFailure = metrics.hasPartialFailures;
        _loading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.toString();
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.toString();
        _loading = false;
      });
    }
  }

  Map<String, dynamic>? get _currentOrganization {
    final orgId = widget.client.organizationId;
    if (orgId == null) return null;
    for (final org in widget.client.organizations) {
      if (org['id'] == orgId) return org;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final isEmpty = !_loading && _error == null && _metrics.isEmpty;
    final user = widget.client.user;
    final userName = user?['name']?.toString() ?? '';
    final userEmail = user?['email']?.toString() ?? '';
    final organization = _currentOrganization;
    final orgName = organization?['name']?.toString() ?? Ar.notAvailable;
    final orgRole = organization?['role']?.toString();

    return AsyncState(
      loading: _loading,
      error: _error,
      empty: isEmpty,
      onRetry: _load,
      child: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    Ar.navDashboard,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
                  ),
                ),
                TextButton.icon(
                  onPressed: _load,
                  icon: const Icon(Icons.refresh),
                  label: const Text(Ar.refresh),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (_partialFailure)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: PartialFailureBanner(onRetry: _load),
              ),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (userName.isNotEmpty || userEmail.isNotEmpty) ...[
                      Text(
                        Ar.dashboardSignedInAs,
                        style: Theme.of(context).textTheme.labelLarge?.copyWith(color: Colors.black54),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        userName.isNotEmpty ? userName : userEmail,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                      ),
                      if (userEmail.isNotEmpty && userName.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(userEmail, style: Theme.of(context).textTheme.bodySmall),
                      ],
                      const SizedBox(height: 12),
                    ],
                    Text(
                      Ar.dashboardOrganization,
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(color: Colors.black54),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      orgName,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                    ),
                    if (orgRole != null && orgRole.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        '${Ar.dashboardRole}: $orgRole',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            ResponsiveMetricGrid(
              children: [
                MetricCard(
                  label: Ar.metricActiveUsers,
                  value: _metrics.activeUsers,
                  icon: Icons.people_outline,
                  tone: MetricTone.green,
                ),
                MetricCard(
                  label: Ar.metricOrganizations,
                  value: _metrics.organizations,
                  icon: Icons.business_outlined,
                  tone: MetricTone.primary,
                ),
                MetricCard(
                  label: Ar.metricProducts,
                  value: _metrics.products,
                  icon: Icons.inventory_2_outlined,
                  tone: MetricTone.primary,
                ),
                MetricCard(
                  label: Ar.metricCrops,
                  value: _metrics.crops,
                  icon: Icons.grass_outlined,
                  tone: MetricTone.green,
                ),
                MetricCard(
                  label: Ar.metricFarms,
                  value: _metrics.farms,
                  icon: Icons.agriculture_outlined,
                  tone: MetricTone.green,
                ),
                MetricCard(
                  label: Ar.metricCommunications,
                  value: _metrics.communications,
                  subtitle: _metrics.communicationsSubtitle,
                  icon: Icons.forum_outlined,
                  tone: MetricTone.amber,
                ),
                MetricCard(
                  label: Ar.metricReports,
                  value: _metrics.reports,
                  subtitle: _metrics.reportsSubtitle,
                  icon: Icons.analytics_outlined,
                  tone: MetricTone.blue,
                ),
                MetricCard(
                  label: Ar.metricSystemHealth,
                  value: _metrics.systemHealth,
                  subtitle: _metrics.systemHealthSubtitle,
                  icon: Icons.monitor_heart_outlined,
                  tone: _metrics.systemHealth == Ar.systemHealthy ? MetricTone.green : MetricTone.red,
                ),
                MetricCard(
                  label: Ar.metricJobSeekers,
                  value: _metrics.jobSeekers,
                  icon: Icons.work_outline,
                  tone: MetricTone.blue,
                ),
                MetricCard(
                  label: Ar.metricMarketplace,
                  value: _metrics.marketplace,
                  icon: Icons.storefront_outlined,
                  tone: MetricTone.amber,
                ),
                MetricCard(
                  label: Ar.metricAlerts,
                  value: _metrics.alerts,
                  icon: Icons.notifications_active_outlined,
                  tone: MetricTone.red,
                ),
              ],
            ),
            if (_traffic.isNotEmpty) ...[
              const SizedBox(height: 24),
              ChartWidget(
                title: Ar.chartTraffic,
                series: _trafficChartSeries(),
                loading: false,
              ),
            ],
            if (_metrics.recentAudit.isNotEmpty) ...[
              const SizedBox(height: 24),
              Text(Ar.dashboardRecentAudit, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 12),
              ..._metrics.recentAudit.map((row) => ListTile(
                leading: const Icon(Icons.history),
                title: Text(row.action),
                subtitle: Text('${row.userName} — ${row.createdAt}'),
              )),
            ],
          ],
        ),
      ),
    );
  }

  List<Map<String, dynamic>> _trafficChartSeries() {
    final charts = _traffic['charts'] as Map<String, dynamic>?;
    final series = charts?['page_views_over_time'] as List<dynamic>? ?? _traffic['traffic'] as List<dynamic>? ?? [];
    return series.map((row) => Map<String, dynamic>.from(row as Map)).toList();
  }
}

class DashboardMetrics {
  DashboardMetrics({
    required this.organizations,
    required this.activeUsers,
    required this.farms,
    required this.crops,
    required this.products,
    required this.communications,
    this.communicationsSubtitle,
    required this.reports,
    this.reportsSubtitle,
    required this.systemHealth,
    this.systemHealthSubtitle,
    required this.alerts,
    required this.jobSeekers,
    required this.marketplace,
    this.recentAudit = const [],
    this.allSourcesFailed = false,
    this.hasPartialFailures = false,
  });

  static const sourceCount = 8;

  final String organizations;
  final String activeUsers;
  final String farms;
  final String crops;
  final String products;
  final String communications;
  final String? communicationsSubtitle;
  final String reports;
  final String? reportsSubtitle;
  final String systemHealth;
  final String? systemHealthSubtitle;
  final String alerts;
  final String jobSeekers;
  final String marketplace;
  final List<AuditRow> recentAudit;
  final bool allSourcesFailed;
  final bool hasPartialFailures;

  bool get isEmpty =>
      organizations == Ar.notAvailable &&
      activeUsers == Ar.notAvailable &&
      farms == Ar.notAvailable &&
      crops == Ar.notAvailable &&
      products == Ar.notAvailable &&
      communications == Ar.notAvailable &&
      reports == Ar.notAvailable &&
      alerts == Ar.notAvailable;

  factory DashboardMetrics.empty() => DashboardMetrics(
        organizations: Ar.notAvailable,
        activeUsers: Ar.notAvailable,
        farms: Ar.notAvailable,
        crops: Ar.notAvailable,
        products: Ar.notAvailable,
        communications: Ar.notAvailable,
        reports: Ar.notAvailable,
        systemHealth: Ar.systemUnknown,
        alerts: Ar.notAvailable,
        jobSeekers: Ar.notAvailable,
        marketplace: Ar.notAvailable,
      );

  static Future<DashboardMetrics> load(ApiClient client) async {
    final results = await Future.wait<_FetchResult<Object?>>([
      _safe(() => client.platform.organizations()),
      _safe(() => client.platform.accessSummary()),
      _safe(() => client.platform.workflowSummary()),
      _safe(() => client.platform.analyticsOverview()),
      _safe(() => client.platform.marketingDashboard()),
      _safe(() => client.platform.monitoringHealth()),
      _safe(() => client.adminModules.reportsOverview(days: 7)),
      _safe(() => client.platform.dashboard()),
    ]);

    final failedCount = results.where((result) => result.failed).length;
    final allFailed = failedCount == sourceCount;
    final partialFailures = failedCount > 0 && !allFailed;

    final orgRows = results[0].value as List<dynamic>?;
    final access = results[1].value as Map<String, dynamic>?;
    final workflow = results[2].value as Map<String, dynamic>?;
    final analytics = results[3].value as Map<String, dynamic>?;
    final marketing = results[4].value as Map<String, dynamic>?;
    final monitoring = results[5].value as Map<String, dynamic>?;
    final reportsOverview = results[6].value as Map<String, dynamic>?;
    final dashboard = results[7].value as Map<String, dynamic>?;

    final system = access?['system'] as Map<String, dynamic>?;
    final notifications = analytics?['notifications'] as Map<String, dynamic>?;
    final audit = analytics?['audit'] as Map<String, dynamic>?;
    final catalog = reportsOverview?['catalog'] as Map<String, dynamic>?;
    final agriculture = reportsOverview?['agriculture'] as Map<String, dynamic>?;

    final auditRaw = access?['recent_audit'] as List<dynamic>?;
    final recentAudit = auditRaw != null
        ? auditRaw.map((row) => AuditRow.fromJson(Map<String, dynamic>.from(row as Map))).toList()
        : <AuditRow>[];

    final monitoringStatus = monitoring?['status']?.toString();
    final systemHealth = monitoringStatus == 'healthy'
        ? Ar.systemHealthy
        : monitoringStatus == 'degraded'
            ? Ar.systemDegraded
            : system?['api']?.toString() ?? Ar.systemUnknown;

    final unread = notifications?['unread'];
    final campaigns = marketing?['campaigns'];

    final metrics = dashboard?['metrics'] as Map<String, dynamic>?;

    return DashboardMetrics(
      organizations: orgRows != null ? '${orgRows.length}' : Ar.notAvailable,
      activeUsers: access?['users_count']?.toString() ?? analytics?['users']?['total']?.toString() ?? Ar.notAvailable,
      farms: agriculture?['farms_total']?.toString() ?? workflow?['farms']?.toString() ?? Ar.notAvailable,
      crops: agriculture?['crop_types_total']?.toString() ?? Ar.notAvailable,
      products: catalog?['products_total']?.toString() ?? Ar.notAvailable,
      communications: unread?.toString() ?? campaigns?.toString() ?? Ar.notAvailable,
      communicationsSubtitle: campaigns != null ? 'حملات: $campaigns' : null,
      reports: audit?['events_24h']?.toString() ?? access?['audit_events_24h']?.toString() ?? Ar.notAvailable,
      reportsSubtitle: audit != null ? 'نطاق: ${analytics?['scope'] ?? Ar.notAvailable}' : null,
      systemHealth: systemHealth,
      systemHealthSubtitle: system != null ? 'الطابور: ${system['queue'] ?? Ar.notAvailable}' : null,
      alerts: unread?.toString() ??
          marketing?['failed_deliveries']?.toString() ??
          Ar.notAvailable,
      jobSeekers: metrics?['job_seekers_active']?.toString() ?? Ar.notAvailable,
      marketplace: metrics?['marketplace_published']?.toString() ?? Ar.notAvailable,
      recentAudit: recentAudit,
      allSourcesFailed: allFailed,
      hasPartialFailures: partialFailures,
    );
  }

  static Future<_FetchResult<T>> _safe<T>(Future<T> Function() action) async {
    try {
      return _FetchResult(value: await action(), failed: false);
    } catch (_) {
      return const _FetchResult(value: null, failed: true);
    }
  }
}

class _FetchResult<T> {
  const _FetchResult({required this.value, required this.failed});

  final T? value;
  final bool failed;
}

class AuditRow {
  AuditRow({required this.action, required this.userName, required this.createdAt});

  final String action;
  final String userName;
  final String createdAt;

  factory AuditRow.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>?;
    return AuditRow(
      action: json['action']?.toString() ?? Ar.notAvailable,
      userName: user?['name']?.toString() ?? Ar.notAvailable,
      createdAt: json['created_at']?.toString() ?? Ar.notAvailable,
    );
  }
}
