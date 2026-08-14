import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/widgets/async_state.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _loading = true;
  String? _error;
  late DashboardMetrics _metrics;

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
    });

    try {
      final metrics = await DashboardMetrics.load(widget.client);
      if (!mounted) return;
      setState(() {
        _metrics = metrics;
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

  @override
  Widget build(BuildContext context) {
    return AsyncState(
      loading: _loading,
      error: _error,
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
            const SizedBox(height: 20),
            LayoutBuilder(
              builder: (context, constraints) {
                final crossAxisCount = constraints.maxWidth >= 1200
                    ? 4
                    : constraints.maxWidth >= 800
                        ? 3
                        : constraints.maxWidth >= 560
                            ? 2
                            : 1;

                return GridView.count(
                  crossAxisCount: crossAxisCount,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  childAspectRatio: 1.6,
                  children: [
                    MetricCard(
                      label: Ar.metricOrganizations,
                      value: _metrics.organizations,
                      icon: Icons.business_outlined,
                      tone: MetricTone.primary,
                    ),
                    MetricCard(
                      label: Ar.metricActiveUsers,
                      value: _metrics.activeUsers,
                      icon: Icons.people_outline,
                      tone: MetricTone.green,
                    ),
                    MetricCard(
                      label: Ar.metricFarms,
                      value: _metrics.farms,
                      icon: Icons.agriculture_outlined,
                      tone: MetricTone.blue,
                    ),
                    MetricCard(
                      label: Ar.metricCrops,
                      value: _metrics.crops,
                      icon: Icons.grass_outlined,
                      tone: MetricTone.green,
                    ),
                    MetricCard(
                      label: Ar.metricCourses,
                      value: _metrics.courses,
                      icon: Icons.school_outlined,
                      tone: MetricTone.amber,
                    ),
                    MetricCard(
                      label: Ar.metricProducts,
                      value: _metrics.products,
                      icon: Icons.inventory_2_outlined,
                      tone: MetricTone.primary,
                    ),
                    MetricCard(
                      label: Ar.metricCampaigns,
                      value: _metrics.campaigns,
                      icon: Icons.campaign_outlined,
                      tone: MetricTone.amber,
                    ),
                    MetricCard(
                      label: Ar.metricAiUsage,
                      value: _metrics.aiUsage,
                      subtitle: _metrics.aiUsageSubtitle,
                      icon: Icons.smart_toy_outlined,
                      tone: MetricTone.blue,
                    ),
                    MetricCard(
                      label: Ar.metricRecentActivity,
                      value: _metrics.recentActivity,
                      subtitle: _metrics.recentActivitySubtitle,
                      icon: Icons.history,
                      tone: MetricTone.primary,
                    ),
                    MetricCard(
                      label: Ar.metricSystemHealth,
                      value: _metrics.systemHealth,
                      subtitle: _metrics.systemHealthSubtitle,
                      icon: Icons.monitor_heart_outlined,
                      tone: _metrics.systemHealth == Ar.systemHealthy ? MetricTone.green : MetricTone.red,
                    ),
                    MetricCard(
                      label: Ar.metricAlerts,
                      value: _metrics.alerts,
                      icon: Icons.notifications_active_outlined,
                      tone: MetricTone.red,
                    ),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class DashboardMetrics {
  DashboardMetrics({
    required this.organizations,
    required this.activeUsers,
    required this.farms,
    required this.crops,
    required this.courses,
    required this.products,
    required this.campaigns,
    required this.aiUsage,
    this.aiUsageSubtitle,
    required this.recentActivity,
    this.recentActivitySubtitle,
    required this.systemHealth,
    this.systemHealthSubtitle,
    required this.alerts,
  });

  final String organizations;
  final String activeUsers;
  final String farms;
  final String crops;
  final String courses;
  final String products;
  final String campaigns;
  final String aiUsage;
  final String? aiUsageSubtitle;
  final String recentActivity;
  final String? recentActivitySubtitle;
  final String systemHealth;
  final String? systemHealthSubtitle;
  final String alerts;

  factory DashboardMetrics.empty() => DashboardMetrics(
        organizations: Ar.notAvailable,
        activeUsers: Ar.notAvailable,
        farms: Ar.notAvailable,
        crops: Ar.notAvailable,
        courses: Ar.notAvailable,
        products: Ar.notAvailable,
        campaigns: Ar.notAvailable,
        aiUsage: Ar.notAvailable,
        recentActivity: Ar.notAvailable,
        systemHealth: Ar.systemUnknown,
        alerts: Ar.notAvailable,
      );

  static Future<DashboardMetrics> load(ApiClient client) async {
    final results = await Future.wait<Object?>([
      _safe(() => client.platform.organizations()),
      _safe(() => client.platform.accessSummary()),
      _safe(() => client.platform.workflowSummary()),
      _safe(() => client.platform.analyticsOverview()),
      _safe(() => client.platform.marketingDashboard()),
      _safe(() => client.platform.monitoringHealth()),
    ]);

    final orgRows = results[0] as List<dynamic>?;
    final access = results[1] as Map<String, dynamic>?;
    final workflow = results[2] as Map<String, dynamic>?;
    final analytics = results[3] as Map<String, dynamic>?;
    final marketing = results[4] as Map<String, dynamic>?;
    final monitoring = results[5] as Map<String, dynamic>?;

    final aiRequests = access?['ai_requests'] as Map<String, dynamic>?;
    final recentAudit = access?['recent_audit'] as List<dynamic>?;
    final system = access?['system'] as Map<String, dynamic>?;

    final notifications = analytics?['notifications'] as Map<String, dynamic>?;

    final monitoringStatus = monitoring?['status']?.toString();
    final systemHealth = monitoringStatus == 'healthy'
        ? Ar.systemHealthy
        : monitoringStatus == 'degraded'
            ? Ar.systemDegraded
            : system?['api']?.toString() ?? Ar.systemUnknown;

    final auditCount = access?['audit_events_24h'];
    final recentActivityValue = recentAudit != null && recentAudit.isNotEmpty
        ? '${recentAudit.length}'
        : auditCount?.toString() ?? Ar.notAvailable;

    return DashboardMetrics(
      organizations: orgRows != null ? '${orgRows.length}' : Ar.notAvailable,
      activeUsers: access?['users_count']?.toString() ?? Ar.notAvailable,
      farms: workflow?['farms']?.toString() ?? Ar.notAvailable,
      crops: Ar.notAvailable,
      courses: workflow?['training_courses']?.toString() ?? Ar.notAvailable,
      products: Ar.notAvailable,
      campaigns: marketing?['campaigns']?.toString() ?? Ar.notAvailable,
      aiUsage: aiRequests?['today']?.toString() ?? Ar.notAvailable,
      aiUsageSubtitle: aiRequests != null
          ? 'معلق: ${aiRequests['pending'] ?? 0} · مكتمل: ${aiRequests['completed'] ?? 0}'
          : null,
      recentActivity: recentActivityValue,
      recentActivitySubtitle: auditCount != null ? 'أحداث آخر 24 ساعة: $auditCount' : null,
      systemHealth: systemHealth,
      systemHealthSubtitle: system != null ? 'الطابور: ${system['queue'] ?? Ar.notAvailable}' : null,
      alerts: notifications?['unread']?.toString() ??
          marketing?['failed_deliveries']?.toString() ??
          Ar.notAvailable,
    );
  }

  static Future<T?> _safe<T>(Future<T> Function() action) async {
    try {
      return await action();
    } catch (_) {
      return null;
    }
  }
}
