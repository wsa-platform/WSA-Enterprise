# WSA-Enterprise

WSA-Enterprise is a multi-client platform with a Laravel 12 API, React 19 web application, and Flutter mobile application.

## Architecture

| Directory | Stack | Purpose |
| --- | --- | --- |
| `backend` | Laravel 12, Sanctum | REST API and authentication |
| `frontend` | React 19, Vite, TypeScript | Browser client |
| `mobile` | Flutter | iOS and Android client |
| `nginx` | Nginx | HTTP gateway for the Laravel API |

PostgreSQL stores application data and Redis provides cache, sessions, and queues.

## Start with Docker

```bash
cp backend/.env.example backend/.env
docker compose up --build
```

The API is available at `http://localhost:8080`; its health endpoint is `GET /api/v1/health`.

## Local development

```bash
# Backend (requires PHP 8.2+ and Composer)
cd backend && composer install && php artisan key:generate && php artisan migrate

# Frontend (requires Node 22+)
cd frontend && npm install && npm run dev

# Mobile (requires Flutter SDK)
cd mobile && flutter create . && flutter pub get && flutter run
```

Sanctum protects API routes via `auth:sanctum`; `/api/v1/user` is the initial authenticated endpoint.
