# Model-Specific RAG Retrieval Design

## Goal

Provide read-only semantic retrieval tools that search one model's Qdrant collection,
then return current authoritative source records for use by model-specific agents.

## Components

`RagSearchable` is an opt-in model contract. It declares collection routing, source
record loading, and safe result mapping for one domain model.

`RagRetriever` is generic infrastructure. It embeds query text with Ollama, searches
the selected Qdrant collection, and returns documents with similarity scores.

`SearchModelTool` combines retrieval with a `RagSearchable` model. It obtains source
IDs from document metadata, loads current SQL records, preserves vector ranking, and
returns JSON-safe results without embeddings or raw internal document content.

`VehicleSearchTool` is the first model-specific configuration. Future models such as
`Fix` add their own adapter and tool while reusing the generic retriever.

## Access and Consistency

Agents receive only their allowed model-specific tools, not direct Qdrant access.
The tool is read-only. Mutation operations require separate tools with explicit
authorization and validation.

Qdrant determines candidate ordering, but SQL records are loaded before results are
returned. Deleted or unavailable source records are omitted without changing the
relative order of remaining results.

## Tests

Tests will cover query embedding, collection selection, metadata-ID extraction,
current-record loading, score preservation, missing source-record omission, and safe
vehicle result mapping.
