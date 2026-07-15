# ADR-003: PostgreSQL Selected for Encore

- Status: Accepted
- Date: 2026-07-15
- Scope: Persistent application data

## Context

Encore requires relational integrity across organisations, shows, venues, performances, invitations, reviewers, and reviews. Synchronization also depends on transactions, row locking, foreign keys, composite uniqueness, timestamps, and JSON metadata.

The Docker Compose runtime provisions PostgreSQL 18 and the active development environment uses Laravel's `pgsql` connection. PHPUnit uses in-memory SQLite for fast automated tests, but SQLite is not the selected deployed database.

## Decision

Use PostgreSQL as Encore's authoritative relational database.

Schema changes must be delivered through Laravel migrations and remain valid for PostgreSQL. Production data, migrations, and operational recovery procedures must be designed around PostgreSQL semantics. SQLite remains a test convenience and must not be treated as proof of PostgreSQL-specific behavior.

## Consequences

- Foreign keys and uniqueness constraints enforce core identity and ownership rules.
- Transactional workflows can use row locking for invitation consumption and provider synchronization.
- JSON columns can retain provider metadata without moving the core relational model into documents.
- Engineering must test database-specific migrations against PostgreSQL before deployment.
- Operators must provide PostgreSQL backups, retention, recovery testing, monitoring, and secure credentials; the repository does not automate these controls.
- Differences between SQLite tests and PostgreSQL remain a risk until PostgreSQL integration tests are added.

## Alternatives considered

### SQLite as the primary database

Rejected for deployed operation. It is useful for isolated tests and simple local execution but is not the intended concurrent service database.

### MySQL or MariaDB

Viable relational alternatives, but not selected. The Compose topology, active configuration, migrations, and operational workflow are already aligned to PostgreSQL.

### Document database

Rejected because Encore's ownership graph, referential actions, uniqueness rules, and transactional invitation workflow are naturally relational.
