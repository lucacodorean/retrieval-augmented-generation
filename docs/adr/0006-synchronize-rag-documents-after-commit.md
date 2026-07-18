# ADR 0006: Synchronize RAG Documents After Commit

## Status

Accepted

## Context

Embedding and vector-store operations are external, potentially slow, and can fail.
They must not run for rolled-back database changes or make normal writes wait for
vector indexing.

## Decision

Register an after-commit document observer and dispatch queued upsert and delete
jobs. The jobs use stable model-type and model-ID keys for vector persistence and
retry transient failures.

## Consequences

Semantic search is eventually consistent with the relational database. The database
remains the source of truth, and failed vector synchronization can be retried without
undoing a successfully committed model change.
