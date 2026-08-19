import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class JobSeekersScreen extends StatefulWidget {
  const JobSeekersScreen({super.key, required this.client});

  final ApiClient client;

  @visibleForTesting
  static Future<JobSeekersSummary> Function(ApiClient client)? debugLoader;

  @visibleForTesting
  static PaginatedFetch<Map<String, dynamic>>? debugListFetch;

  @visibleForTesting
  static Future<Map<String, dynamic>> Function(int id)? debugProfileLoader;

  @visibleForTesting
  static Future<List<Map<String, dynamic>>> Function(int id)? debugNotesLoader;

  @visibleForTesting
  static Future<List<Map<String, dynamic>>> Function(int id)? debugHistoryLoader;

  @override
  State<JobSeekersScreen> createState() => _JobSeekersScreenState();
}

class _JobSeekersScreenState extends State<JobSeekersScreen> {
  bool _loading = true;
  String? _error;
  JobSeekersSummary _summary = JobSeekersSummary.empty();
  String _search = '';
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
      final loader = JobSeekersScreen.debugLoader ?? JobSeekersSummary.load;
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

  Future<void> _updateStatus(Map<String, dynamic> row) async {
    if (!widget.client.hasPermission('jobs.status')) return;
    final id = row['id'] as int;
    final status = await showDialog<String>(
      context: context,
      builder: (ctx) => SimpleDialog(
        title: const Text(Ar.jobSeekerUpdateStatus),
        children: JobSeekerStatuses.all.map((s) {
          return SimpleDialogOption(
            onPressed: () => Navigator.pop(ctx, s.key),
            child: Text(s.label),
          );
        }).toList(),
      ),
    );
    if (status == null) return;
    try {
      await widget.client.modules.updateJobSeekerStatus(id, status);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));
        _listKey.currentState?.reload();
        _loadSummary();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _openProfile(Map<String, dynamic> row) async {
    final id = row['id'] as int;
    Map<String, dynamic> profile = Map<String, dynamic>.from(row);
    List<Map<String, dynamic>> notes = const [];
    List<Map<String, dynamic>> history = const [];
    try {
      profile = JobSeekersScreen.debugProfileLoader != null
          ? await JobSeekersScreen.debugProfileLoader!(id)
          : await widget.client.modules.jobSeekerShow(id);
      if (widget.client.hasPermission('jobs.notes')) {
        if (JobSeekersScreen.debugNotesLoader != null) {
          notes = await JobSeekersScreen.debugNotesLoader!(id);
        } else {
          notes = (await widget.client.modules.jobSeekerNotesPage(id)).data;
        }
      }
      if (JobSeekersScreen.debugHistoryLoader != null) {
        history = await JobSeekersScreen.debugHistoryLoader!(id);
      } else {
        history = (await widget.client.modules.jobSeekerHistoryPage(id)).data;
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
    if (!mounted) return;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => JobSeekerDetailSheet(
        client: widget.client,
        profile: profile,
        notes: notes,
        history: history,
        onAddNote: widget.client.hasPermission('jobs.notes')
            ? (body) => widget.client.modules.addJobSeekerNote(id, body)
            : null,
        onUpdateStatus: widget.client.hasPermission('jobs.status') ? () => _updateStatus(profile) : null,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final canManageStatus = widget.client.hasPermission('jobs.status');

    return ModuleScreenLayout(
      title: Ar.navJobSeekers,
      loading: _loading,
      error: _error,
      empty: false,
      onRetry: _loadSummary,
      searchHint: Ar.searchJobSeekers,
      onSearchChanged: (value) {
        setState(() => _search = value);
        _listKey.currentState?.reload();
      },
      filterChips: [
        for (final status in JobSeekerStatuses.all)
          FilterChip(
            label: Text(status.label),
            selected: _statusFilter == status.key,
            onSelected: (_) {
              setState(() => _statusFilter = _statusFilter == status.key ? null : status.key);
              _listKey.currentState?.reload();
            },
          ),
      ],
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ResponsiveMetricGrid(children: [
            MetricCard(label: Ar.jobSeekersTotal, value: _summary.total, icon: Icons.people, tone: MetricTone.primary),
            MetricCard(label: Ar.jobSeekersActive, value: _summary.active, icon: Icons.check_circle_outline, tone: MetricTone.green),
            MetricCard(label: Ar.jobSeekersUnderReview, value: _summary.underReview, icon: Icons.hourglass_top, tone: MetricTone.amber),
            MetricCard(label: Ar.jobSeekersHired, value: _summary.hired, icon: Icons.work_outline, tone: MetricTone.blue),
          ]),
          const SizedBox(height: 24),
          Text(Ar.jobSeekersList, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          PaginatedDataList<Map<String, dynamic>>(
            key: _listKey,
            fetchPage: (page, perPage) {
              if (JobSeekersScreen.debugListFetch != null) {
                return JobSeekersScreen.debugListFetch!(page, perPage);
              }
              return widget.client.modules.jobSeekersPage(
                page: page,
                perPage: perPage,
                search: _search.trim().isEmpty ? null : _search.trim(),
                status: _statusFilter,
              );
            },
            columns: [
              (row) => AdminDataColumn(label: Ar.colName, cellBuilder: (_, __) => Text(row['full_name']?.toString() ?? Ar.notAvailable)),
              (row) => AdminDataColumn(label: Ar.colSpecialization, cellBuilder: (_, __) => Text(row['specialization']?.toString() ?? Ar.notAvailable)),
              (row) => AdminDataColumn(label: Ar.colCity, cellBuilder: (_, __) => Text(row['city']?.toString() ?? Ar.notAvailable)),
              (row) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(JobSeekerStatuses.labelFor(row['recruitment_status']?.toString()))),
              (row) => AdminDataColumn(
                    label: Ar.jobSeekerCompleteness,
                    cellBuilder: (_, __) => Text('${row['completeness_percent'] ?? Ar.notAvailable}'),
                  ),
              (row) => AdminDataColumn(
                    label: Ar.colActions,
                    cellBuilder: (_, __) => Wrap(
                      spacing: 8,
                      children: [
                        TextButton(onPressed: () => _openProfile(row), child: const Text(Ar.jobSeekerViewProfile)),
                        if (canManageStatus)
                          TextButton(onPressed: () => _updateStatus(row), child: const Text(Ar.jobSeekerUpdateStatus)),
                      ],
                    ),
                  ),
            ],
          ),
        ],
      ),
    );
  }
}

class JobSeekerDetailSheet extends StatelessWidget {
  const JobSeekerDetailSheet({
    super.key,
    required this.client,
    required this.profile,
    required this.notes,
    required this.history,
    this.onAddNote,
    this.onUpdateStatus,
  });

  final ApiClient client;
  final Map<String, dynamic> profile;
  final List<Map<String, dynamic>> notes;
  final List<Map<String, dynamic>> history;
  final Future<Map<String, dynamic>> Function(String body)? onAddNote;
  final Future<void> Function()? onUpdateStatus;

  bool get _canViewPrivate => client.hasPermission('jobs.private_data');

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(profile['full_name']?.toString() ?? Ar.navJobSeekers, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              Text('${Ar.jobSeekerCompleteness}: ${profile['completeness_percent'] ?? Ar.notAvailable}'),
              Text('${Ar.colSpecialization}: ${profile['specialization'] ?? Ar.notAvailable}'),
              Text('${Ar.colCity}: ${profile['city'] ?? Ar.notAvailable}'),
              Text('${Ar.colStatus}: ${JobSeekerStatuses.labelFor(profile['recruitment_status']?.toString())}'),
              if (_canViewPrivate && profile.containsKey('email')) Text('${Ar.colEmail}: ${profile['email']}'),
              if (_canViewPrivate && profile.containsKey('phone')) Text('${Ar.phoneNumber}: ${profile['phone']}'),
              if (_canViewPrivate && profile.containsKey('cv_path')) Text('${Ar.jobSeekerCvPath}: ${profile['cv_path']}'),
              if (_canViewPrivate && profile.containsKey('desired_salary'))
                Text('${Ar.jobSeekerSalary}: ${profile['desired_salary']} ${profile['salary_currency'] ?? ''}'),
              if (onUpdateStatus != null) ...[
                const SizedBox(height: 12),
                FilledButton(onPressed: onUpdateStatus, child: const Text(Ar.jobSeekerUpdateStatus)),
              ],
              const SizedBox(height: 16),
              Text(Ar.jobSeekerHistory, style: Theme.of(context).textTheme.titleMedium),
              if (history.isEmpty) const Text(Ar.emptyData),
              for (final item in history)
                ListTile(
                  dense: true,
                  title: Text(JobSeekerStatuses.labelFor(item['status']?.toString())),
                  subtitle: item.containsKey('notes') ? Text(item['notes']?.toString() ?? '') : null,
                ),
              if (client.hasPermission('jobs.notes')) ...[
                const SizedBox(height: 8),
                Text(Ar.jobSeekerNotes, style: Theme.of(context).textTheme.titleMedium),
                if (notes.isEmpty) const Text(Ar.emptyData),
                for (final note in notes)
                  ListTile(
                    dense: true,
                    title: Text(note['body']?.toString() ?? ''),
                    subtitle: Text(note['author'] is Map ? (note['author']['name']?.toString() ?? '') : ''),
                  ),
                if (onAddNote != null)
                  TextButton(
                    onPressed: () async {
                      final data = await SimpleFormDialog.show(
                        context,
                        title: Ar.jobSeekerAddNote,
                        fields: [const FormFieldDef(key: 'body', label: Ar.jobSeekerNoteBody)],
                      );
                      final body = data?['body']?.trim();
                      if (body == null || body.isEmpty) return;
                      await onAddNote!(body);
                      if (context.mounted) Navigator.pop(context);
                    },
                    child: const Text(Ar.jobSeekerAddNote),
                  ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class JobSeekersSummary {
  JobSeekersSummary({required this.total, required this.active, required this.underReview, required this.hired});

  final String total;
  final String active;
  final String underReview;
  final String hired;

  factory JobSeekersSummary.empty() => JobSeekersSummary(
        total: Ar.notAvailable,
        active: Ar.notAvailable,
        underReview: Ar.notAvailable,
        hired: Ar.notAvailable,
      );

  static Future<JobSeekersSummary> load(ApiClient client) async {
    final report = await client.modules.reportsRecruitment(days: 30);
    final summary = report['summary'] as Map<String, dynamic>? ?? {};
    final byStatus = report['by_status'] as Map<String, dynamic>? ?? {};
    return JobSeekersSummary(
      total: '${summary['total_profiles'] ?? 0}',
      active: '${summary['active_profiles'] ?? 0}',
      underReview: '${byStatus['under_review'] ?? 0}',
      hired: '${summary['hired_total'] ?? 0}',
    );
  }
}

class JobSeekerStatusOption {
  const JobSeekerStatusOption(this.key, this.label);
  final String key;
  final String label;
}

abstract final class JobSeekerStatuses {
  static const all = [
    JobSeekerStatusOption('new', 'جديد'),
    JobSeekerStatusOption('under_review', 'قيد المراجعة'),
    JobSeekerStatusOption('qualified', 'مؤهل'),
    JobSeekerStatusOption('interview', 'مقابلة'),
    JobSeekerStatusOption('accepted', 'مقبول'),
    JobSeekerStatusOption('rejected', 'مرفوض'),
    JobSeekerStatusOption('hired', 'تم التوظيف'),
  ];

  static String labelFor(String? key) {
    if (key == null) return Ar.notAvailable;
    return all.firstWhere((s) => s.key == key, orElse: () => JobSeekerStatusOption(key, key)).label;
  }
}
