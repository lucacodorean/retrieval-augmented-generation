# ADR 0002: Transform Vehicles Into RAG Documents

## Status

Accepted

## Context

Future RAG ingestion needs semantically meaningful content and structured metadata,
but the Eloquent models should not depend on a future vector-store workflow.

## Decision

Use `app/Rag/Contracts/VehicleRagDocument` to create one
`NeuronAI\RAG\Document` per vehicle. The transformer combines individual vehicle
attributes with its shared vehicle-details attributes. Content is readable prose;
metadata contains stable IDs and filterable vehicle fields. Its document ID is the
deterministic namespaced key `vehicle:{vehicle ID}`.

## Consequences

The RAG representation is isolated from persistence models and can be independently
tested. Future ingestion jobs can call the transformer without duplicating formatting
logic.
