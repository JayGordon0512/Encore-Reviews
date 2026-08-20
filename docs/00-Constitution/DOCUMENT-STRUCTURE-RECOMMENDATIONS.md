# Future Document Structure Recommendations

Encore exists to orchestrate the live entertainment ecosystem through trusted experiences and collective intelligence.

This document contributes to that purpose.

**Version:** 1.0

**Status:** Recommendation only

## Recommendation

Adopt the following structure incrementally, not through an immediate bulk move:

```text
docs/
├── 00-Constitution/
├── 01-Product/
├── 02-Architecture/
├── 03-ADR/
└── 04-Engineering/
```

## Intended Responsibilities

| Layer | Responsibility | Examples |
| --- | --- | --- |
| `00-Constitution/` | Highest governance, Purpose and enduring principles | Constitution, Core Purpose, Theory of Change, Manifesto, Trust Promise |
| `01-Product/` | Approved product direction and specifications | Platform Strategy, Product Blueprint, Audience Journey, capability specifications |
| `02-Architecture/` | System boundaries and architecture assessments | Architecture overview, security, tenancy, integration architecture |
| `03-ADR/` | Accepted and superseded architecture decisions | ADR-000 onward, ADR index and decision register |
| `04-Engineering/` | Delivery, API, domain, operations, roadmap and release practice | Engineering handbook, API contracts, domain guides, operations and releases |

## Why Files Should Not Move Yet

- The repository contains many internal links to current paths.
- Existing ADRs span two directories and have established references.
- Pull requests, external references and contributor habits may depend on current locations.
- Product Specifications need a status model before a new Product layer is useful.
- A bulk move would obscure substantive governance changes inside mechanical link churn.

## Suggested Migration Sequence

1. Establish `00-Constitution/` and the constitutional index without moving sources.
2. Require new documents to declare layer, status, owner and constitutional traceability.
3. Define the Product Specification lifecycle and decide which current Vision documents belong in `01-Product/`.
4. Consolidate architecture indexes and agree whether ADR-000 should join the numbered ADR sequence.
5. Produce a path mapping and validate every relative link before moving files.
6. Move one layer per dedicated documentation change, preserving Git history where practical.
7. Update contribution guidance, external links and governance checks after each layer migrates.

## Naming Recommendation

Use stable, descriptive filenames and retain ADR numbers. Avoid renaming source documents solely to make capitalisation uniform unless a controlled migration addresses every reference.

## Decision Required Before Restructuring

Founder, Product and Engineering should approve:

- the final layer definitions;
- the meaning and lifecycle of Product Specifications;
- whether roadmap and release records belong under Engineering or retain dedicated layers;
- the ADR consolidation approach;
- the migration and link-compatibility plan.

Until then, existing document paths remain authoritative and this structure is a recommendation only.
