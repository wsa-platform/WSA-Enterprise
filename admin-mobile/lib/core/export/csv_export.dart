import 'dart:convert';

import 'package:universal_html/html.dart' as html;
import 'package:wsa_admin/data/api/admin_modules_api.dart';

/// Browser CSV download with UTF-8 BOM for Arabic Excel compatibility.
Future<void> downloadCsvExport({
  required AdminModulesApi modules,
  required int days,
  void Function(bool loading)? onLoading,
  void Function(String message)? onError,
  void Function()? onSuccess,
}) async {
  onLoading?.call(true);
  try {
    final csv = await modules.reportsExport(days: days);
    final bom = '\uFEFF';
    final blob = html.Blob([bom + csv], 'text/csv;charset=utf-8');
    final url = html.Url.createObjectUrlFromBlob(blob);
    html.AnchorElement(href: url)
      ..setAttribute('download', 'wsa-report-${DateTime.now().toIso8601String().substring(0, 10)}-${days}d.csv')
      ..click();
    html.Url.revokeObjectUrl(url);
    onSuccess?.call();
  } catch (error) {
    onError?.call(error.toString());
  } finally {
    onLoading?.call(false);
  }
}

String escapeCsvField(String value) {
  if (value.contains(',') || value.contains('"') || value.contains('\n')) {
    return '"${value.replaceAll('"', '""')}"';
  }
  return value;
}

List<int> csvBytesWithBom(String csv) => utf8.encode('\uFEFF$csv');
