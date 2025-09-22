# CaseWatch: AML/KYC reviewer console (API)

Laravel 12 backend for a transaction-monitoring case queue. A deterministic risk engine turns wallet activity into scored, explainable alerts, and analysts work them through a disposition workflow backed by an append-only audit trail.

The frontend (Next.js) lives in a sibling web package and consumes this API. Multi-tenant isolation is enforced at the query layer, counterparty PII is encrypted at rest, and audit rows are append-only: inserted, never updated or deleted.
