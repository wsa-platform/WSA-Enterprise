import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';

class OrgSwitcher extends StatelessWidget {
  const OrgSwitcher({
    super.key,
    required this.client,
    required this.onChanged,
  });

  final ApiClient client;
  final Future<void> Function() onChanged;

  @override
  Widget build(BuildContext context) {
    final organizations = client.organizations;
    if (organizations.isEmpty) {
      return const SizedBox.shrink();
    }

    return DropdownButtonHideUnderline(
      child: DropdownButton<int>(
        value: client.organizationId ?? organizations.first['id'] as int?,
        hint: const Text(Ar.selectOrganization),
        items: organizations
            .map(
              (organization) => DropdownMenuItem<int>(
                value: organization['id'] as int,
                child: Text(organization['name']?.toString() ?? Ar.selectOrganization),
              ),
            )
            .toList(),
        onChanged: (value) async {
          if (value == null) return;
          await client.setOrganizationId(value);
          await onChanged();
        },
      ),
    );
  }
}
