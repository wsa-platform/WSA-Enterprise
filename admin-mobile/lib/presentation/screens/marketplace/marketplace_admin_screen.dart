import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class MarketplaceAdminScreen extends StatefulWidget {
  const MarketplaceAdminScreen({super.key, required this.client});

  final ApiClient client;

  @visibleForTesting
  static Future<MarketplaceSummary> Function(ApiClient client)? debugLoader;

  @override
  State<MarketplaceAdminScreen> createState() => _MarketplaceAdminScreenState();
}

class _MarketplaceAdminScreenState extends State<MarketplaceAdminScreen> {
  bool _loading = true;
  String? _error;
  MarketplaceSummary _summary = MarketplaceSummary.empty();
  String? _statusFilter;
  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();

  @override
  void initState() {
    super.initState();
    _loadSummary();
  }

  Future<void> _loadSummary() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final loader = MarketplaceAdminScreen.debugLoader ?? MarketplaceSummary.load;
      final summary = await loader(widget.client);
      if (!mounted) return;
      setState(() {
        _summary = summary;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _moderate(Map<String, dynamic> row, String action) async {
    final id = row['id'] as int;
    try {
      switch (action) {
        case 'approve':
          await widget.client.adminModules.approveMarketListing(id);
          break;
        case 'reject':
          await widget.client.adminModules.rejectMarketListing(id);
          break;
        case 'suspend':
          await widget.client.adminModules.suspendMarketListing(id);
          break;
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));
        _listKey.currentState?.reload();
        _loadSummary();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final canApprove = widget.client.hasPermission('market.approve');
    final canSuspend = widget.client.hasPermission('market.suspend');

    return ModuleScreenLayout(
      title: Ar.navMarketplace,
      loading: _loading,
      error: _error,
      empty: false,
      onRetry: _loadSummary,
      filterChips: [
        FilterChip(label: const Text('منشور'), selected: _statusFilter == 'published', onSelected: (_) {
          setState(() => _statusFilter = _statusFilter == 'published' ? null : 'published');
          _listKey.currentState?.reload();
        }),
        FilterChip(label: const Text('بانتظار المراجعة'), selected: _statusFilter == 'pending_review', onSelected: (_) {
          setState(() => _statusFilter = _statusFilter == 'pending_review' ? null : 'pending_review');
          _listKey.currentState?.reload();
        }),
      ],
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ResponsiveMetricGrid(children: [
            MetricCard(label: Ar.marketListingsTotal, value: _summary.total, icon: Icons.storefront, tone: MetricTone.primary),
            MetricCard(label: Ar.marketPublished, value: _summary.published, icon: Icons.check, tone: MetricTone.green),
            MetricCard(label: Ar.marketPendingReview, value: _summary.pending, icon: Icons.pending_actions, tone: MetricTone.amber),
            MetricCard(label: Ar.marketContactOrders, value: _summary.contactOrders, icon: Icons.payments, tone: MetricTone.blue),
          ]),
          const SizedBox(height: 24),
          Text(Ar.marketListingsList, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          PaginatedDataList<Map<String, dynamic>>(
            key: _listKey,
            fetchPage: (page, perPage) => widget.client.adminModules.adminMarketListingsPage(
              page: page,
              perPage: perPage,
              status: _statusFilter,
            ),
            columns: [
              (row) => AdminDataColumn(label: Ar.colTitle, cellBuilder: (_, __) => Text(row['title']?.toString() ?? Ar.notAvailable)),
              (row) {
                final seller = row['seller'] as Map<String, dynamic>?;
                return AdminDataColumn(
                  label: Ar.colSeller,
                  cellBuilder: (_, __) => Text(seller?['display_name']?.toString() ?? row['seller_display_name']?.toString() ?? Ar.notAvailable),
                );
              },
              (row) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(row['status']?.toString() ?? Ar.notAvailable)),
              (row) {
                final status = row['status']?.toString() ?? '';
                return AdminDataColumn(
                  label: Ar.colActions,
                  cellBuilder: (_, __) => Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (canApprove && status == 'pending_review') ...[
                        TextButton(onPressed: () => _moderate(row, 'approve'), child: const Text(Ar.approve)),
                        TextButton(onPressed: () => _moderate(row, 'reject'), child: const Text(Ar.reject)),
                      ],
                      if (canSuspend && status == 'published')
                        TextButton(onPressed: () => _moderate(row, 'suspend'), child: const Text(Ar.suspend)),
                    ],
                  ),
                );
              },
            ],
          ),
        ],
      ),
    );
  }
}

class MarketplaceSummary {
  MarketplaceSummary({required this.total, required this.published, required this.pending, required this.contactOrders});

  final String total;
  final String published;
  final String pending;
  final String contactOrders;

  factory MarketplaceSummary.empty() => MarketplaceSummary(
        total: Ar.notAvailable,
        published: Ar.notAvailable,
        pending: Ar.notAvailable,
        contactOrders: Ar.notAvailable,
      );

  static Future<MarketplaceSummary> load(ApiClient client) async {
    final report = await client.adminModules.reportsMarketplace(days: 30);
    final summary = report['summary'] as Map<String, dynamic>? ?? {};
    return MarketplaceSummary(
      total: '${summary['listings_total'] ?? 0}',
      published: '${summary['published'] ?? 0}',
      pending: '${summary['pending_review'] ?? 0}',
      contactOrders: '${summary['contact_orders_paid'] ?? 0}',
    );
  }
}
