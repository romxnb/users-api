# Users API

## Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose v2)

## Run with Docker (development)

### 1) Create `.env.local`

This project uses Symfony’s `.env` + optional `.env.local` overrides.

Create a `.env.local` file in the project root:

```dotenv
# /Users/roman/Documents/Projects/users-api/.env.local
APP_ENV=dev
APP_SECRET=ChangeMeToARealSecret

# DB credentials must match the docker-compose `database` service
DATABASE_URL="mysql://app:!ChangeMe!@database:3306/app?serverVersion=8.4&charset=utf8mb4"

# LexikJWTAuthenticationBundle
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=ChangeMeToARandomPassphrase

# Optional: if you expose the app on a different host/port
# DEFAULT_URI=http://localhost
```

Notes:
- `APP_SECRET` should be a long random string.
- `JWT_PASSPHRASE` can be any random string; it’s used to encrypt the private key.
- You can keep `.env` as-is and only override values in `.env.local`.

### 2) Start containers

```bash
docker compose up -d --build
```

The app container is `php` and the DB container is `database`.

### 3) Install PHP dependencies (first run only)

```bash
docker compose exec php composer install
```

### 4) Create the database + run migrations

```bash
docker compose exec php php bin/console doctrine:database:create --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### 5) Generate JWT PEM keys (LexikJWTAuthenticationBundle)

LexikJWT reads these env vars (see `config/packages/lexik_jwt_authentication.yaml`):

- `JWT_SECRET_KEY` (private key path)
- `JWT_PUBLIC_KEY` (public key path)
- `JWT_PASSPHRASE`

Generate keys **inside the container** so the paths match and permissions are correct:

```bash
# Ensure the directory exists
docker compose exec php mkdir -p config/jwt

# Generate the keypair (will use JWT_PASSPHRASE)
docker compose exec php php bin/console lexik:jwt:generate-keypair
```

Expected output: `config/jwt/private.pem` and `config/jwt/public.pem`.

If you want to overwrite existing keys:

```bash
docker compose exec php php bin/console lexik:jwt:generate-keypair --overwrite
```

### 6) Create a root user

```bash
docker compose exec php php bin/console app:create-root
```

### 7) Access the API

- API: `http://localhost/`

- `POST /api/v1/auth/login`
- JSON body uses `login` and `pass`

Example:

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin","pass":"admin"}'
```

## Common commands

# Run tests
docker compose exec php php vendor/bin/phpunit

# Stop
docker compose down
