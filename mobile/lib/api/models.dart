class ApiModels {
  const userFromJson = _userFromJson;
  const organizationFromJson = _organizationFromJson;
  const dashboardFromJson = _dashboardFromJson;
  const aiRequestFromJson = _aiRequestFromJson;
}

class ApiUser {
  const ApiUser({required this.id, required this.name, required this.email});
  final int id;
  final String name;
  final String email;
}

class ApiOrganization {
  const ApiOrganization({required this.id, required this.name, required this.slug, this.role});
  final int id;
  final String name;
  final String slug;
  final String? role;
}

class ApiDashboard {
  const ApiDashboard({required this.organization, required this.metrics});
  final Map<String, dynamic> organization;
  final Map<String, dynamic> metrics;
}

class ApiAiRequest {
  const ApiAiRequest({
    required this.id,
    required this.status,
    required this.requestType,
    this.output,
    this.errorMessage,
  });
  final int id;
  final String status;
  final String requestType;
  final Map<String, dynamic>? output;
  final String? errorMessage;
}

ApiUser _userFromJson(Map<String, dynamic> json) => ApiUser(
  id: json['id'] as int,
  name: json['name'] as String,
  email: json['email'] as String,
);

ApiOrganization _organizationFromJson(Map<String, dynamic> json) => ApiOrganization(
  id: json['id'] as int,
  name: json['name'] as String,
  slug: json['slug'] as String,
  role: json['role'] as String?,
);

ApiDashboard _dashboardFromJson(Map<String, dynamic> json) => ApiDashboard(
  organization: Map<String, dynamic>.from(json['organization'] as Map),
  metrics: Map<String, dynamic>.from(json['metrics'] as Map),
);

ApiAiRequest _aiRequestFromJson(Map<String, dynamic> json) => ApiAiRequest(
  id: json['id'] as int,
  status: json['status'] as String,
  requestType: json['request_type'] as String,
  output: json['output'] == null ? null : Map<String, dynamic>.from(json['output'] as Map),
  errorMessage: json['error_message'] as String?,
);
