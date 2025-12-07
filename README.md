# CaseWatch: AML/KYC reviewer console (API)

Laravel 12 backend for a transaction-monitoring case queue. A deterministic risk engine turns wallet activity into scored, explainable alerts, and analysts work them through a disposition workflow backed by an append-only audit trail.

The frontend (Next.js) lives in a sibling web package and consumes this API. Multi-tenant isolation is enforced at the query layer, counterparty PII is encrypted at rest, and audit rows are append-only: inserted, never updated or deleted.

## How alerts get scored

Each rule runs over one counterparty's transaction history and, if it fires, contributes a weighted finding. An alert aggregates the findings for a counterparty: its score is the capped sum of weights, its type is the heaviest finding.

The rules right now: STRUCTURING fires on three or more transfers just under the $10k reporting threshold within a week (weight 45). LAYERING fires when funds fan in from two or more sources and out to two or more destinations (weight 40). RAPID_MOVEMENT fires when most of an inflow leaves again within a day of arriving (weight 35). HIGH_RISK_JURISDICTION fires on a large transfer to or from a counterparty registered in a flagged country (weight 30).

A score of 70 or above is HIGH severity, 40 or above is MEDIUM, everything else is LOW. Alerts are deduplicated per counterparty, so re-running ingest is idempotent.

## API

Auth is Sanctum bearer tokens. All alert routes are tenant-scoped to the caller's organisation.

```
POST /api/auth/register        create org + first user (lead), returns token
POST /api/auth/login           returns token
POST /api/auth/logout          revoke tokens

GET  /api/alerts               queue, ordered by score; ?status= ?severity=
GET  /api/alerts/{id}          alert + counterparty + audit trail
GET  /api/alerts/{id}/assessment   structured, explainable risk breakdown
GET  /api/alerts/{id}/sar          Suspicious Activity Report draft
GET  /api/alerts/{id}/audit        append-only event log
POST /api/alerts/{id}/disposition  { action: clear|escalate|assign, note?, assignee? }
```

Clear and escalate require the lead role; analysts get a 403. Every disposition writes an audit event in the same transaction as the state change.

## Running it

```bash
docker compose up -d              # Postgres 16 on :55437
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed        # seeds one demo tenant with alerts

php artisan serve                 # http://127.0.0.1:8000
```

Demo logins (password: password): lead@casewatch.test, analyst@casewatch.test.

## Quality gates

```bash
composer ci        # pint --test + phpstan (level max) + pest
composer lint      # pint --test
composer analyse   # phpstan level max
composer test      # pest
```

Tests run against an in-memory SQLite database, no services needed; the app itself runs on Postgres. PHPStan is set to level max with Larastan.
