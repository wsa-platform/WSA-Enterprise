# Production Secrets Management — M12.3

## Purpose

M12.3 defines how WSA-Enterprise production secrets are represented, stored, and used without committing real credentials to Git.

## Rules

1. `.env.production.example` is a template only and contains placeholders.
2. The real production `.env` lives only on the production host and is never committed.
3. Production credentials must be unique and strong; never reuse local development secrets.
4. Never commit passwords, API keys, access tokens, SSH private keys, TLS private keys, or certificates.
5. GitHub Actions deployment credentials belong in the protected `production` environment.
6. Rotate any credential immediately if it is exposed or accidentally committed.
7. Production deployment should use immutable image tags for controlled releases; `main` remains the default deployment tag until production verification establishes a release process.

## Host-side configuration

Start from `.env.production.example` and replace every placeholder on the production host.

Required values include:

- `DOMAIN`
- `CERTBOT_EMAIL`
- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`
- `VITE_API_URL`
- `FRONTEND_URL` (comma-separated allowed browser origins for CORS — M13.4)
- `VITE_SHOW_DEMO_LOGIN=false` (production frontend builds)
- `IMAGE_TAG`
- `GHCR_IMAGE_PREFIX`

The production `.env` must have permissions restricted to the deployment user/root as appropriate for the host.

## CORS and client hardening (M13.4)

Set `FRONTEND_URL` in `backend/.env` to the production SPA origin(s). Comma-separated values are supported, for example:

```env
FRONTEND_URL=https://app.example.com
```

Production Docker builds pass `VITE_SHOW_DEMO_LOGIN=false` so the React client does not ship demo credentials. Flutter release builds omit the demo hint unless compiled with `--dart-define=SHOW_DEMO_HINT=true`.

Bearer tokens remain in client localStorage (Phase 11 known limitation — not changed in M13).

## GitHub production environment secrets

Configure these as GitHub Actions environment secrets under `production`:

- `PROD_HOST`
- `PROD_USER`
- `PROD_SSH_KEY`
- `PROD_DEPLOY_PATH`
- `GHCR_PULL_TOKEN`

Enable required reviewers for the `production` environment before allowing production deployment.

## Publish permissions

The image publishing workflow uses the repository `GITHUB_TOKEN` with `packages: write`; no long-lived publish token is required for normal `main` publishing.

## Verification checklist

- [ ] No real passwords or tokens are tracked.
- [ ] `.env.production.example` contains placeholders only.
- [ ] Production `.env` is host-local and ignored by Git.
- [ ] GitHub `production` environment exists.
- [ ] Required deployment secrets are configured in GitHub.
- [ ] Required reviewers are configured for production deployment.
- [ ] GHCR pull credentials are available to the deployment path when packages are private.
