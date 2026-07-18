# RAG Result Resources Design

## Goal

Move model-specific RAG search result serialization from Eloquent models into
independent Neuron resources while keeping authoritative-record reload, vector result
order, and similarity scores unchanged.

## Architecture

`RagSearchable` continues to declare collection routing and authoritative-record
loading, but replaces `toRagResult()` with `ragResultResource()`. The method returns a
resource class implementing a Neuron resource contract.

`VehicleResource` maps a loaded `Vehicle` and its eager-loaded details to the
existing safe response shape. It does not know about vector scores or retrieval order.

`SearchModelTool` resolves the configured resource for each current record and adds the
document score to its result. It processes documents in retrieved order and omits
missing source records, so the remaining results retain their original Qdrant ordering.

## Error Handling

The resource contract constrains configured result resources to a class that can turn
an Eloquent model into an array. The tool relies on this contract and does not add
fallback serialization.

## Tests

Tests first establish the missing resource class and contract as failures. Resource
tests cover the safe vehicle response fields; the search-tool test confirms resource
output plus score, missing-record omission, and unchanged Qdrant ordering.
