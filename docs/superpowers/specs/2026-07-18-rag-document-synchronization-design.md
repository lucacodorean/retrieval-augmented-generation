# RAG Document Synchronization Design

## Goal

Keep vector-store documents eventually consistent with opted-in Eloquent models after
database transactions commit. The design must let new model types, such as `Fix`,
join without changing the shared synchronization pipeline.

## Opt-in model contract

`Documentable` is an explicit contract for models that have a RAG representation.
It identifies the transformer responsible for that model type.

`SyncsDocuments` is a trait used only by `Documentable` models. Its
`bootSyncsDocuments` method registers `DocumentObserver`. No base `DocumentModel` is
introduced, so models remain free to use other domain base classes and
non-documentable models are never indexed by accident.

To add a future `Fix` model, implement `Documentable`, use `SyncsDocuments`, create
`FixRagDocumentTransformer`, and test its document representation. The generic
observer and jobs remain unchanged.

## Lifecycle flow

`DocumentObserver` implements `ShouldHandleEventsAfterCommit`.

- `created` and `updated` dispatch `UpsertRagDocument` with the model class and ID.
- `deleted` dispatches `DeleteRagDocument` with the model class and ID.

Both jobs are queued. The upsert job reloads the model with relationships required
by its transformer, creates its `Document`, embeds the document content, and upserts
the vector with a stable application key made from model type and model ID, such as
`vehicle:42`. The delete job uses the same key to remove the vector. A vector-store
adapter may convert this key to a backend-specific native ID, including UUIDv5 when a
backend requires UUID point IDs.

The observer dispatches jobs directly. Dedicated custom events are not needed until
another consumer, such as an audit trail or analytics pipeline, must react to the
same lifecycle change.

## Dependent document updates

`VehicleDetails` is not itself documentable, but its fields appear in vehicle
documents. Its observer queues one `UpsertRagDocument` job for every related vehicle
after details are created or updated. The database foreign key prevents deleting
details that vehicles still reference, so no deletion fan-out is needed.

## Consistency and failure handling

The relational database is the source of truth. Vector synchronization is
asynchronous and eventually consistent: a committed change can have a short delay
before semantic search reflects it. Queue retries handle transient embedding or
vector-store failures without rolling back the relational transaction.

The eventual vector backend and embedding provider are configuration concerns. Jobs
depend on abstractions for embedding and vector upsert/delete, not a concrete vendor.

## Tests

Tests will verify:

- only `Documentable` models register document synchronization;
- lifecycle actions dispatch the correct jobs after commit;
- upserts resolve the model's transformer, embed content, and use stable IDs;
- deletes use the same stable IDs;
- a `VehicleDetails` change queues one upsert for each related vehicle; and
- queue retries do not alter the committed relational data.
