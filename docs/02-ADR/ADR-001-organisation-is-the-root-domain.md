# ADR-001: Organisation Is the Root Domain

- Status: Accepted
- Date: 2026-07-15
- Scope: Domain model, tenancy, administration, provider ingestion

## Context

Encore serves many kinds of live-event operators. Ticketing customers are only one subset. Naming the tenant after a customer account or a provider would incorrectly bind the core model to a sales or integration concept.

## Decision

Use `Organisation` as the root ownership entity.

Users, shows, and venues belong directly to an organisation. Performances, invitations, and reviews inherit organisation ownership through their show. Encore super administrators are global users and therefore have a null `organisation_id`.

Provider APIs accept Encore `organisation_id` where ownership must be assigned. Provider names and IDs remain integration metadata.

## Consequences

- Customer data can be scoped through one stable tenant identifier.
- Theatre companies, venues, festivals, schools, colleges, touring companies, promoters, and music organisations fit the same core model.
- Super-administration and support tools manage organisations even though customer-facing URLs may use the word `accounts`.
- All new tenant-sensitive queries must explicitly enforce organisation scope until a centralized tenant mechanism exists.
- A future concept such as billing account or workspace must be modeled separately rather than overloading Organisation.

## Alternatives considered

### Client account as the ownership root

Rejected because it frames every tenant as a commercial account and does not fit venues, schools, colleges, festivals, or other organisations cleanly.

### TicketPal customer as the ownership root

Rejected because it would couple Encore's core model to one provider and exclude organisations sourced elsewhere.

### Direct ownership on every record

Rejected as the primary model because duplicating organisation ownership across every descendant would create consistency risks. Reviews and invitations derive ownership through performance and show.
