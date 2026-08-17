# SupportFlow

SupportFlow is a ServiceDesk/helpdesk REST API built with Laravel, demonstrating a Form Request → DTO → Service → Model architecture, Policy-based authorization (Spatie Permission + Laravel Policies), and Sanctum token authentication.

The API covers tickets and their sub-resources (comments, attachments, watchers, tags), saved filters, and admin-facing catalog resources (departments, teams, ticket categories, tags, SLA policies).

## Requirements

- PHP `^8.3`
- Composer
- SQLite (the configured default — see `DB_CONNECTION` in `.env.example`; MySQL/Postgres also work if you point the `DB_*` variables at one)
- MinIO or another S3-compatible object store, for ticket attachments

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

The seeder creates demo roles, permissions, a pool of customers, and sample role assignments — see `database/seeders` for details.

## MinIO setup (attachments)

Ticket attachments are stored in an S3-compatible bucket. `.env.example` is already configured for a local MinIO instance:

```
AWS_ACCESS_KEY_ID=supportflow
AWS_SECRET_ACCESS_KEY=supportflow-dev-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=supportflow-attachments
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Start a local MinIO server and create the bucket:

```bash
docker run -d -p 9000:9000 -p 9001:9001 --name supportflow-minio \
  -e MINIO_ROOT_USER=supportflow -e MINIO_ROOT_PASSWORD=supportflow-dev-secret \
  minio/minio server /data --console-address ":9001"

mc alias set supportflow-local http://localhost:9000 supportflow supportflow-dev-secret
mc mb supportflow-local/supportflow-attachments
```

The MinIO console is then available at `http://localhost:9001` (login with the root user/password above).

## Authentication

Authenticate with email/password to receive a Sanctum token, then send it as a bearer token on subsequent requests:

```bash
curl -X POST http://localhost:8000/api/v1/auth/tokens \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
# => { "data": { "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" } }

curl http://localhost:8000/api/v1/tickets \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Accept: application/json"
```

Revoke the current token with `DELETE /api/v1/auth/tokens/current`.

## API overview

All endpoints live under `/api/v1` and (aside from issuing a token) require the `Authorization: Bearer <token>` header. Resource groups:

- `tickets` — CRUD, plus `assign`/`close`/`reopen`/`priority` actions
- `tickets/{ticket}/comments`, `tickets/{ticket}/attachments`, `tickets/{ticket}/watchers`, `tickets/{ticket}/tags`
- `saved-filters` — per-user saved ticket-list filters
- `departments`, `teams`, `ticket-categories`, `tags`, `sla-policies` — admin-managed catalog resources

For the full route list, request/response shapes, and design rationale, see [`docs/superpowers/specs/2026-09-29-api-layer-design.md`](docs/superpowers/specs/2026-09-29-api-layer-design.md).

## Running tests

```bash
php artisan test
# or
vendor/bin/pest
```
