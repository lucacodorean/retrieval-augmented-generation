# Per-Model Qdrant Collections Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Route each documentable model to its own Qdrant collection.

**Architecture:** `Documentable` declares its collection name. A Qdrant store resolver constructs a collection-specific `QdrantDocumentStore` from shared connection and dimension configuration; the synchronizer requests the store for the model rather than using one global binding.

**Tech Stack:** Laravel 13, Neuron AI, Qdrant, Ollama.

---

### Task 1: Add model collection declarations

**Files:**
- Modify: `app/Rag/Contracts/Documentable.php`
- Modify: `app/Models/Vehicle.php`
- Modify: `tests/Unit/Rag/DocumentableTest.php`

- [ ] **Step 1: Add a failing collection declaration assertion**

```php
$this->assertSame('vehicle-documents', Vehicle::ragCollection());
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `ddev php artisan test tests/Unit/Rag/DocumentableTest.php`

Expected: FAIL because `ragCollection()` does not exist.

- [ ] **Step 3: Add the contract and vehicle implementation**

```php
// Documentable
public static function ragCollection(): string;

// Vehicle
public static function ragCollection(): string
{
    return 'vehicle-documents';
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `ddev php artisan test tests/Unit/Rag/DocumentableTest.php`

Expected: PASS.

### Task 2: Resolve Qdrant stores per collection

**Files:**
- Create: `app/Rag/QdrantDocumentStoreResolver.php`
- Modify: `app/Rag/RagDocumentSynchronizer.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `config/rag.php`
- Modify: `.env.example`
- Test: `tests/Unit/Rag/QdrantDocumentStoreResolverTest.php`

- [ ] **Step 1: Write failing resolver tests**

Assert `forCollection('vehicle-documents')` builds a store with URL ending in `/collections/vehicle-documents/`, and `forCollection('fix-documents')` uses its own URL while sharing the configured key, dimension, and UUID namespace.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `ddev php artisan test tests/Unit/Rag/QdrantDocumentStoreResolverTest.php`

Expected: FAIL because the resolver does not exist.

- [ ] **Step 3: Implement the resolver and route synchronizer calls**

`QdrantDocumentStoreResolver::forCollection(string $collection): QdrantDocumentStore` constructs:

```php
new QdrantDocumentStore(
    collectionUrl: rtrim(config('rag.qdrant.base_url'), '/').'/collections/'.$collection.'/',
    key: config('rag.qdrant.key'),
    dimension: config('rag.qdrant.dimension'),
    pointIdNamespace: config('rag.qdrant.point_id_namespace'),
);
```

Change `RagDocumentSynchronizer` to obtain `$resolver->forCollection($model::ragCollection())` for upserts and `$resolver->forCollection($modelClass::ragCollection())` for deletes. Replace the global `VectorStoreInterface` binding with the resolver binding. Replace `RAG_QDRANT_URL` with `RAG_QDRANT_BASE_URL=http://qdrant:6333` in `.env.example`.

- [ ] **Step 4: Run resolver and synchronization tests**

Run: `ddev php artisan test tests/Unit/Rag/QdrantDocumentStoreResolverTest.php tests/Unit/Rag/RagDocumentSynchronizerTest.php`

Expected: PASS.

### Task 3: Verify separate collection ingestion

- Modify: `tests/Feature/Rag/BackfillRagDocumentsTest.php`
- Modify: `docs/adr/0009-use-a-qdrant-collection-per-documentable-model.md`

- [ ] **Step 1: Add a failing backfill assertion**

Assert a vehicle backfill job resolves `vehicle-documents` and never references a global collection setting.

- [ ] **Step 2: Run the test to verify it fails**

Run: `ddev php artisan test tests/Feature/Rag/BackfillRagDocumentsTest.php`

Expected: FAIL before collection routing is implemented.

- [ ] **Step 3: Run local integration verification**

Run: `ddev php artisan migrate:fresh --seed`

Run: `ddev php artisan rag:backfill-vehicles`

Run: `ddev php artisan queue:work --once` until the queue is empty.

Run: `ddev exec curl --fail http://qdrant:6333/collections/vehicle-documents`

Expected: Qdrant reports the vehicle collection with points.

- [ ] **Step 4: Format and run the full suite**

Run: `ddev exec vendor/bin/pint app config tests`

Run: `ddev php artisan test`

Expected: both commands exit 0.
