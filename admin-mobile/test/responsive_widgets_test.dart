import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/core/layout/breakpoints.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

void main() {
  group('ResponsiveMetricGrid', () {
    test('columnCount returns 1 for mobile', () {
      expect(ResponsiveMetricGrid.columnCount(400), 1);
    });

    test('columnCount returns 2 for tablet', () {
      expect(ResponsiveMetricGrid.columnCount(700), 2);
    });

    test('columnCount returns 3-4 for desktop', () {
      expect(ResponsiveMetricGrid.columnCount(1000), 3);
      expect(ResponsiveMetricGrid.columnCount(1300), 4);
    });
  });

  group('AdminDataList', () {
    testWidgets('shows DataTable on desktop width', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          home: MediaQuery(
            data: const MediaQueryData(size: Size(1000, 800)),
            child: Scaffold(
              body: AdminDataList(
                rowCount: 2,
                columns: [
                  AdminDataColumn(
                    label: 'A',
                    cellBuilder: (_, index) => Text('row-$index'),
                  ),
                ],
              ),
            ),
          ),
        ),
      );

      expect(find.byType(DataTable), findsOneWidget);
    });

    testWidgets('shows cards on mobile width', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          home: MediaQuery(
            data: const MediaQueryData(size: Size(400, 800)),
            child: Scaffold(
              body: AdminDataList(
                rowCount: 1,
                columns: [
                  AdminDataColumn(
                    label: 'A',
                    cellBuilder: (_, index) => Text('row-$index'),
                  ),
                ],
              ),
            ),
          ),
        ),
      );

      expect(find.byType(DataTable), findsNothing);
      expect(find.text('row-0'), findsOneWidget);
    });
  });

  group('AdminBreakpoints', () {
    test('classifies widths correctly', () {
      expect(AdminBreakpoints.isMobile(500), isTrue);
      expect(AdminBreakpoints.isTablet(700), isTrue);
      expect(AdminBreakpoints.isDesktop(1000), isTrue);
    });
  });
}
