# ADR 0005: Use Opt-In Documentable Models

## Status

Accepted

## Context

Some Eloquent models need RAG documents, while others, including sensitive models,
must never be indexed. New documentable models will be added over time.

## Decision

Use a `Documentable` contract and `SyncsDocuments` trait rather than a shared
`DocumentModel` base class. A documentable model identifies its transformer and opts
into the generic observer and synchronization jobs.

## Consequences

New model types can join the RAG pipeline without modifying shared infrastructure.
Only explicit participants are indexed, and models remain free to use other base
classes or domain-specific behavior.
