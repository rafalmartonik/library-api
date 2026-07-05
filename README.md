# library-api

Simple library API built with **Symfony + FrankenPHP + PostgreSQL**: add books,
list them, remove them, and change their borrow state. No authentication.

**Live demo:** https://library-api-production-5904.up.railway.app
**Interactive docs (Swagger UI):** https://library-api-production-5904.up.railway.app/api/doc

## Running the app

Bring up the stack (FrankenPHP + PostgreSQL). The image is self-contained and the
entrypoint runs migrations on startup, so a fresh clone just works:

```bash
docker-compose up -d --build
```

The app is served on http://localhost:8080.

## API

Base path: `/api/books`. All payloads and responses are JSON.

| Method   | Path                        | Description               | Success | Errors                          |
|----------|-----------------------------|---------------------------|---------|---------------------------------|
| `GET`    | `/api/books`                | List all books            | `200`   | –                               |
| `POST`   | `/api/books`                | Add a book                | `201`   | `409` duplicate, `422` invalid  |
| `PATCH`  | `/api/books/{serialNumber}` | Change borrow state       | `200`   | `404`, `409` conflict, `422`    |
| `DELETE` | `/api/books/{serialNumber}` | Remove a book             | `204`   | `404` not found                 |

A book has a unique 6-digit `serialNumber`, `title`, `author`, and its borrow state
(`borrowed`, `borrowedByCardNumber` — a 6-digit library card, `borrowedAt`).

**Interactive docs:** http://localhost:8080/api/doc (raw spec: `/api/openapi.json`).

Quick taste:

```bash
# add a book
curl -X POST http://localhost:8080/api/books \
  -H 'Content-Type: application/json' \
  -d '{"serialNumber":"123456","title":"Solaris","author":"Lem"}'

# borrow it (card number required)
curl -X PATCH http://localhost:8080/api/books/123456 \
  -H 'Content-Type: application/json' \
  -d '{"borrowed":true,"cardNumber":"654321"}'

# return it
curl -X PATCH http://localhost:8080/api/books/123456 \
  -H 'Content-Type: application/json' \
  -d '{"borrowed":false}'
```

## Quality checks

PSR-12 style (PHP_CodeSniffer, `phpcs.xml.dist`), static analysis (PHPStan level 6
+ Symfony extension, `phpstan.dist.neon`) and tests (PHPUnit, `phpunit.dist.xml`).
The tools ship in the image and the source is bind-mounted (see `docker-compose.yml`),
so once the stack is up they run against your live code with no extra setup:

```bash
docker-compose exec app vendor/bin/phpcs              # coding standard
docker-compose exec app vendor/bin/phpcbf             # auto-fix style
docker-compose exec app vendor/bin/phpstan analyse    # static analysis
```

## Tests

The functional and acceptance tests run against a separate `library_test` database.
Create and migrate it once (the dev database is handled automatically on startup):

```bash
docker-compose exec app php bin/console --env=test doctrine:database:create --if-not-exists
docker-compose exec app php bin/console --env=test doctrine:migrations:migrate -n
```

Then run the suite:

```bash
docker-compose exec app vendor/bin/phpunit
```

The whole gate at once (stops at the first failure):

```bash
docker-compose exec app sh -c "vendor/bin/phpcs && vendor/bin/phpstan analyse && vendor/bin/phpunit"
```

Dependencies live in the image; after changing them (`composer require ...`) rebuild
with `docker-compose up -d --build`.
