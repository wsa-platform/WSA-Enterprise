class PaginatedResponse<T> {
  PaginatedResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.perPage,
  });

  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int total;
  final int perPage;

  bool get hasMore => currentPage < lastPage;

  factory PaginatedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic> row) mapper,
  ) {
    final rows = (json['data'] as List<dynamic>? ?? [])
        .map((row) => mapper(Map<String, dynamic>.from(row as Map)))
        .toList();

    return PaginatedResponse(
      data: rows,
      currentPage: json['current_page'] as int? ?? 1,
      lastPage: json['last_page'] as int? ?? 1,
      total: json['total'] as int? ?? rows.length,
      perPage: json['per_page'] as int? ?? rows.length,
    );
  }
}
