# Setu — matrimonial platform with a walled Connect mode

A Laravel 11 application built from the master build specification
(*Matrimonial and Dating Platform — Master Build Specification*, 94 pages,
27 chapters). Bilingual Bangla/English, MySQL 8, no JavaScript framework.

Two products live in one codebase and are deliberately kept apart:

| | Matrimonial | Connect |
|---|---|---|
| Audience | families and candidates | individuals, 18+ |
| Tables | `profiles`, `photos`, `interests`, … | `connect_profiles`, `connect_photos`, `connect_likes`, … |
| Photo storage | `photos` disk | `connect_photos` disk, separate prefix |
| Guardians | full dashboard, three levels | **no access at any level** |
| Ghotoks | consent-scoped access | **cannot see it exists** |
| Search engines | public profiles indexable | `noindex, nofollow, noarchive` |
| Notifications | SMS, email, push | push only, never named |

The separation is by **separate tables**, not a `mode` column. A mode column
works until the one query that forgets to filter on it, and that query is a
cross-mode leak that puts somebody in a dating product in front of their
family. See `app/Services/ConnectWall.php`.

---

## Requirements

- PHP 8.2 or newer, with `mbstring`, `intl`, `pdo_mysql`, `gd` (or `imagick`), `bcmath`, `openssl`
- Composer 2
- MySQL 8.0 (or MariaDB 10.6+)

## Setup

```bash
composer install

cp .env.example .env
php artisan key:generate
```

Create the database with a Bangla-safe collation — this matters, `utf8mb4`
with the wrong collation sorts Bangla incorrectly and truncates on some
emoji:

```sql
CREATE DATABASE setu
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Point `.env` at it, then:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open <http://localhost:8000>.

### Demo accounts

Every seeded account uses the password `password`.

| Role | Email | What to look at |
|---|---|---|
| Member | `demo1@setu.test` | dashboard, search, privacy screen |
| Member (private) | `demo7@setu.test` | a profile with photos hidden — the default |
| Guardian | `demo11@setu.test` | family dashboard at L1, L2 and L3 |
| Ghotok | `ghotok@setu.test` | a case with no live consent — the gate |
| Moderator | `mod@setu.test` | photo and advertisement queues |
| Privacy officer | `privacy@setu.test` | the only role that can read Connect membership |
| Admin | `admin@setu.test` | consent-violation counter, success fees, SEO |

The seeder deliberately leaves **two profiles unconfirmed** so the consent
gate is visible: they are registrant-created, they do not appear in search,
and an operator cannot open them. It also seeds **no Connect data at all** —
Connect is opt-in per person behind an explicit consent, and seeding it
would put demo people in a dating product they never joined. Create one by
hand at `/connect/start` after enabling it on an account.

---

## Layout

```
app/
  Http/
    Controllers/    Public, Auth, Member, Family, Operator, Admin, Connect
    Middleware/     SetLocale, EnsureConnectEnabled, EnsureGuardian,
                    EnsureOperator, EnsureStaff
  Models/           60 models
  Services/         the ten places the rules actually live
config/setu.php     plans, match weights, SLAs, retention windows
database/
  migrations/       11 files, ~68 tables
  seeders/          geo (8 divisions, 64 districts), plans, staff, demo, content
lang/{bn,en}/       748 keys each, symmetric
resources/views/    67 Blade templates
routes/web.php      108 routes
```

### The ten services

| Service | Responsibility |
|---|---|
| `VisibilitySerializer` | **the single disclosure path.** Every cross-person render goes through it. `NEVER_IN_PROFILE` is enforced here, not at the call site. |
| `ConnectWall` | cross-mode reads, photo-reuse warnings, person-level bans, Connect-only deletion |
| `ConsentGate` | operator access; writes an `operator_access_logs` row on **every** view, with `consent_present` |
| `ContactMasker` | phone/email/URL/social masking, including Bengali digits `[০-৯]{9,15}` |
| `MatchScore` | harmonic mean of both directions, hard gates return `DISQUALIFIED` |
| `DeckGenerator` | Connect suggestions: 30-day repeat exclusion, anti-spray penalty |
| `LandingPageService` | programmatic SEO, auto-`noindex` below the profile threshold |
| `PaymentGateway` | idempotent `verifyAndActivate` |
| `OtpService` / `SmsSender` | OTP issue/verify; the sender throws if a Connect SMS names the mode |

### Invariants worth knowing before you edit anything

1. **Contact details are not profile fields.** `mobile`, `email` and `social`
   are in `VisibilitySerializer::NEVER_IN_PROFILE` and cannot be reached
   through any visibility level, including `SELF`. A number becomes visible
   only through `contact_exchanges`, and only when both people agree.
2. **Private access is mutual.** `PrivateAccess::grantMutually()` writes both
   directions in one call. There is no one-sided grant.
3. **A guardian can never** read messages, act on an interest, edit a
   profile, upload a photo, or see anything from Connect — at any level.
   `GuardianLink::may()` returns `false` for those capabilities as literals,
   not as settings.
4. **A success fee needs two people.** `SuccessFee::confirm()` throws if the
   confirmer is the person who recorded it.
5. **Quotas consume under a row lock.** `Entitlement::consume()` runs
   `lockForUpdate()` inside a transaction.
6. **A photo can only be uploaded by the profile's owner.** Enforced as a
   model `creating` hook in `AppServiceProvider`, so it holds for seeders and
   tinker too.
7. **Advertisers who tick "no media" are invisible to ghotok accounts.**
   A global scope on `ClassifiedAd` for the `OPERATOR` role.

## Tests

```bash
php artisan test
```

Four feature test files, covering the wall, the consent gates, disclosure,
and quotas. They are the executable form of the invariants above — if one
fails, a promise the product makes to its members is broken.

## Scheduled work

`routes/console.php` registers the retention and freshness jobs. Add one
cron entry:

```
* * * * * cd /path/to/setu && php artisan schedule:run >> /dev/null 2>&1
```

| Job | Cadence | Why |
|---|---|---|
| Purge KYC documents | daily, 30-day window | the verified/not-verified result is kept; the image is not |
| Purge biodata drafts | daily, 90 days | anonymous drafts, no account attached |
| Purge deleted Connect data | daily, 7 days | Connect deletion is a real deletion |
| Expire classified advertisements | daily | 30-day run |
| Refresh landing-page counts | hourly | drives the auto-`noindex` threshold |

## Configuration you will want to change

Everything commercial is in `config/setu.php` — plan features, the ghotok
deposit (`success_deposit_bdt`), match weights, moderation SLAs, retention
windows, and the landing-page threshold. Prices are in the `plans` table
because finance edits those; the rules they buy are in config because
engineering owns those.

Match weights deliberately **exclude complexion**. The field exists on
`profiles` because members expect to fill it in; it is not scored, because
the product should not amplify the preference.

## Payments

`.env` carries blocks for SSLCOMMERZ, bKash and Stripe. `PaymentGateway` is
written against an interface with an idempotent `verifyAndActivate`; the
sandbox credentials go in `.env`, and nothing in the codebase assumes a
particular provider beyond that method.

## What is not built

Honest list, so you are not surprised:

- Live payment gateway integration — the interface and the idempotency are
  there, the provider SDK calls are stubs.
- SMS delivery — `SmsSender` logs in `local`; wire a provider in `services.php`.
- Image processing runs through `intervention/image`; blur variants are
  generated on upload, but there is no CDN or signed-URL layer.
- Push notifications for Connect — the notification rows are written, the
  transport is not.
- Admin analytics beyond the counters on the operations screen.
