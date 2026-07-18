# RAG in a PHP Backend

## What RAG Is

Retrieval-augmented generation, or RAG, gives an AI system relevant external context
at request time. It retrieves likely useful information before a model produces an
answer.

RAG does not replace a database. It makes information discoverable by meaning, even
when a query does not use the exact words stored in a record.

## The Building Blocks

A RAG pipeline has five concepts:

- **Source of truth**: the authoritative business data, normally relational records.
- **Transformer**: converts a source record into a retrieval document.
- **Embedding**: converts document content into a numeric semantic vector.
- **Vector store**: persists vectors and finds nearby vectors for a query.
- **Retriever**: embeds a query, searches the vector store, and returns useful data.

```text
Source record -> Transformer -> Document -> Embedding -> Vector store
```

At query time the direction is reversed:

```text
User query -> Query embedding -> Vector search -> Relevant source records
```

## Source of Truth

The vector store is an index, not the authority. A relational database remains the
place where records are created, updated, validated, and deleted.

The vector store keeps enough document content and metadata to retrieve candidates.
After retrieval, the backend should load the current source records by their IDs
before returning an API response or giving context to an agent.

This avoids answering from stale vector metadata after a database record changes.

## Transformers

A transformer creates one RAG document from one domain record. It decides what the
embedding model should understand and which values should remain structured metadata.

In PHP with Neuron, a document is a `NeuronAI\RAG\Document`:

```php
$document = new Document(
    'Vehicle AB-123-CD is a Nissan Leaf with 150 hp and electric fuel.',
);

$document->addMetadata('vehicle_id', 42);
$document->addMetadata('fuel', 'electric');
```

`content` is readable text for semantic matching. Metadata is scalar data for
identity and exact filtering, such as IDs, categories, or ownership boundaries.

Do not include unnecessary sensitive data in document content. An embedding index is
not a reason to duplicate names, emails, passwords, or private fields.

## Embeddings

An embedding model maps text to an array of numbers. Text with similar meaning tends

For example, a query about an electric city car can retrieve a vehicle document that

This project uses Ollama with `nomic-embed-text`. Its output dimension is `768`, so
the matching Qdrant collection must also use dimension `768`.

## Vector Stores

Qdrant stores document vectors, content, and metadata. It performs similarity search
without the application loading every vector into PHP.

A collection groups vectors with compatible embedding dimensions and retrieval rules.
Documentable model types can use separate collections, such as `vehicle-documents`

Use stable document keys. This project uses keys such as `vehicle:42`; Qdrant maps
them to deterministic UUID point IDs while retaining the source key as metadata.

## Keeping the Index Current

Database changes and vector changes are separate operations. The normal PHP pattern
is an after-commit observer that queues synchronization work.

```text
Database transaction commits
  -> observer dispatches a job
  -> job transforms and embeds the record
  -> job upserts or deletes its vector
```

The queue makes vector synchronization eventually consistent. A temporary Ollama or
Qdrant failure can retry without rolling back the business transaction.

When a shared record contributes to many documents, its update must fan out. Updating
shared vehicle details therefore queues an update for every related vehicle document.

## Working With RAG in PHP

Use small boundaries:

- A model opts into RAG through `Documentable`.
- A transformer creates its `Document`.
- A synchronizer embeds and persists the document.
- A queue job performs synchronization after commit.
- A retrieval tool searches a selected collection and reloads current source records.

This separation keeps Eloquent models focused on domain data and keeps vector-store
or embedding-provider choices out of business logic.

## Local Runtime

Qdrant runs through DDEV. Ollama runs on the macOS host and is available to the DDEV
web container at `http://host.docker.internal:11434/api`.

```bash
ddev start
ddev php artisan queue:work
```

Open Qdrant at `http://localhost:6333/dashboard`.

## Tests

```bash
ddev php artisan test
```

## History

See `docs/history/2026-07-18-rag-foundation.md` for the session summary. Design and
implementation decisions are recorded in `docs/superpowers/` and `docs/adr/`.
