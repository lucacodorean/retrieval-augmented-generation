# Local RAG Infrastructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Connect the RAG synchronization pipeline to local host Ollama and DDEV-managed Qdrant.

**Architecture:** Qdrant runs as a persistent DDEV service while Laravel accesses macOS Ollama through `host.docker.internal`. Configuration-backed factories construct Neuron providers. A Qdrant adapter maps application keys such as `vehicle:42` to deterministic UUIDv5 point IDs, and a backfill command queues existing vehicles.

**Tech Stack:** DDEV Docker Compose, Qdrant, Ollama `nomic-embed-text`, Laravel 13 queues, Neuron AI.

---

### Task 1: Add Qdrant to DDEV

**Files:**
- Create: `.ddev/docker-compose.qdrant.yaml`
- Modify: `.env.example`

- [ ] **Step 1: Write a failing infrastructure configuration test**

Create `tests/Unit/Rag/RagConfigurationTest.php` asserting `config('rag.ollama.model')` is `nomic-embed-text`, `config('rag.qdrant.dimension')` is `768`, and collection configuration is non-empty.

- [ ] **Step 2: Run the test to verify it fails**

Run: `ddev php artisan test tests/Unit/Rag/RagConfigurationTest.php`

Expected: FAIL because Ollama and Qdrant configuration keys do not exist.

- [ ] **Step 3: Add the Qdrant service and environment values**

Create `.ddev/docker-compose.qdrant.yaml`:

```yaml
services:
  qdrant:
    image: qdrant/qdrant:v1.13.4
    container_name: ddev-${DDEV_SITENAME}-qdrant
    labels:
      com.ddev.site-name: ${DDEV_SITENAME}
      com.ddev.approot: $DDEV_APPROOT
    volumes:
      - qdrant_storage:/qdrant/storage
    ports:
      - "6333:6333"
      - "6334:6334"
volumes:
  qdrant_storage:
    name: ddev-${DDEV_SITENAME}-qdrant-storage
```

Add these defaults to `.env.example`:

```dotenv
RAG_OLLAMA_URL=http://host.docker.internal:11434/api
RAG_OLLAMA_MODEL=nomic-embed-text
RAG_QDRANT_COLLECTION=vehicle-documents
RAG_QDRANT_URL=http://qdrant:6333/collections/${RAG_QDRANT_COLLECTION}/
RAG_QDRANT_KEY=
RAG_EMBEDDING_DIMENSION=768
```

- [ ] **Step 4: Restart DDEV and verify Qdrant**

Run: `ddev restart`

Run: `ddev exec curl --fail http://qdrant:6333/readyz`

Expected: exit code 0.

- [ ] **Step 5: Commit the local Qdrant service**

```bash
git add .ddev/docker-compose.qdrant.yaml .env.example tests/Unit/Rag/RagConfigurationTest.php
```

### Task 2: Bind Ollama and Qdrant concretely

**Files:**
- Create: `app/Rag/QdrantDocumentStore.php`
- Modify: `config/rag.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Rag/QdrantDocumentStoreTest.php`

- [ ] **Step 1: Write failing binding and ID tests**

Assert provider resolution produces `OllamaEmbeddingsProvider` with the configured model and URL. Assert `QdrantDocumentStore::pointId('vehicle:42')` is a deterministic UUID and differs from `pointId('fix:42')`.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `ddev php artisan test tests/Unit/Rag/RagConfigurationTest.php tests/Unit/Rag/QdrantDocumentStoreTest.php`

Expected: FAIL because concrete bindings and Qdrant key mapping do not exist.

- [ ] **Step 3: Implement concrete configuration and bindings**

Expand `config/rag.php` to provide `ollama.url`, `ollama.model`, and `qdrant.collection`, `qdrant.url`, `qdrant.key`, and `qdrant.dimension` values from the environment.

Replace generic class-name bindings in `AppServiceProvider` with:

```php
$this->app->singleton(EmbeddingsProviderInterface::class, fn () =>
    new OllamaEmbeddingsProvider(
        config('rag.ollama.model'),
        config('rag.ollama.url'),
    ),
);
```

Bind `VectorStoreInterface` to `QdrantDocumentStore`, which wraps Neuron's
`QdrantVectorStore`. Its `pointId(string $key): string` derives a UUIDv5 from a
fixed application namespace and `$key`; its add method replaces `Document::$id` with
that UUID before delegation while retaining the original key in `sourceName`.

- [ ] **Step 4: Run binding and ID tests to verify they pass**

Run: `ddev php artisan test tests/Unit/Rag/RagConfigurationTest.php tests/Unit/Rag/QdrantDocumentStoreTest.php`

Expected: PASS.

- [ ] **Step 5: Commit concrete RAG bindings**

```bash
git add app/Rag/QdrantDocumentStore.php app/Providers/AppServiceProvider.php config/rag.php tests/Unit/Rag
```

### Task 3: Backfill existing vehicle documents

**Files:**
- Create: `app/Console/Commands/BackfillRagDocuments.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Rag/BackfillRagDocumentsTest.php`

- [ ] **Step 1: Write the failing backfill command test**

Create three vehicles, fake the bus, run `rag:backfill-vehicles`, and assert one `UpsertRagDocument` job per vehicle ID. Assert the command reports the dispatched count.

- [ ] **Step 2: Run the test to verify it fails**

Run: `ddev php artisan test tests/Feature/Rag/BackfillRagDocumentsTest.php`

Expected: FAIL because the command does not exist.

- [ ] **Step 3: Implement the command**

Create a command with signature `rag:backfill-vehicles`. Iterate `Vehicle::query()->select('id')->cursor()`, dispatch `UpsertRagDocument(Vehicle::class, $vehicle->getKey())`, count dispatches, and output `Queued {count} vehicle documents.`. Register the command through Laravel command discovery or `routes/console.php`.

- [ ] **Step 4: Run backfill tests to verify they pass**

Run: `ddev php artisan test tests/Feature/Rag/BackfillRagDocumentsTest.php`

Expected: PASS.

- [ ] **Step 5: Verify the local end-to-end flow**

Run: `ddev php artisan migrate:fresh --seed`

Run: `ddev php artisan rag:backfill-vehicles`

Run: `ddev php artisan queue:work --once` repeatedly until queued jobs are processed.

Run: `ddev exec curl --fail http://qdrant:6333/collections/vehicle-documents`

Expected: Qdrant reports an existing collection with points.

- [ ] **Step 6: Run formatting and the full test suite**

Run: `ddev exec vendor/bin/pint app config routes tests`

Run: `ddev php artisan test`

Expected: both commands exit 0.

- [ ] **Step 7: Commit backfill support and documentation**

```bash
git add app/Console routes/console.php tests/Feature/Rag docs
```
