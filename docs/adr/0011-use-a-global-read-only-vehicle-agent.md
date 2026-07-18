# ADR 0011: Use a Global Read-Only Vehicle Agent

## Status

Accepted

## Context

The application needs natural-language questions over the full vehicle domain before
adding user-scoped access or mutation capabilities.

## Decision

Create a `VehicleAgent` powered by local Ollama `qwen3:8b` and give it only a
read-only `VehicleSearchTool`. Keep generation configuration separate from the Ollama
embedding model.

## Consequences

The agent can reason over globally searchable vehicles but cannot change records or
access Qdrant directly. Its backend response includes generated prose and serialized
resource results captured from tool calls.
