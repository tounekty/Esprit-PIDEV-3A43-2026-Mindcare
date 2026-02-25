# Installation Guide (Windows) - MindCare Symfony Project

## 1) Prerequisites

Install these tools:

- Git
- PHP 8.1+ (recommended: 8.2)
- Composer 2.x
- MySQL or MariaDB
- Symfony CLI (recommended)

Required PHP extensions:

- ctype
- iconv
- pdo_mysql
- intl
- mbstring
- openssl
- zip

Quick check:

```powershell
php -v
php -m
composer -V
symfony -V
```

## 2) Clone the project

```powershell
git clone <YOUR_REPO_URL>
cd PI_3A43
```

## 3) Install dependencies

```powershell
composer install
```

## 4) Configure environment

Create local env file:

```powershell
Copy-Item .env .env.local
```

Edit `.env.local`:

```env
APP_ENV=dev
APP_SECRET=change_this_secret
DATABASE_URL="mysql://root:@127.0.0.1:3306/mindcare?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

# OpenAI moderation
OPENAI_API_KEY="sk-proj-..."
LOCAL_COMMENT_FILTER_ENABLED=1
LOCAL_FILTER_ALLOW_ON_API_FAILURE=1

# Google Analytics (tracking + admin stats)
GA4_MEASUREMENT_ID=G-XXXXXXXXXX
GA4_PROPERTY_ID=123456789
GA4_CREDENTIALS_PATH=%kernel.project_dir%/config/ga4-service-account.json
GA4_LOOKBACK_DAYS=30
```

Notes:

- `GA4_MEASUREMENT_ID` must be the stream ID (`G-...`).
- `GA4_PROPERTY_ID` must be numeric (property ID), not the stream ID.
- `GA4_CREDENTIALS_PATH` must point to a valid service account JSON file.
- Do not commit real API keys or credentials.

## 5) OpenAI moderation behavior

Comment flow:

1. Local filter checks spam/bad words first.
2. If local filter flags content, comment is rejected.
3. If local filter passes, OpenAI moderation is called.
4. If OpenAI is rate-limited/unavailable and `LOCAL_FILTER_ALLOW_ON_API_FAILURE=1`, clean comments are still published using local fallback.

Set strict mode (block on OpenAI failure):

```env
LOCAL_FILTER_ALLOW_ON_API_FAILURE=0
```

## 6) Google Analytics setup (admin stats)

Required for `/admin/ressources/stats`:

1. Enable **Google Analytics Data API** in Google Cloud.
2. Create a Service Account and download JSON.
3. Place JSON in `config/ga4-service-account.json`.
4. Add service account email to GA4 Property access (Viewer or Analyst).
5. Ensure tracking script is active via `GA4_MEASUREMENT_ID` in `.env.local`.
6. Visit pages like `/resources/{id}` to generate events.

If admin GA cards show zeros, first check GA4 Realtime for `page_view`.

## 7) Database setup

```powershell
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
```

## 8) Start the project

Recommended:

```powershell
symfony serve -d
```

Open:

- `http://127.0.0.1:8000/home`

Alternative (without Symfony CLI):

```powershell
php -S 127.0.0.1:8000 -t public
```

## 9) Useful commands

```powershell
php bin/console cache:clear
php bin/console about
php bin/console debug:router
php bin/console lint:twig templates
php bin/console lint:container
```

## 10) Common issues

### A) `TerminateProcess: Access is denied` (Symfony CLI)

```powershell
symfony server:stop --all
taskkill /IM php-cgi.exe /F
symfony serve -d
```

### B) GA4 in admin shows 0 data

- Confirm `.env.local` values (`GA4_MEASUREMENT_ID`, `GA4_PROPERTY_ID`)
- Confirm service account has access to the same GA4 property
- Open `/resources/{id}` pages to generate data
- Wait for GA processing (minutes to 24h)

### C) Comment posting blocked by OpenAI 429

- Use fallback mode:

```env
LOCAL_FILTER_ALLOW_ON_API_FAILURE=1
```

- Clear cache after env changes:

```powershell
php bin/console cache:clear
```

### D) DB connection error

- Check MySQL/MariaDB service is running
- Verify `DATABASE_URL` in `.env.local`
- Re-run migrations

### E) Cache/template looks outdated

```powershell
php bin/console cache:clear --env=dev
```

Then hard refresh browser: `Ctrl + F5`.
