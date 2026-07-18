# ADR 0009: Use a Qdrant Collection Per Documentable Model

## Status

Accepted

## Context

Vehicle documents and future model documents must remain in separate vector indexes.

## Decision

Add `ragCollection(): string` to `Documentable`. Each documentable model declares
its Qdrant collection, such as `vehicle-documents` or `fix-documents`. A resolver
creates a Qdrant store for the requested collection using the shared embedding
dimension and connection settings.

## Consequences

Models are isolated in Qdrant while sharing one server and Ollama embedding model.
Cross-model semantic search requires an explicit multi-collection retrieval tool.
