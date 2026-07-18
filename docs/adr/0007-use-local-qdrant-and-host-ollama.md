# ADR 0007: Use Local Qdrant and Host Ollama

## Status

Accepted

## Context

Local RAG development needs persistent vector search without paid external services.
Ollama with `nomic-embed-text` is already installed on the macOS host.

## Decision

Run Qdrant as a DDEV-managed service and connect to host Ollama from the DDEV web
container. Use `nomic-embed-text` with a Qdrant collection dimension of 768.

## Consequences

Qdrant setup is reproducible with the project while Ollama models remain local to the
developer machine. The DDEV environment requires host networking access to Ollama.
