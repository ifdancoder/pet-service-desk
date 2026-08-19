# SupportFlow

*[Русская версия](README.ru.md)*

SupportFlow is a ServiceDesk/helpdesk platform built with Laravel. It started as a REST API and grew into a full backend: a Form Request to DTO to Service to Model architecture, Policy-based authorization (Spatie Permission plus Laravel Policies), an events and SLA tracking engine, a Filament admin panel, and full-text search over tickets through Elasticsearch.

The core domain is tickets and their sub-resources (comments, attachments, watchers, tags), plus saved filters and admin-facing catalog resources (departments, teams, ticket categories, tags, SLA policies).

## Requirements

- PHP `^8.3`
- Composer
- PostgreSQL 15+ (the configured default, see `DB_CONNECTION` in `.env.example`; MySQL also works if you point the `DB_*` variables at one)
- MinIO or another S3-compatible object store, for ticket attachments
- Elasticsearch 8.x, for ticket search

## Setup

The app needs Postgres, MinIO, and Elasticsearch running alongside it. `docker-compose.yml` in the repo root starts all three with the credentials `.env.example` already expects:

```bash
docker compose up -d
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

The seeder creates demo roles, permissions, a pool of customers, and sample tickets, and queues them all for indexing in Elasticsearch. `test@example.com` / `password` is seeded as an administrator, so you can log into both the API and the admin panel with it right away. See `database/seeders` for the rest.

## PostgreSQL setup

`.env.example` is already configured for the `postgres` service in `docker-compose.yml`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=supportflow
DB_USERNAME=supportflow
DB_PASSWORD=supportflow-dev-secret
```

That container also creates a separate `supportflow_testing` database on first boot (see `docker/postgres/init-testing-db.sh`), which is what `phpunit.xml` points the test suite at. Tests run against a real Postgres database, not an in-memory one, so `docker compose up -d postgres` needs to be running before you run the suite too.

## MinIO setup (attachments)

Ticket attachments are stored in an S3-compatible bucket. `.env.example` is already configured for the `minio` service in `docker-compose.yml`:

```
AWS_ACCESS_KEY_ID=supportflow
AWS_SECRET_ACCESS_KEY=supportflow-dev-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=supportflow-attachments
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

The bucket itself still needs creating once, after the container is up:

```bash
mc alias set supportflow-local http://localhost:9000 supportflow supportflow-dev-secret
mc mb supportflow-local/supportflow-attachments
```

The MinIO console is then available at `http://localhost:9001` (login with the root user/password above).

## Elasticsearch setup (search)

Ticket search is backed by Elasticsearch. `.env.example` is already configured for the `elasticsearch` service in `docker-compose.yml`:

```
ELASTICSEARCH_HOSTS=http://localhost:9200
ELASTICSEARCH_TICKET_INDEX=tickets
```

The `tickets` index gets created automatically the first time a ticket is indexed, there's no separate migration step. If Elasticsearch is down or unreachable, ticket create/update/delete still work as normal, and search just returns no results rather than failing: indexing happens in a queued job that logs a warning and moves on rather than failing the request, and the search endpoint does the same. Once Elasticsearch is back, run `php artisan tickets:reindex-search` to catch up anything that was missed while it was down.

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

- `tickets`: CRUD, plus `assign`/`close`/`reopen`/`priority` actions and a `search` endpoint (`GET /tickets/search?q=...`)
- `tickets/{ticket}/comments`, `tickets/{ticket}/attachments`, `tickets/{ticket}/watchers`, `tickets/{ticket}/tags`
- `saved-filters`: per-user saved ticket-list filters
- `departments`, `teams`, `ticket-categories`, `tags`, `sla-policies`: admin-managed catalog resources

The full contract, every endpoint, request and response shape, and error format, is described in [`openapi.yaml`](openapi.yaml). Open it in [Swagger Editor](https://editor.swagger.io/) or any OpenAPI-compatible viewer to browse it.

## Admin panel

A Filament-based admin panel lives at `/admin`. It covers tickets (with inline comments, attachments, and watchers), teams, departments, ticket categories, and SLA policies, and includes dashboards for ticket volume and SLA breach tracking. Access follows the same roles and permissions as the API.

## Events and SLA tracking

Ticket status changes, assignments, and comments fire domain events that drive notifications and SLA calculations. Each ticket category can have SLA policies per priority level (response time and resolution time), and a scheduled command flags tickets that have breached their SLA so team leads get notified.

## Running tests

Needs the Postgres container from the setup step above running (`docker compose up -d postgres`), since the suite runs against a real `supportflow_testing` database rather than an in-memory one.

```bash
php artisan test
# or
vendor/bin/pest
```
