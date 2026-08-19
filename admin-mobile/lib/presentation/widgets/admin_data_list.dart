import 'package:flutter/material.dart';
import 'package:wsa_admin/core/layout/breakpoints.dart';

class AdminDataColumn {
  const AdminDataColumn({
    required this.label,
    required this.cellBuilder,
    this.flex = 1,
  });

  final String label;
  final Widget Function(BuildContext context, int index) cellBuilder;
  final int flex;
}

/// Table on desktop/tablet, card list on mobile.
class AdminDataList extends StatelessWidget {
  const AdminDataList({
    super.key,
    required this.columns,
    required this.rowCount,
    this.cardBuilder,
    this.emptyMessage,
  });

  final List<AdminDataColumn> columns;
  final int rowCount;
  final Widget Function(BuildContext context, int index)? cardBuilder;
  final String? emptyMessage;

  @override
  Widget build(BuildContext context) {
    if (rowCount == 0 && emptyMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(emptyMessage!, textAlign: TextAlign.center),
        ),
      );
    }

    final width = MediaQuery.sizeOf(context).width;
    final useTable = !AdminBreakpoints.isMobile(width);

    if (useTable) {
      return _DataTableView(columns: columns, rowCount: rowCount);
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: rowCount,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        if (cardBuilder != null) {
          return cardBuilder!(context, index);
        }
        return _DefaultMobileCard(columns: columns, index: index);
      },
    );
  }
}

class _DataTableView extends StatelessWidget {
  const _DataTableView({
    required this.columns,
    required this.rowCount,
  });

  final List<AdminDataColumn> columns;
  final int rowCount;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: ConstrainedBox(
          constraints: BoxConstraints(minWidth: MediaQuery.sizeOf(context).width - 48),
          child: DataTable(
            headingRowColor: WidgetStatePropertyAll(
              Theme.of(context).colorScheme.surfaceContainerHighest,
            ),
            columns: [
              for (final column in columns)
                DataColumn(label: Text(column.label)),
            ],
            rows: [
              for (var index = 0; index < rowCount; index++)
                DataRow(
                  cells: [
                    for (final column in columns)
                      DataCell(column.cellBuilder(context, index)),
                  ],
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DefaultMobileCard extends StatelessWidget {
  const _DefaultMobileCard({
    required this.columns,
    required this.index,
  });

  final List<AdminDataColumn> columns;
  final int index;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (var i = 0; i < columns.length; i++) ...[
              if (i > 0) const SizedBox(height: 8),
              Text(
                columns[i].label,
                style: Theme.of(context).textTheme.labelSmall?.copyWith(color: Colors.black54),
              ),
              const SizedBox(height: 2),
              columns[i].cellBuilder(context, index),
            ],
          ],
        ),
      ),
    );
  }
}
