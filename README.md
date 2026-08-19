# SupportFlow

*[Русская версия](README.ru.md)*

SupportFlow is a ServiceDesk/helpdesk platform built with Laravel. It started as a REST API and grew into a full backend: a Form Request to DTO to Service to Model architecture, Policy-based authorization (Spatie Permission plus Laravel Policies), an events and SLA tracking engine, a Filament admin panel, and full-text search over tickets through Elasticsearch.

The core domain is tickets and their sub-resources (comments, attachments, watchers, tags), plus saved filters and admin-facing catalog resources (departments, teams, ticket categories, tags, SLA policies).

## Requirements

- PHP `^8.3`
- Composer
- SQLite (the configured default, see `DB_CONNECTION` in `.env.example`; MySQL/Postgres also work if you point the `DB_*` variables at one)
- MinIO or another S3-compatible object store, for ticket attachments
- Elasticsearch 8.x, for ticket search

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

The seeder creates demo roles, permissions, a pool of customers, and sample tickets. `test@example.com` / `password` is seeded as an administrator, so you can log into both the API and the admin panel with it right away. See `database/seeders` for the rest.

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

## Elasticsearch setup (search)

Ticket search is backed by Elasticsearch. Start a local single-node instance:

```bash
docker run -d -p 9200:9200 --name supportflow-elasticsearch \
  -e discovery.type=single-node -e xpack.security.enabled=false \
  docker.elastic.co/elasticsearch/elasticsearch:8.15.0
```

`.env.example` already points at it:

```
ELASTICSEARCH_HOSTS=http://localhost:9200
ELASTICSEARCH_TICKET_INDEX=tickets
```

The `tickets` index gets created automatically the first time a ticket is saved, there's no separate migration step. If Elasticsearch is down or unreachable, ticket create/update/delete still work as normal: indexing happens in a queued job that logs a warning and moves on rather than failing the request. Search itself just returns nothing until the index catches back up.

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

```bash
php artisan test
# or
vendor/bin/pest
```
