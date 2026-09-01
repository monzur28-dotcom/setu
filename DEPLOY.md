# Deploying SheTu

Supabase for the database and file storage, Railway to run the application,
GitHub for the code. Written to be followed in order.

Everything here has been verified against a real PostgreSQL 16 except the
two steps that need accounts nobody had yet — the Railway build and the
Supabase connection itself. Those are marked.

---

## Before you start

You need three things open:

| | |
|---|---|
| **Supabase** | a project, and its database connection string |
| **Railway** | a project, connected to the GitHub repository |
| **GitHub** | the Royal Bengal AI organisation, with this repository pushed to it |

---

## 1. Move the repository to the organisation

The code currently sits on a personal account. On GitHub:

1. Create an empty repository under the Royal Bengal AI organisation. No
   README, no `.gitignore` — this repository already has both.
2. Point the local checkout at it and push:

```bash
git remote set-url origin https://github.com/<org>/shetu.git
git push -u origin master
```

Keeping the old remote as well is reasonable while you check the new one
works: `git remote add personal <old-url>`.

---

## 2. Supabase: the database

Copy the **connection string**, not the individual fields. Supabase shows
several; take either:

- **Direct connection** — `db.PROJECT.supabase.co:5432`
- **Session pooler** — `aws-0-REGION.pooler.supabase.com:5432`

**Do not use the transaction pooler on port 6543.** It hands a different
backend to each statement, which breaks the prepared statements Laravel's
PDO driver relies on. Errors from that look like unrelated SQL failures and
waste an afternoon.

You do not need to create any tables. The container runs the migrations on
first boot.

### What has been checked

All 15 migrations, every seeder and the whole test suite were run against
PostgreSQL 16 locally before this was written. One migration needed
rewriting to get there: `widen_religion_and_locale_defaults` used
`$table->enum()->change()`, which Laravel emits as invalid SQL on Postgres.
It is now written per driver. Nothing else required changing.

---

## 3. Supabase: file storage

Member photographs are written to disk. On Railway that filesystem resets on
every redeploy, so they must go to object storage.

Create **three buckets**, all **private**:

| Bucket | Holds |
|---|---|
| `member-photos` | matrimonial profile photographs |
| `connect-photos` | Connect photographs — a separate bucket on purpose (wall rule W2) |
| `kyc-documents` | identity documents, deleted after 30 days |

> **Create them private.** This application serves media only through
> short-lived signed URLs. A public bucket bypasses that completely, and
> nothing in the code will warn you — it will simply work, and every
> member's photograph will be a public URL.

Then create an **S3 access key** under Storage settings. You need the key,
the secret, the endpoint and the region.

---

## 4. Railway: the service

Deploy from the GitHub repository. Railway will find the `Dockerfile` and
`railway.json` and needs no build configuration.

### Generate an APP_KEY first

```bash
php artisan key:generate --show
```

Copy what it prints. **This key decrypts stored mobile numbers.** Set it once
and never change it — a new key does not fail loudly, it silently orphans
every phone number already in the database. The container refuses to start
without one rather than generating its own, for exactly that reason.

### Environment variables

```
APP_NAME=SheTu
APP_ENV=production
APP_KEY=base64:...              # from the command above
APP_DEBUG=false
APP_URL=https://<your-railway-domain>
APP_TIMEZONE=UTC
APP_LOCALE=en

DB_CONNECTION=pgsql
DB_URL=postgresql://postgres.PROJECT:PASSWORD@aws-0-REGION.pooler.supabase.com:5432/postgres
DB_SSLMODE=require

MEDIA_DRIVER=s3
MEDIA_S3_ENDPOINT=https://PROJECT.supabase.co/storage/v1/s3
MEDIA_S3_REGION=us-east-1
MEDIA_S3_KEY=...
MEDIA_S3_SECRET=...
MEDIA_S3_BUCKET=member-photos
MEDIA_S3_CONNECT_BUCKET=connect-photos
MEDIA_S3_KYC_BUCKET=kyc-documents

SETU_HOME_MARKET=US
SETU_COMPANY_NAME=Royal Bengal AI
SETU_SEED_DEMO=false
SETU_OTP_BYPASS=false

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

**`APP_DEBUG=false` is not optional.** Laravel's debug error page prints the
entire environment — every key above, including the payment credentials — to
anyone who can trigger a 500.

**`SETU_SEED_DEMO=false`** keeps forty invented members out of the real
database. It is the default in production; setting it explicitly means
nobody has to remember what the default was.

---

## 5. First boot

Watch the deploy log. The entrypoint prints what it is doing:

```
==> SheTu starting on port 8080
==> Waiting for the database
    connected
==> Migrating
==> Empty database, seeding
==> Caching config, routes and views
==> Handing over to Apache
```

**Copy the generated password.** With `SETU_SEED_PASSWORD` unset, the seeder
generates one, prints it once, and stores only its hash. There is no way to
recover it afterwards — reseeding is the only alternative, and that means
losing whatever is in the database.

The staff accounts it creates:

| | |
|---|---|
| `admin@setu.test` | admin — moderation, appearance, word list, slides |
| `mod@setu.test` | moderator |
| `ghotok@setu.test` | matchmaker console |
| `privacy@setu.test` | privacy officer |

Change those addresses to real ones before anyone else has the URL.

---

## 6. Check it worked

- `/` loads and the slideshow runs
- `/admin` accepts the admin login
- `/register` completes — including the photograph upload, which proves
  object storage is wired correctly
- A registered profile appears in `/admin/moderation` and is **not** in
  `/search` until approved

That last one is the whole publication model. If a new profile shows up in
search without approval, stop and work out why.

---

## What is likely to go wrong first

**The image has never been built.** There is no Docker on the machine this
was developed on, so Railway's build is genuinely the first time that
Dockerfile runs. Read the build log rather than assuming.

**Prepared-statement errors** mean the transaction pooler. Move to port 5432.

**`FATAL: APP_KEY is not set`** is the container refusing to start rather
than inventing a key. Set it.

**Photographs upload but do not display** means the buckets exist but the
credentials or endpoint are wrong — the write is succeeding into a local
path that then vanishes. Check `MEDIA_DRIVER=s3` is actually set.

---

## Local development is unchanged

SQLite, no S3, demo data on, OTP skipped:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8000
```

Everything seeded uses the password `password` outside production.
