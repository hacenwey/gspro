# Migrations

Forward-only SQL migrations for V2+.

## Layout

- `master/` — runs against the master DB (tenant registry, tickets, polar, etc.)
- `tenant/` — runs against each tenant DB

File naming: `YYYYMMDDHHMM_slug.sql` (sortable).

## Authoring rules

- No rollback. Fix forward with another migration.
- Write idempotent SQL when possible: `CREATE TABLE IF NOT EXISTS`, guard `ALTER TABLE ADD COLUMN` with `INFORMATION_SCHEMA` checks, etc.
- Don't rely on app-level code — pure SQL.
- One statement per SQL file is safest; multi-statement files are split on `;\n` (no stored proc support).

## Running

From admin: `GET /admin/migrations/run?key=<MIGRATE_KEY>`
For a single tenant: `GET /{slug}/migrations/run?key=<MIGRATE_KEY>`

On bootstrap of a **new** tenant, `database/schema.sql` is applied and then migrations are baselined (marked applied without running). Only migrations added *after* the tenant's creation date actually execute on them.

## Baselining existing tenants

The runner's `baseline()` method stamps every discovered migration as applied without running them. Use this once per existing tenant DB right after introducing the runner, then add new migrations normally.
