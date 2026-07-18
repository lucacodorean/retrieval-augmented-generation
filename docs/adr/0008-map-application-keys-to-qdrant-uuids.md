# ADR 0008: Map Application Keys to Qdrant UUIDs

## Status

Accepted

## Context

Document synchronization uses human-readable stable keys such as `vehicle:42`, while
Qdrant point IDs must be UUIDs or integers.

## Decision

Keep namespaced application keys as document source metadata and deterministically
map them to UUIDv5 point IDs at the Qdrant boundary.

## Consequences

Documents from different model types cannot collide. Upserts and deletes remain
idempotent without adding a UUID column to relational models.
