# ADR-002: UUID Primary Keys

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

- Status: Accepted
- Date: 2026-07-15
- Scope: Persistence and API identifiers

## Context

Encore receives data from external systems and exposes identifiers across integration and public workflows. Domain identifiers must remain independent of database insertion order and provider identifiers.

## Decision

Use UUID primary keys for organisations, shows, venues, performances, review invitations, reviewers, and reviews. Eloquent models generate them through `HasUuids`.

Laravel administrative users retain the framework's numeric user primary key. Provider identifiers are stored in dedicated columns and are not used as Encore primary keys.

## Consequences

- Encore records have provider-neutral identities.
- Integration responses can safely expose non-sequential identifiers.
- Foreign keys for domain records use UUID columns.
- Code and validation must not assume every model uses the same primary-key type; users are the current exception.

## Alternatives considered

### Auto-incrementing integers for every table

Rejected for domain records because sequential IDs expose insertion order and are less suitable for identities exchanged across providers and environments.

### Provider identifiers as primary keys

Rejected because provider identifiers are externally controlled, provider-scoped, and incompatible with Encore's provider-neutral identity model.

### ULIDs

Not selected. ULIDs would provide sortable identifiers, but UUID support was already established consistently across the domain and no ordering requirement justified a second identifier strategy.
