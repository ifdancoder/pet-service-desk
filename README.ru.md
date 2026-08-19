# SupportFlow

*[English version](README.md)*

SupportFlow: платформа ServiceDesk/helpdesk на Laravel. Начиналась как REST API, а выросла в полноценный бэкенд: архитектура Form Request → DTO → Service → Model, авторизация на основе политик (Spatie Permission и Laravel Policies), движок событий и отслеживания SLA, админ-панель на Filament и полнотекстовый поиск по тикетам через Elasticsearch.

Основная предметная область: тикеты и их вложенные сущности (комментарии, вложения, наблюдатели, теги), а также сохранённые фильтры и справочники для администраторов (отделы, команды, категории тикетов, теги, политики SLA).

## Требования

- PHP `^8.3`
- Composer
- PostgreSQL 15+ (используется по умолчанию, см. `DB_CONNECTION` в `.env.example`; MySQL тоже подойдёт, если указать соответствующие переменные `DB_*`)
- MinIO или другое S3-совместимое хранилище, для вложений тикетов
- Elasticsearch 8.x, для поиска по тикетам

## Установка

Приложению нужны запущенные Postgres, MinIO и Elasticsearch. `docker-compose.yml` в корне репозитория поднимает все три с теми же данными для подключения, что уже прописаны в `.env.example`:

```bash
docker compose up -d
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Сидер создаёт демо-роли, права, пул клиентов и тестовые тикеты, и сразу ставит их в очередь на индексацию в Elasticsearch. `test@example.com` / `password` заведён как администратор, так что этим пользователем можно сразу зайти и в API, и в админ-панель. Подробности смотрите в `database/seeders`.

## Настройка PostgreSQL

`.env.example` уже настроен на сервис `postgres` из `docker-compose.yml`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=supportflow
DB_USERNAME=supportflow
DB_PASSWORD=supportflow-dev-secret
```

Этот контейнер при первом запуске также создаёт отдельную базу `supportflow_testing` (см. `docker/postgres/init-testing-db.sh`), на которую и смотрит `phpunit.xml`. Тесты работают с настоящей базой Postgres, а не с in-memory базой, так что перед запуском тестов `docker compose up -d postgres` тоже должен быть поднят.

## Настройка MinIO (вложения)

Вложения к тикетам хранятся в S3-совместимом бакете. `.env.example` уже настроен на сервис `minio` из `docker-compose.yml`:

```
AWS_ACCESS_KEY_ID=supportflow
AWS_SECRET_ACCESS_KEY=supportflow-dev-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=supportflow-attachments
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Сам бакет всё равно нужно создать один раз, уже после того как контейнер поднялся:

```bash
mc alias set supportflow-local http://localhost:9000 supportflow supportflow-dev-secret
mc mb supportflow-local/supportflow-attachments
```

Консоль MinIO будет доступна на `http://localhost:9001` (используйте логин и пароль root-пользователя выше).

## Настройка Elasticsearch (поиск)

Поиск по тикетам работает через Elasticsearch. `.env.example` уже настроен на сервис `elasticsearch` из `docker-compose.yml`:

```
ELASTICSEARCH_HOSTS=http://localhost:9200
ELASTICSEARCH_TICKET_INDEX=tickets
```

Индекс `tickets` создаётся автоматически при первой индексации тикета, отдельной миграции для этого не нужно. Если Elasticsearch недоступен, создание/изменение/удаление тикетов всё равно работает, а поиск просто ничего не находит вместо того чтобы падать: индексация идёт через очередь, и при сбое джоб пишет предупреждение в лог и не падает, то же самое делает и сам эндпоинт поиска. Когда Elasticsearch снова доступен, выполните `php artisan tickets:reindex-search`, чтобы доиндексировать то, что было пропущено во время простоя.

## Аутентификация

Авторизуйтесь по email и паролю, чтобы получить токен Sanctum, а затем передавайте его как bearer-токен в последующих запросах:

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

Текущий токен отзывается через `DELETE /api/v1/auth/tokens/current`.

## Обзор API

Все эндпоинты живут под `/api/v1` и, кроме выдачи токена, требуют заголовок `Authorization: Bearer <token>`. Группы ресурсов:

- `tickets`: CRUD, плюс действия `assign`/`close`/`reopen`/`priority` и эндпоинт поиска (`GET /tickets/search?q=...`)
- `tickets/{ticket}/comments`, `tickets/{ticket}/attachments`, `tickets/{ticket}/watchers`, `tickets/{ticket}/tags`
- `saved-filters`: сохранённые фильтры списка тикетов для конкретного пользователя
- `departments`, `teams`, `ticket-categories`, `tags`, `sla-policies`: справочники, которыми управляют администраторы

Полный контракт, все эндпоинты, форматы запросов и ответов, формат ошибок: всё это описано в [`openapi.yaml`](openapi.yaml). Откройте его в [Swagger Editor](https://editor.swagger.io/) или любом другом просмотрщике OpenAPI.

## Админ-панель

Админ-панель на Filament находится по адресу `/admin`. Она покрывает тикеты (с комментариями, вложениями и наблюдателями прямо внутри), команды, отделы, категории тикетов и политики SLA, а также включает дашборды по объёму тикетов и нарушениям SLA. Доступ определяется теми же ролями и правами, что и в API.

## События и отслеживание SLA

Смена статуса тикета, назначение исполнителя и новые комментарии порождают доменные события, которые запускают уведомления и расчёты SLA. У каждой категории тикетов может быть своя политика SLA на каждый уровень приоритета (время реакции и время решения), а плановая команда отмечает тикеты, нарушившие SLA, чтобы об этом узнали тимлиды.

## Запуск тестов

Нужен запущенный контейнер Postgres из шага установки выше (`docker compose up -d postgres`), поскольку тесты работают с настоящей базой `supportflow_testing`, а не с in-memory базой.

```bash
php artisan test
# или
vendor/bin/pest
```
