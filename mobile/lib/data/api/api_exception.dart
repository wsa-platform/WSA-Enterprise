class ApiException implements Exception {
  ApiException(
    this.message, {
    this.statusCode,
    this.errors,
    this.isNetworkFailure = false,
    this.payload,
  });

  final String message;
  final int? statusCode;
  final Map<String, List<String>>? errors;
  final bool isNetworkFailure;
  final Map<String, dynamic>? payload;

  factory ApiException.network([String? message]) {
    return ApiException(
      message ?? 'تعذر الاتصال بالخادم. تحقق من الشبكة ثم أعد المحاولة.',
      isNetworkFailure: true,
    );
  }

  @override
  String toString() {
    if (isNetworkFailure) {
      return message.isNotEmpty
          ? message
          : 'تعذر الاتصال بالخادم. تحقق من الشبكة ثم أعد المحاولة.';
    }
    if (statusCode == 403) {
      return message.isNotEmpty
          ? message
          : 'You do not have permission to perform this action.';
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
