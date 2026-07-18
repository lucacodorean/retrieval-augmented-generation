# RAG Retrieval, Agent, And API Session

## What Was Added

The initial RAG index now has a read-only retrieval layer. `RagRetriever` embeds a
natural-language query, searches the model's Qdrant collection, and reloads current
SQL records from returned metadata IDs.

`RagSearchable` lets a domain model declare its collection, source-record loader, and
result resource. Vehicles use `vehicle-documents` and map results through
`VehicleRagResultResource`.

## Agent

`VehicleAgent` uses local Ollama `qwen3:8b`. It has one read-only native Neuron tool:
`vehicle_search`.

The tool searches globally available vehicles and returns ranked records. The agent
response has one stable envelope:

```php
[
    'response' => [
        'natural-lang' => 'Generated explanation.',
        'serialized' => [
            ['record' => [], 'score' => 0.0],
        ],
    ],
]
```

The generated explanation comes from the model. Serialized records come from Neuron
resources and are safe for a frontend or other machine consumer.

## Tool Result Capture

Neuron executes provider-mapped tool instances. The agent therefore reads executed
`ToolResultMessage` entries from chat history rather than relying on mutable state on
its registered tool instance. This fixes empty `serialized` results after live calls.

## API

The vehicle agent endpoint is grouped under the API vehicle prefix:

```text
POST /api/vehicles/ask
{ "query": "Which electric vehicles are available?" }
```

It validates the query and returns the agent response envelope as JSON.

## Filter Experiment

Optional Qdrant metadata filters were added briefly, then intentionally removed.
Semantic search is the current minimal contract:

```text
vehicle_search(query, limit?)
```

Exact filters remain a future capability. They are useful for hard constraints but
are not required for semantic retrieval and complicate the first agent tool call.

## Current Boundary

The vehicle agent is global and read-only. It has no mutation tools and does not
expose owner data in serialized vehicle results.

## Next Work

- Add exact metadata filters when a deliberate structured-query contract is needed.
- Add entities such as `Fix` using their own documents, resources, collections,
  search tools, and agents.
- Add authentication and tenant scoping before exposing non-global data.
