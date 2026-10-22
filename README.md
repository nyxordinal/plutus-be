# Plutus BE

Backend API for Plutus, Nyxordinal's personal finance application.

## Stack

- PHP 8.2.35 or later (Composer requirement: `^8.2.35`)
- Laravel Lumen 8.3.4
- MySQL, with Redis used by the internal-client middleware and cache configuration
- JWT authentication (`php-open-source-saver/jwt-auth`)
- PHPUnit 9

The Docker image is based on `php:8.2.35-fpm-alpine`. The container starts the HTTP server on port `8001` and a queue listener.

## Requirements

For local development, install:

- PHP 8.2.35 or later with PDO MySQL and OpenSSL extensions
- Composer
- MySQL
- Redis (or configure another supported cache/queue backend)

## Local setup

1. Clone the repository and enter the project directory:

    ```sh
    git clone https://github.com/nyxordinal/plutus-be.git
    cd plutus-be
    ```

2. Create the environment file and install dependencies:

    ```sh
    cp .env.example .env
    composer install
    ```

3. Configure `.env` for your local database, Redis, mail transport, and application URL. Set `APP_KEY` and `JWT_SECRET`:

    ```sh
    php artisan key:generate
    php artisan jwt:secret
    ```

    Login also needs the matching encryption key material: set `RSA_PRIVATE_KEY` for v1 login and `AES_SECRET_KEY` for v2 login. The corresponding public RSA key belongs in the Plutus frontend configuration.

4. Create the database named in `DB_DATABASE`, then run migrations:

    ```sh
    php artisan migrate
    ```

    For local development only, `php artisan db:seed` creates a sample user and internal client. Do not use these seed records in production.

5. Start the development server:

    ```sh
    php artisan serve --host=127.0.0.1 --port=8000
    ```

The API is then available at `http://127.0.0.1:8000`.

## API routes

Public API v1 routes use the `/public/v1` prefix. Most endpoints require a JWT access token in `Authorization: Bearer <token>`.

| Area                            | Routes                                                                                                                                 |
| ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Status and login                | `GET /public/v1/`, `GET /public/v1/login-state`, `POST /public/v1/auth/login`                                                          |
| Registration and password reset | `POST /public/v1/auth/register`, `/forgot`, `/reset`                                                                                   |
| Current user and token          | `GET /public/v1/auth/me`, `GET /public/v1/auth/refresh`                                                                                |
| Home                            | `GET /public/v1/home/stat`                                                                                                             |
| Expenses                        | `GET` and `POST /public/v1/expense`, `PUT /public/v1/expense`, `GET /public/v1/expense/summary`, `POST /public/v1/expense/delete/bulk` |
| Expense drafts                  | `GET` and `PUT /public/v1/expense/draft`, `POST /public/v1/expense/draft/approve`, `POST /public/v1/expense/draft/deny`                |
| Income                          | `GET` and `POST /public/v1/income`, `PUT /public/v1/income`, `GET /public/v1/income/summary`, `POST /public/v1/income/delete/bulk`     |
| Settings                        | `GET` and `PUT /public/v1/setting`                                                                                                     |

Public API v2 currently provides `POST /public/v2/auth/login`.

The internal expense endpoint is `POST /internal/v1/expense`. It requires both `X-Client-Id` and `X-Client-Token` headers; client credentials are stored in the `clients` table.

## Tests

Run the PHPUnit suite with:

```sh
vendor/bin/phpunit
```

## Docker

Build the application image from the repository root:

```sh
docker build -t nyxordinal/plutus-be:<tag> .
```

Pass the application configuration at runtime. For example:

```sh
docker run -d \
  --name plutus-be \
  --env-file <runtime-env-file> \
  -p 8001:8001 \
  nyxordinal/plutus-be:<tag>
```

Make sure `DB_HOST` and `REDIS_HOST` in the runtime environment resolve from inside the container. For production, set `APP_ENV=production`, `APP_DEBUG=false`, and provide production-only secrets and credentials. Run database migrations as a deployment step:

```sh
docker exec plutus-be php artisan migrate --force
```

To publish an image, tag it for the registry and push it:

```sh
docker push nyxordinal/plutus-be:<tag>
```

## License

See [LICENSE](LICENSE).
