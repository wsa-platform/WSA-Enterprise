import 'package:flutter/material.dart';

import 'package:wsa_admin/core/export/csv_export.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/chart_widget.dart';

import 'package:wsa_admin/presentation/widgets/metric_card.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';



class ReportsScreen extends StatefulWidget {

  const ReportsScreen({super.key, required this.client});



  final ApiClient client;



  @override

  State<ReportsScreen> createState() => _ReportsScreenState();

}



class _ReportsScreenState extends State<ReportsScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _overview = {};
  Map<String, dynamic> _traffic = {};
  int _days = 7;
  bool _exporting = false;



  @override

  void initState() {

    super.initState();

    _load();

  }



  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final overview = await widget.client.adminModules.reportsOverview(days: _days);
      Map<String, dynamic> traffic = {};
      try { traffic = await widget.client.adminModules.analyticsTraffic(days: _days); } catch (_) {}
      if (!mounted) return;
      setState(() { _overview = overview; _traffic = traffic; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }



  Future<void> _export() async {

    setState(() => _exporting = true);

    await downloadCsvExport(

      modules: widget.client.adminModules,

      days: _days,

      onLoading: (_) {},

      onError: (message) {

        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${Ar.exportFailed}: $message')));

      },

      onSuccess: () {

        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.exported)));

      },

    );

    if (mounted) setState(() => _exporting = false);

  }



  String _section(String key, String field) {

    final section = _overview[key] as Map<String, dynamic>?;

    return section?[field]?.toString() ?? Ar.notAvailable;

  }



  List<Map<String, dynamic>> _trafficChartSeries(String key) {
    final charts = _traffic['charts'] as Map<String, dynamic>?;
    final series = charts?[key] as List<dynamic>? ?? _traffic['traffic'] as List<dynamic>? ?? [];
    return series.map((row) => Map<String, dynamic>.from(row as Map)).toList();
  }

  List<Map<String, dynamic>> _chartSeries(String key) {

    final charts = _overview['charts'] as Map<String, dynamic>?;

    final series = charts?[key] as List<dynamic>? ?? [];

    return series.map((row) => Map<String, dynamic>.from(row as Map)).toList();

  }



  @override

  Widget build(BuildContext context) {

    return ModuleScreenLayout(

      title: Ar.navReports,

      loading: _loading,

      error: _error,

      empty: _overview.isEmpty && !_loading && _error == null,

      onRetry: _load,

      filterChips: [
        ChoiceChip(label: const Text(Ar.reportsToday), selected: _days == 1, onSelected: (_) { _days = 1; _load(); }),
        ChoiceChip(label: const Text(Ar.reportsLast7Days), selected: _days == 7, onSelected: (_) { _days = 7; _load(); }),
        ChoiceChip(label: const Text(Ar.reportsLast30Days), selected: _days == 30, onSelected: (_) { _days = 30; _load(); }),
      ],

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          Row(

            children: [

              Expanded(child: Text(Ar.reportsOverview, style: Theme.of(context).textTheme.titleMedium)),

              FilledButton.icon(

                onPressed: _exporting ? null : _export,

                icon: _exporting

                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))

                    : const Icon(Icons.download),

                label: Text(_exporting ? Ar.exporting : Ar.exportCsv),

              ),

            ],

          ),

          const SizedBox(height: 16),
          Text(Ar.reportsTraffic, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: 8),
          ResponsiveMetricGrid(children: [
            MetricCard(label: Ar.reportsVisitors, value: '${_traffic['sessions_total'] ?? 0}', icon: Icons.people_outline, tone: MetricTone.blue),
            MetricCard(label: Ar.reportsPageViews, value: '${_traffic['page_views_total'] ?? 0}', icon: Icons.visibility, tone: MetricTone.green),
            MetricCard(label: Ar.reportsCategoryUsage, value: '${_traffic['events_total'] ?? 0}', icon: Icons.analytics, tone: MetricTone.amber),
          ]),
          const SizedBox(height: 12),
          ChartWidget(title: Ar.chartTraffic, series: _trafficChartSeries('page_views_over_time'), loading: _loading, error: _error),
          const SizedBox(height: 20),
          Text(Ar.reportsCommerce, style: Theme.of(context).textTheme.titleSmall),

          const SizedBox(height: 8),

          ResponsiveMetricGrid(children: [

            MetricCard(label: 'إجمالي المبيعات', value: _section('commerce', 'sales_total'), icon: Icons.point_of_sale, tone: MetricTone.green),

            MetricCard(label: 'إجمالي الفواتير', value: _section('commerce', 'invoice_total'), icon: Icons.receipt, tone: MetricTone.blue),

            MetricCard(label: 'طلبات مفتوحة', value: _section('commerce', 'open_orders'), icon: Icons.shopping_cart, tone: MetricTone.amber),

          ]),

          const SizedBox(height: 20),

          ChartWidget(title: Ar.chartUsers, series: _chartSeries('users_over_time'), loading: _loading, error: _error),

          const SizedBox(height: 12),

          ChartWidget(title: Ar.chartProducts, series: _chartSeries('products_over_time'), loading: _loading, error: _error),

          const SizedBox(height: 12),

          ChartWidget(title: Ar.chartAudit, series: _chartSeries('audit_over_time'), loading: _loading, error: _error),

          const SizedBox(height: 12),

          ChartWidget(title: Ar.chartCampaigns, series: _chartSeries('campaigns_over_time'), loading: _loading, error: _error),

          const SizedBox(height: 20),

          Text(Ar.reportsAgriculture, style: Theme.of(context).textTheme.titleSmall),

          const SizedBox(height: 8),

          ResponsiveMetricGrid(children: [

            MetricCard(label: Ar.metricFarms, value: _section('agriculture', 'farms_total'), icon: Icons.agriculture, tone: MetricTone.green),

            MetricCard(label: Ar.metricCrops, value: _section('agriculture', 'crop_types_total'), icon: Icons.grass, tone: MetricTone.primary),

          ]),

          const SizedBox(height: 20),

          Text(Ar.reportsMarketing, style: Theme.of(context).textTheme.titleSmall),

          const SizedBox(height: 8),

          ResponsiveMetricGrid(children: [

            MetricCard(label: Ar.metricCampaigns, value: _section('marketing', 'campaigns_total'), icon: Icons.campaign, tone: MetricTone.primary),

            MetricCard(label: 'حملات نشطة', value: _section('marketing', 'campaigns_active'), icon: Icons.trending_up, tone: MetricTone.green),

          ]),

          const SizedBox(height: 20),

          Text(Ar.reportsRecruitment, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: 8),
          FutureBuilder<Map<String, dynamic>>(
            future: widget.client.modules.reportsRecruitment(days: _days),
            builder: (context, snapshot) {
              final summary = snapshot.data?['summary'] as Map<String, dynamic>? ?? {};
              return ResponsiveMetricGrid(children: [
                MetricCard(label: Ar.jobSeekersTotal, value: '${summary['total_profiles'] ?? 0}', icon: Icons.people, tone: MetricTone.blue),
                MetricCard(label: Ar.jobSeekersHired, value: '${summary['hired_total'] ?? 0}', icon: Icons.work, tone: MetricTone.green),
              ]);
            },
          ),

          const SizedBox(height: 20),

          Text(Ar.reportsMarketplace, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: 8),
          FutureBuilder<Map<String, dynamic>>(
            future: widget.client.adminModules.reportsMarketplace(days: _days),
            builder: (context, snapshot) {
              final summary = snapshot.data?['summary'] as Map<String, dynamic>? ?? {};
              return ResponsiveMetricGrid(children: [
                MetricCard(label: Ar.marketListingsTotal, value: '${summary['listings_total'] ?? 0}', icon: Icons.storefront, tone: MetricTone.primary),
                MetricCard(label: Ar.marketPublished, value: '${summary['published'] ?? 0}', icon: Icons.check, tone: MetricTone.green),
                MetricCard(label: Ar.marketContactOrders, value: '${summary['contact_orders_paid'] ?? 0}', icon: Icons.payments, tone: MetricTone.amber),
              ]);
            },
          ),

          const SizedBox(height: 20),

          Text(Ar.reportsAccess, style: Theme.of(context).textTheme.titleSmall),

          const SizedBox(height: 8),

          ResponsiveMetricGrid(children: [

            MetricCard(label: Ar.usersTotal, value: _section('access', 'users_total'), icon: Icons.people, tone: MetricTone.blue),

            MetricCard(label: Ar.auditOverview, value: _section('access', 'audit_events'), icon: Icons.receipt_long, tone: MetricTone.amber),

          ]),

        ],

      ),

    );

  }

}

