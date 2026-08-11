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
- `IMAGE_TAG`
- `GHCR_IMAGE_PREFIX`

The production `.env` must have permissions restricted to the deployment user/root as appropriate for the host.

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
