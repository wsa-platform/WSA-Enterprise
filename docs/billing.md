# Billing

**Last updated:** Phase 11 (2026-08-10)  
**Status:** Design — domain foundation; no payment provider integrated

## Overview

Phase 11 introduces a **provider-independent billing domain** that models plans, subscriptions, usage, and entitlements. This enables feature gating and usage limits without coupling to Stripe or any specific payment processor.

**Phase 11 scope:** Domain models, migrations, read APIs, entitlement checks.  
**Out of scope:** Live payment processing, webhooks, invoicing.

---

## Domain Model

```
Plan
  └── PlanFeature (feature_key, limit_value)
        └── Subscription (organization_id, plan_id, status)
              └── Entitlement (resolved feature access)
                    └── UsageRecord (metered consumption)
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
```

### UsageRecord

Metered consumption tracking.

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
    public function createSubscription(Organization $org, Plan $plan): SubscriptionResult;
    public function cancelSubscription(Subscription $subscription): void;
    public function handleWebhook(array $payload): WebhookResult;
}
```

**Phase 11 implementation:** `NullBillingProvider` — all orgs get Free plan; no external calls.

**Future:** `StripeBillingProvider` implements same interface.

---

## API Endpoints (Planned)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/v1/billing/plans` | Plan catalog (public to authenticated users) |
| GET | `/api/v1/billing/subscription` | Current org subscription |
| GET | `/api/v1/billing/usage` | Usage summary for current period |

All endpoints require authentication + org context. Billing management requires `access.manage` or Owner role.

---

## Integration Points

| System | Integration |
|--------|-------------|
| **AI Platform** | `AiQuotaService` checks `ai.requests` entitlement before dispatch |
| **Access** | `users.max` entitlement checked on user invite/create |
| **Notifications** | `billing.quota_warning` when usage exceeds 80% of limit |
| **Audit** | `billing.subscription.changed` events |

---

## Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `BILLING_ENABLED` | `false` | Enable entitlement enforcement |
| `BILLING_PROVIDER` | `null` | Provider selection (future: `stripe`) |
| `DEFAULT_PLAN_SLUG` | `free` | Plan assigned to new orgs |

When `BILLING_ENABLED=false`, all features are allowed (current behavior preserved).

---

## Migration Strategy

1. Add billing tables (additive migration)
2. Seed default plans
3. Assign Free plan to all existing organizations
4. Enable `BILLING_ENABLED=true` in staging for testing
5. Wire AI quota enforcement
6. Production: enable after validation

**No changes to existing tables except new FK references.**

---

## Related Documents

- [phase-11-architecture.md](./phase-11-architecture.md)
- [phase-11-roadmap.md](./phase-11-roadmap.md)
- [ai-platform.md](./ai-platform.md)
