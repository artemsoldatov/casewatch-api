# CaseWatch: AML/KYC reviewer console (API)

Laravel 12 backend for a transaction-monitoring case queue. A deterministic risk engine turns wallet activity into scored, explainable alerts, and analysts work them through a disposition workflow backed by an append-only audit trail.

The frontend (Next.js) lives in a sibling web package and consumes this API. Multi-tenant isolation is enforced at the query layer, counterparty PII is encrypted at rest, and audit rows are append-only: inserted, never updated or deleted.

## How alerts get scored

Each rule runs over one counterparty's transaction history and, if it fires, contributes a weighted finding. An alert aggregates the findings for a counterparty: its score is the capped sum of weights, its type is the heaviest finding.

The rules right now: STRUCTURING fires on three or more transfers just under the $10k reporting threshold within a week (weight 45). LAYERING fires when funds fan in from two or more sources and out to two or more destinations (weight 40). RAPID_MOVEMENT fires when most of an inflow leaves again within a day of arriving (weight 35). HIGH_RISK_JURISDICTION fires on a large transfer to or from a counterparty registered in a flagged country (weight 30).

A score of 70 or above is HIGH severity, 40 or above is MEDIUM, everything else is LOW. Alerts are deduplicated per counterparty, so re-running ingest is idempotent.
