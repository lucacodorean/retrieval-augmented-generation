# Local RAG Infrastructure Design

## Goal

Provide a reproducible local vector store through DDEV while using the existing
macOS Ollama installation to embed documents with `nomic-embed-text`.

## Services

Qdrant runs as a DDEV-managed Docker Compose service with persistent storage. Laravel
connects to it through the DDEV service hostname. The Qdrant dashboard is exposed on
a local host port for inspection.

Ollama remains on the macOS host. From the DDEV web container Laravel reaches it at
`http://host.docker.internal:11434/api`. The configured embedding model is
`nomic-embed-text`, which produces 768-dimensional embeddings.

## Application bindings

`config/rag.php` holds the Ollama URL/model and Qdrant collection URL/key/dimension.
`AppServiceProvider` explicitly constructs Neuron's `OllamaEmbeddingsProvider` and
`QdrantVectorStore` from this configuration. Generic environment class-name bindings
are removed.

The Qdrant collection dimension is 768 and must remain aligned with the configured
Ollama model.

## Vector identifiers

The application continues to use namespaced string keys, such as `vehicle:42`, as
its document source key. The Qdrant adapter deterministically converts that key to a
UUIDv5 point ID because Qdrant point IDs require UUIDs or integers. The source key
remains in Qdrant metadata for deletion and traceability.

## Backfill and verification

An Artisan command dispatches an upsert job for every existing documentable vehicle.
It is safe to run repeatedly because synchronization replaces vectors by stable key.

Verification covers Qdrant/Ollama configuration, backfill job dispatch, and a local
manual check that a queued vehicle upsert appears in Qdrant after the queue worker
processes it.
