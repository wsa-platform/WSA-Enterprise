import 'package:flutter/material.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

typedef PaginatedFetch<T> = Future<PaginatedResponse<T>> Function(int page, int perPage);

/// Reusable paginated data table with server-side pagination controls.
class PaginatedDataList<T> extends StatefulWidget {
  const PaginatedDataList({
    super.key,
    required this.columns,
    required this.fetchPage,
    this.perPage = 25,
    this.emptyMessage = Ar.emptyData,
    this.itemBuilder,
  });

  final List<AdminDataColumn Function(T item)> columns;
  final PaginatedFetch<T> fetchPage;
  final int perPage;
  final String emptyMessage;
  final Widget Function(BuildContext context, T item)? itemBuilder;

  @override
  State<PaginatedDataList<T>> createState() => PaginatedDataListState<T>();
}

class PaginatedDataListState<T> extends State<PaginatedDataList<T>> {
  bool _loading = true;
  String? _error;
  int _page = 1;
  int _lastPage = 1;
  int _total = 0;
  List<T> _items = [];

  @override
  void initState() {
    super.initState();
    reload();
  }

  Future<void> reload() => _load(page: 1);

  Future<void> _load({int? page}) async {
    setState(() {
      _loading = true;
      _error = null;
      if (page != null) _page = page;
    });

    try {
      final response = await widget.fetchPage(_page, widget.perPage);
      if (!mounted) return;
      setState(() {
        _items = response.data;
        _page = response.currentPage;
        _lastPage = response.lastPage;
        _total = response.total;
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
    if (_loading) {
      return const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()));
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(onPressed: () => _load(), child: const Text(Ar.retry)),
          ],
        ),
      );
    }

    final labeledColumns = <AdminDataColumn>[];
    for (var i = 0; i < widget.columns.length; i++) {
      if (_items.isEmpty) {
        labeledColumns.add(AdminDataColumn(label: '', cellBuilder: (_, __) => const SizedBox.shrink()));
        continue;
      }
      final sample = widget.columns[i](_items.first);
      labeledColumns.add(AdminDataColumn(
        label: sample.label,
        flex: sample.flex,
        cellBuilder: (_, index) => widget.columns[i](_items[index]).cellBuilder(context, index),
      ));
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        AdminDataList(
          rowCount: _items.length,
          emptyMessage: widget.emptyMessage,
          cardBuilder: widget.itemBuilder == null
              ? null
              : (ctx, index) => widget.itemBuilder!(ctx, _items[index]),
          columns: labeledColumns,
        ),
        if (_lastPage > 1) ...[
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('صفحة $_page من $_lastPage ($_total)'),
              Row(
                children: [
                  IconButton(
                    onPressed: _page > 1 ? () => _load(page: _page - 1) : null,
                    icon: const Icon(Icons.chevron_right),
                    tooltip: 'السابق',
                  ),
                  IconButton(
                    onPressed: _page < _lastPage ? () => _load(page: _page + 1) : null,
                    icon: const Icon(Icons.chevron_left),
                    tooltip: 'التالي',
                  ),
                ],
              ),
            ],
          ),
        ],
      ],
    );
  }
}
