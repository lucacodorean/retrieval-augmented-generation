# Vehicle Search Filters Design

## Goal

Allow the read-only vehicle agent to combine semantic similarity with optional exact
vehicle metadata constraints: fuel, brand, model, and inclusive horsepower bounds.

## Data Flow

`VehicleSearchTool` accepts the optional tool properties. It converts `fuel` and
`brand` using the existing backed enums, preserves a supplied model string, and
validates non-negative horsepower bounds with `min_hp <= max_hp`. It passes only
canonical payload values to `SearchModelTool`, then to `RagRetriever`.

`RagRetriever` still embeds the natural-language query and resolves the model's
collection. It forwards the optional structured filters to the Qdrant document-store
adapter without changing how it limits or orders returned documents.

## Qdrant Query

The adapter keeps the existing unfiltered Neuron vector-store call when no filters
are supplied. When filters exist, its Qdrant-specific store sends a `points/query`
similarity request with `filter.must` clauses:

- `fuel`, `brand`, and `model` use exact `match.value` clauses.
- Both horsepower bounds become one `hp` `range` clause using `gte` and/or `lte`.

All clauses are applied with the similarity query, so Qdrant ranks only eligible
vehicles. The application-owned Qdrant store subclass owns this extension rather
than changing Neuron package source.

## Agent And Errors

Vehicle-agent instructions direct the model to populate a filter whenever the user
states a supported constraint, while retaining natural language in `query` for
semantic intent. Invalid enum values and invalid horsepower ranges cause tool input
validation to fail before retrieval or Qdrant access.

## Tests

Test-first coverage verifies canonical enum forwarding, valid combined filters,
invalid enum and range rejection, unfiltered retrieval compatibility, propagation
through each pipeline boundary, and Qdrant request serialization for exact and
inclusive range clauses.
