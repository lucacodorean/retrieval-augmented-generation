# Vehicle Agent Design

## Goal

Add the first model-specific agent: a global-scope vehicle agent powered by local
Ollama `qwen3:8b`. It answers natural-language vehicle questions by calling one
read-only vehicle-search tool.

## Agent Boundary

`App\Neuron\Agents\VehicleAgent` owns vehicle-question instructions and receives only
the vehicle-search capability. It has no direct access to Qdrant, Eloquent models, or
mutation tools.

The agent may decide whether search is needed. When it searches, the tool returns
current SQL-backed vehicle results ranked by semantic similarity.

## Tool Boundary

The existing `SearchModelTool` remains generic retrieval infrastructure.
`App\Neuron\Tools\VehicleSearchTool` adapts it to Neuron's native tool contract,
accepting a natural-language query and result limit. It is read-only and operates on
the global `vehicle-documents` collection.

## Generation

The agent uses local Ollama `qwen3:8b` for generation. This configuration is separate
from the `nomic-embed-text` embedding model. The agent must not use `qwen3:8b` for
vector embeddings.

## Response Contract

The agent returns a stable backend response envelope:

```php
[
    'response' => [
        'natural-lang' => 'Generated agent answer.',
        'serialized' => [],
    ],
]
```

When the agent calls vehicle search, `serialized` contains ranked results mapped by
the model's `App\Neuron\Resources` result resource. It is an empty array when the
agent does not search. The LLM produces `natural-lang`; it does not construct the
serialized result data.

This contract lets a frontend render reliable records from `serialized` without
parsing generated prose.

## Tests

Tests will verify agent construction, the registered tool set, tool arguments and
read-only search behavior, and an agent interaction that invokes vehicle search.
