# PostgreSQL data model

The Phase 2 schema is designed around organization-scoped work management.

    users --< organization_user >-- organizations --< projects --< tasks
      \---------------------------------------------------------------/
                       task assignee / project manager

| Table | Purpose | Important constraints |
| --- | --- | --- |
| users | User identities | unique email |
| organizations | Tenant/workspace boundary | unique slug |
| organization_user | Membership and role | unique organization/user pair |
| projects | Organization-owned initiatives | unique organization/code pair |
| tasks | Project work items | indexed status, priority, and due date |
| personal_access_tokens | Sanctum API tokens | unique token hash |

All tenant-owned reads are scoped through the authenticated user's organization membership. Foreign keys cascade from organizations to memberships/projects and from projects to tasks; optional manager and assignee references are set to null when a user is removed.
