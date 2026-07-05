# library-api
Library API

## Running the app

Bring up the stack (FrankenPHP + PostgreSQL). The image is self-contained, so a
fresh clone just works:

```bash
docker-compose up -d --build
```

The app is served on http://localhost:8080.

## Quality checks

The project is configured for PSR-12 style (PHP_CodeSniffer, `phpcs.xml.dist`),
static analysis (PHPStan level 6 + Symfony extension, `phpstan.dist.neon`) and
tests (PHPUnit, `phpunit.dist.xml`). The tools ship in the image and the source is
bind-mounted (see `docker-compose.yml`), so once the stack is up they run against
your live code with no extra setup:

```bash
docker-compose exec app vendor/bin/phpcs              # coding standard
docker-compose exec app vendor/bin/phpcbf             # auto-fix style
docker-compose exec app vendor/bin/phpstan analyse    # static analysis
docker-compose exec app vendor/bin/phpunit            # tests
```

Run the whole gate before committing; `&&` stops at the first failure, so nothing
broken gets committed:

```bash
docker-compose exec app sh -c "vendor/bin/phpcs && vendor/bin/phpstan analyse && vendor/bin/phpunit"
```

Dependencies live in the image; after changing them (`composer require ...`) rebuild
with `docker-compose up -d --build`.
