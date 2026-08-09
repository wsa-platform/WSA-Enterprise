import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';

class OrgSwitcher extends StatelessWidget {
  const OrgSwitcher({
    super.key,
    required this.client,
    required this.onChanged,
  });

  final ApiClient client;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) {
    final organizations = client.organizations;
    if (organizations.isEmpty) return const SizedBox.shrink();

    return DropdownButtonHideUnderline(
      child: DropdownButton<int>(
        value: client.organizationId ?? organizations.first['id'] as int?,
        items: organizations
            .map(
              (organization) => DropdownMenuItem<int>(
                value: organization['id'] as int,
                child: Text(organization['name']?.toString() ?? 'Organization'),
              ),
            )
            .toList(),
        onChanged: (value) async {
          if (value == null) return;
          await client.setOrganizationId(value);
          onChanged();
        },
      ),
    );
  }
}
