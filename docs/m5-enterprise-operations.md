# Phase 11 M5 — Enterprise Operations & Billing Foundation

**Last updated:** 2026-08-10  
**Status:** Implemented on branch `phase-11-m5-enterprise-operations`  
**Payment providers:** Intentionally **not** connected in M5

## Objective

M5 adds a production-grade billing and operations foundation without live payment processing:

- Organization-scoped billing domain (plans, subscriptions, invoices, payments)
- Plan catalog with feature limits
- Subscription lifecycle (assign, cancel, reactivate)
- Billing-aware feature gating layered on RBAC
- Usage aggregation via existing AI `UsageRecord` infrastructure
- Provider-neutral billing adapter (`MockBillingProvider`)
- Enterprise operational settings API + dashboard UI
- Comprehensive automated verification

## Architecture

```
Organization
  └── BillingAccount
        └── Subscription → Plan → PlanFeature[]
              └── BillingInvoice → BillingPayment[]
```

**Policy stack (all must pass):**

1. Tenant isolation (`X-Organization-Id` + global scopes)
2. RBAC (`billing.view`, `billing.manage`)
3. Subscription/plan state (`EntitlementService`)
4. Quota/usage (`AiQuotaService` + plan limits)

## Configuration

| Variable | Default | Purpose |
|----------|---------|---------|
| `BILLING_ENABLED` | `false` | Enable subscription + entitlement enforcement |
| `BILLING_PROVIDER` | `mock` | Provider adapter selection |
| `DEFAULT_PLAN_SLUG` | `free` | Default plan for new subscriptions |

When `BILLING_ENABLED=false`, existing M1–M4 behavior is preserved.

## Database (migration `2026_08_10_140000_add_phase11_billing_foundation`)

| Table | Scope | Purpose |
|-------|-------|---------|
| `plans` | Global catalog | Plan definitions |
| `plan_features` | Global catalog | Limits and feature flags per plan |
| `billing_accounts` | Organization | Provider-neutral billing account |
| `subscriptions` | Organization | Active/historical subscription state |
| `billing_invoices` | Organization | Invoice records |
| `billing_payments` | Organization | Payment status records |

## API Endpoints

All require `auth:sanctum` + organization context.

| Method | Path | Permission | Purpose |
|--------|------|------------|---------|
| GET | `/billing/plans` | `billing.view` | Active plan catalog |
| GET | `/billing/subscription` | `billing.view` | Current subscription + entitlements |
| GET | `/billing/usage` | `billing.view` | Period usage summary |
| GET | `/billing/invoices` | `billing.view` | Paginated invoices |
| POST | `/billing/subscription/plan` | `billing.manage` | Assign/change plan |
| POST | `/billing/subscription/cancel` | `billing.manage` | Cancel subscription |
| POST | `/billing/subscription/reactivate` | `billing.manage` | Reactivate subscription |
| GET | `/billing/settings` | `billing.view` | Operational settings |
| PUT | `/billing/settings` | `billing.manage` | Update operational settings |

## Subscription Lifecycle

| Action | Behavior |
|--------|----------|
| **Assign plan** | Updates subscription, records audit event |
| **Cancel (at period end)** | Sets `cancel_at_period_end`, keeps access until period end |
| **Cancel (immediate)** | Sets status `cancelled`, blocks gated features |
| **Reactivate** | Clears cancellation flags, restores `active` status |

Historical billing records are never deleted on cancellation.

## Provider Abstraction

```php
interface BillingProviderInterface {
    public function syncSubscription(Subscription $subscription): Subscription;
    public function createInvoice(BillingInvoice $invoice): BillingInvoice;
    public function recordPayment(BillingPayment $payment): BillingPayment;
}
```

M5 ships `MockBillingProvider` — no external API calls, no credentials required.

## Frontend

Route: `/billing` (nav item visible with `billing.view`)

Sections:

- Current plan and subscription state
- Usage/quota summary
- Invoice list
- Operational settings (admin only)

## Security Model

- Organization ID derived from tenant context, never trusted from request body
- Billing mutations require `billing.manage`
- Cross-tenant IDOR blocked by organization global scopes + middleware
- Subscription inactive → `403 SubscriptionInactiveException`
- Plan restriction → `403 PlanRestrictionException`
- Quota exceeded → `429 AiQuotaExceededException`

## Testing

`backend/tests/Feature/Phase11M5BillingTest.php` covers:

- Plan catalog
- Subscription assign/cancel/reactivate
- Usage summary integration
- Inactive subscription AI blocking
- Plan quota enforcement
- Tenant isolation
- RBAC for plan assignment
- Invoice scoping
- Operational settings

Run full suite:

```bash
docker compose --profile test run --rm backend-test
```

## Remaining Production Risks

- Live Stripe/payment webhooks not implemented
- Invoice PDF/email delivery not implemented
- Proration and mid-cycle plan changes are basic
- `BILLING_ENABLED` defaults to `false` — must be enabled explicitly in staging/production after validation

## Related Documents

- [billing.md](./billing.md)
- [phase-11-architecture.md](./phase-11-architecture.md)
- [ai-platform.md](./ai-platform.md)
- [m4-dashboard.md](./m4-dashboard.md)
