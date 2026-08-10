import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/models/models.dart';

class NotificationsApi {
  NotificationsApi(this.http);

  final HttpClient http;

  Future<List<ApiNotification>> list({int page = 1}) async {
    final rows = await http.getList('/notifications?page=$page');
    return rows.map((row) => notificationFromJson(Map<String, dynamic>.from(row as Map))).toList();
  }

  Future<ApiNotification> markRead(int notificationId) async {
    final payload = await http.postJson('/notifications/$notificationId/read', {});
    return notificationFromJson(payload);
  }
}
