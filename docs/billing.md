# Billing

**Last updated:** Phase 11 M5 (2026-08-10)  
**Status:** Foundation implemented — mock provider only; no live payment integration

## Overview

Phase 11 M5 introduces a **provider-independent billing domain** that models plans, subscriptions, usage, invoices, and entitlements. This enables feature gating and usage limits without coupling to Stripe or any specific payment processor.

**M5 scope:** Domain models, migrations, REST APIs, entitlement checks, dashboard UI, mock provider.  
**Out of scope:** Live payment processing, webhooks, Stripe credentials.

---

## Domain Model

```
Plan (global catalog)
  └── PlanFeature (feature_key, limit_value)
        └── BillingAccount (organization)
              └── Subscription (organization_id, plan_id, status)
                    └── BillingInvoice → BillingPayment
                          └── UsageRecord (existing AI metering)
```

---

## Core Entities

### Plan

Catalog entry defining a service tier.

| Field | Type | Description |
|-------|------|-------------|
| `slug` | string | `free`, `pro`, `enterprise` |
| `name` | string | Display name |
| `description` | text | Marketing description |
| `is_active` | boolean | Available for new subscriptions |

### PlanFeature

Feature flag or limit attached to a plan.

| Field | Type | Description |
|-------|------|-------------|
| `feature_key` | string | e.g. `ai.requests`, `users.max`, `modules.business` |
| `limit_value` | integer/null | Null = unlimited |
| `limit_period` | string | `monthly`, `lifetime` |

### Subscription

Links an organization to a plan.

| Status | Meaning |
|--------|---------|
| `trialing` | Trial period active |
| `active` | Paid/active subscription |
| `past_due` | Payment failed (future) |
| `cancelled` | Subscription ended |

### Entitlement

Resolved access for an organization (computed from subscription + plan features).

```php
EntitlementService::canUseFeature($org, 'ai.requests'): bool
EntitlementService::getLimit($org, 'ai.requests'): ?int
EntitlementService::assertSubscriptionActive($org): void
```

### UsageRecord

Metered consumption tracking (existing M3 infrastructure).

| Field | Type | Description |
|-------|------|-------------|
| `organization_id` | FK | Tenant |
| `metric` | string | e.g. `ai.requests`, `ai.tokens` |
| `quantity` | integer | Amount consumed |
| `period_start` | date | Billing period start |

---

## Default Plans (Seeded)

| Plan | AI Requests/mo | Users | Modules |
|------|---------------|-------|---------|
| Free | 50 | 5 | Core agricultural |
| Pro | 500 | 25 | All modules |
| Enterprise | Unlimited | Unlimited | All + priority |

*Limits enforced via `EntitlementService` — not hard-coded in controllers.*

---

## Provider Abstraction

```php
interface BillingProviderInterface {
    public function syncSubscription(Subscription $subscription): Subscription;
    public function createInvoice(BillingInvoice $invoice): BillingInvoice;
    public function recordPayment(BillingPayment $payment): BillingPayment;
}
```

**M5 implementation:** `MockBillingProvider` — no external calls, no API keys.

**Future:** `StripeBillingProvider` implements same interface.

---

## API Endpoints

| Method | Path | Permission | Purpose |
|--------|------|------------|---------|
| GET | `/api/v1/billing/plans` | `billing.view` | Plan catalog |
| GET | `/api/v1/billing/subscription` | `billing.view` | Current org subscription |
| GET | `/api/v1/billing/usage` | `billing.view` | Usage summary for current period |
| GET | `/api/v1/billing/invoices` | `billing.view` | Paginated invoices |
| POST | `/api/v1/billing/subscription/plan` | `billing.manage` | Assign/change plan |
| POST | `/api/v1/billing/subscription/cancel` | `billing.manage` | Cancel subscription |
| POST | `/api/v1/billing/subscription/reactivate` | `billing.manage` | Reactivate subscription |
| GET | `/api/v1/billing/settings` | `billing.view` | Operational settings |
| PUT | `/api/v1/billing/settings` | `billing.manage` | Update operational settings |

All endpoints require authentication + org context (`X-Organization-Id`).

---

## Integration Points

| System | Integration |
|--------|-------------|
| **AI Platform** | `AiQuotaService` checks plan `ai.requests` limit when billing enabled |
| **Access** | `users.max` entitlement checked on user invite/create |
| **Audit** | `billing.subscription.*` events on plan change and cancellation |
| **Dashboard** | `/billing` route shows plan, usage, invoices, settings |

---

## Authorization Stack

Feature access requires (in order):

1. Valid tenant context
2. RBAC permission (`billing.view` / `billing.manage` / feature-specific permissions)
3. Active subscription (when `BILLING_ENABLED=true`)
4. Plan feature allowance
5. Quota not exceeded

Error responses:

| Condition | Status | Exception |
|-----------|--------|-----------|
| Not authenticated | 401 | — |
| Missing permission | 403 | Authorization |
| Inactive subscription | 403 | `SubscriptionInactiveException` |
| Plan restriction | 403 | `PlanRestrictionException` |
| Quota exceeded | 429 | `AiQuotaExceededException` |

---

## Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `BILLING_ENABLED` | `false` | Enable entitlement enforcement |
| `BILLING_PROVIDER` | `mock` | Provider selection (future: `stripe`) |
| `DEFAULT_PLAN_SLUG` | `free` | Plan assigned to new orgs |

When `BILLING_ENABLED=false`, billing APIs remain available but entitlement enforcement is bypassed (existing behavior preserved).

---

## Migration Strategy

1. Run billing migration (additive)
2. Seed default plans via `BillingSeeder`
3. Enable `BILLING_ENABLED=true` in staging for validation
4. Assign subscriptions via API or seeder
5. Production: enable after validation

**No changes to existing tables except new FK references.**

---

## Related Documents

- [m5-enterprise-operations.md](./m5-enterprise-operations.md)
- [phase-11-architecture.md](./phase-11-architecture.md)
- [phase-11-roadmap.md](./phase-11-roadmap.md)
- [ai-platform.md](./ai-platform.md)
