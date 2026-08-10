class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, List<String>>? errors;

  @override
  String toString() {
    if (statusCode == 403) {
      return message.isNotEmpty ? message : 'You do not have permission to perform this action.';
    }
    if (errors != null && errors!.isNotEmpty) {
      final details = errors!.entries
          .expand((entry) => entry.value.map((value) => '${entry.key}: $value'))
          .join(' · ');
      return details.isEmpty ? message : details;
    }
    return message;
  }
}
