import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';

/// Line chart widget for reports time-series data.
class ChartWidget extends StatelessWidget {
  const ChartWidget({
    super.key,
    required this.title,
    required this.series,
    this.loading = false,
    this.error,
    this.emptyMessage = Ar.emptyData,
    this.lineColor,
  });

  final String title;
  final List<Map<String, dynamic>> series;
  final bool loading;
  final String? error;
  final String emptyMessage;
  final Color? lineColor;

  @override
  Widget build(BuildContext context) {
    final color = lineColor ?? Theme.of(context).colorScheme.primary;

    if (loading) {
      return _ChartCard(title: title, child: const Center(child: CircularProgressIndicator()));
    }

    if (error != null) {
      return _ChartCard(title: title, child: Center(child: Text(error!, textAlign: TextAlign.center)));
    }

    if (series.isEmpty || series.every((point) => (point['count'] as num? ?? 0) == 0)) {
      return _ChartCard(title: title, child: Center(child: Text(emptyMessage)));
    }

    final spots = <FlSpot>[];
    for (var i = 0; i < series.length; i++) {
      spots.add(FlSpot(i.toDouble(), (series[i]['count'] as num? ?? 0).toDouble()));
    }

    final maxY = spots.map((s) => s.y).reduce((a, b) => a > b ? a : b);

    return _ChartCard(
      title: title,
      child: SizedBox(
        height: 200,
        child: LineChart(
          LineChartData(
            minY: 0,
            maxY: maxY == 0 ? 1 : maxY * 1.2,
            gridData: const FlGridData(show: true, drawVerticalLine: false),
            titlesData: FlTitlesData(
              leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: true, reservedSize: 32)),
              bottomTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 28,
                  interval: (series.length / 4).clamp(1, 7).toDouble(),
                  getTitlesWidget: (value, meta) {
                    final index = value.toInt();
                    if (index < 0 || index >= series.length) return const SizedBox.shrink();
                    final date = series[index]['date']?.toString() ?? '';
                    final label = date.length >= 5 ? date.substring(5) : date;
                    return Text(label, style: const TextStyle(fontSize: 10));
                  },
                ),
              ),
              topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
              rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            ),
            borderData: FlBorderData(show: false),
            lineBarsData: [
              LineChartBarData(
                spots: spots,
                isCurved: true,
                color: color,
                barWidth: 3,
                dotData: const FlDotData(show: false),
                belowBarData: BarAreaData(show: true, color: color.withValues(alpha: 0.12)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ChartCard extends StatelessWidget {
  const _ChartCard({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 12),
            child,
          ],
        ),
      ),
    );
  }
}
