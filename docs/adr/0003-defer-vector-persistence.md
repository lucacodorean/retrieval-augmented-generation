# ADR 0003: Defer Embedding and Vector Persistence

## Status

Accepted

## Context

The immediate requirement is to prepare source documents, not to choose an embedding
provider, vector store, synchronization policy, or agent interface.

## Decision

This increment ends after document transformation. It will not configure an embedding
provider, persist vectors, add an ingestion command, or implement retrieval.

## Consequences

The implementation stays focused on correct source data and deterministic documents.
The eventual vector-store choice can determine dimensionality, metadata filtering,
upsert identifiers, and update/deletion synchronization without premature coupling.
