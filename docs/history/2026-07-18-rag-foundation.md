# RAG Foundation Session

## What Happened

Vehicle data was normalized and converted into a local RAG pipeline. A vehicle and
its shared `VehicleDetails` become a Neuron document, Ollama embeds its text, and
Qdrant persists the vector.

```text
Vehicle + VehicleDetails -> VehicleRagDocument -> Ollama -> Qdrant
```

The relational database remains the source of truth.

## RAG Synchronization

- `Documentable` opts a model into RAG indexing.
- `SyncsDocuments` registers an after-commit observer.
- Queued upsert/delete jobs call `RagDocumentSynchronizer`.
- `VehicleDetails` changes fan out updates to every related vehicle document.
- Jobs retry three times; vector failures do not roll back database writes.

## Local Stack

- Qdrant runs in DDEV with persistent storage.
- Ollama runs on macOS and is reached at `http://host.docker.internal:11434/api`.
- The embedding model is `nomic-embed-text`, dimension `768`.
- Stable source keys look like `vehicle:42`; deterministic UUIDv5 IDs are used for
  Qdrant point IDs.

## Collections

Each documentable model declares `ragCollection()`. Vehicles use
`vehicle-documents`; future models such as `Fix` can use independent collections.

## Commands

```bash
ddev start
ddev php artisan migrate:fresh --seed
ddev php artisan rag:backfill-vehicles
ddev php artisan queue:work
ddev php artisan test
```

The Qdrant dashboard is available at `http://localhost:6333/dashboard`.

## Next Step

Build a retrieval tool that embeds a query with Ollama, searches the requested model
collection in Qdrant, loads the current relational records, and returns JSON. An
agent can call that tool later.

## Reference

See `docs/superpowers/specs/`, `docs/superpowers/plans/`, and `docs/adr/` for the
full design and decision record.
