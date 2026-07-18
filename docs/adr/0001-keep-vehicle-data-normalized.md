# ADR 0001: Keep Vehicle Data Normalized

## Status

Accepted

## Context

Vehicle specifications can be shared by many individual vehicles. RAG documents
will later represent those vehicles for retrieval.

## Decision

`VehicleDetails` is the shared specifications record. `Vehicle` holds the
`vehicle_details_id` foreign key and belongs to one details record. A details record
has many vehicles. RAG document text is not persisted in a separate SQL table.

## Consequences

Shared specifications are stored once, updates remain authoritative in the
relational database, and there is no document-copy synchronization work before a
vector store exists.
