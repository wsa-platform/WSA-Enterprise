# Phase 11 M6 — Flutter Platform

**Last updated:** 2026-08-10  
**Status:** Implemented on branch `phase-11-m6-enterprise-flutter-platform`  
**Scope:** Roadmap Milestone 5 (Flutter platform alignment), delivered as execution Milestone 6 after M5 billing merge.

## Objective

Align the Flutter mobile client with the Phase 11 backend architecture:

- Layered `data/` / `domain/` / `presentation/` structure
- Domain-scoped API clients (auth, platform, AI, notifications, modules)
- Typed models integrated into the data layer
- AI request polling use case for async lifecycle
- Notifications, profile, and settings screens
- Token storage abstraction (SharedPreferences default; secure storage ready)
- Removed hard-coded demo credentials from login UI

## Architecture

```
mobile/lib/
├── data/
│   ├── api/          # HttpClient + domain APIs + ApiClient facade
│   ├── models/       # Typed JSON parsers
│   └── storage/      # TokenStorage interface + SharedPreferences impl
├── domain/
│   └── use_cases/    # PollAiRequestUseCase
├── presentation/
│   └── screens/      # Notifications, Profile, Settings
├── screens/          # Existing module screens (Phase 7)
└── widgets/
```

Backward-compatible exports remain at `lib/api/api_client.dart` and `lib/api/models.dart`.

## New mobile capabilities

| Screen | API | Purpose |
|--------|-----|---------|
| Notifications | `GET /notifications`, `POST /notifications/{id}/read` | In-app notification list |
| Profile | `GET /platform/me` | User, roles, permissions |
| Settings | `GET /ai/usage` | Session + AI quota summary (read-only) |
| AI workspace | polling use case | Poll pending AI requests to completion |

## Security

- Bearer token + `X-Organization-Id` on all authenticated requests
- Session stored via `TokenStorage` abstraction
- Login form no longer pre-fills credentials
- 401 responses clear local session and return to login

## Testing

```bash
cd mobile
flutter analyze
flutter test
```

Coverage includes model parsers, AI polling use case, login widget (no prefilled creds), and existing ApiClient tests.

## Out of scope (M7+)

- Push notifications
- Secure Keychain/Keystore storage implementation
- Billing mobile UI (available on web `/billing` in M5)
- Notification delivery queue expansion

## Related documents

- [phase-11-roadmap.md](./phase-11-roadmap.md) — Milestone 5 Flutter platform specification
- [m5-enterprise-operations.md](./m5-enterprise-operations.md)
- [ai-platform.md](./ai-platform.md)
