# RAG Document Synchronization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Queue after-commit vector-document upserts and deletes for opted-in Eloquent models, beginning with `Vehicle`.

**Architecture:** `Documentable` and `SyncsDocuments` let models opt into a generic `DocumentObserver` without a common base model. Queued jobs pass a model class and primary key to `RagDocumentSynchronizer`, which reloads required relationships, transforms and embeds documents, then replaces or deletes vectors through Neuron interfaces using deterministic keys such as `vehicle:42`.

**Tech Stack:** PHP 8.3, Laravel 13 Eloquent observers and queues, PHPUnit 12, Neuron AI RAG interfaces.

---

## File Structure

- `app/Rag/Contracts/Documentable.php`: model opt-in contract.
- `app/Rag/Contracts/DocumentTransformer.php`: one-model-to-one-document transformation contract.
- `app/Rag/Concerns/SyncsDocuments.php`: trait that registers `DocumentObserver` during Eloquent booting.
- `app/Rag/RagDocumentSynchronizer.php`: embeds and replaces/removes vectors through Neuron abstractions.
- `app/Observers/DocumentObserver.php`: dispatches document jobs after model lifecycle events commit.
- `app/Observers/VehicleDetailsObserver.php`: fans a details change out to its related vehicle documents.
- `app/Jobs/UpsertRagDocument.php` and `app/Jobs/DeleteRagDocument.php`: queue boundaries that carry only model class/ID data.
- `app/Rag/Contracts/DocumentTransformer.php` and `app/Rag/Contracts/VehicleRagDocument.php`: document construction API and the vehicle implementation.
- `config/rag.php` and `app/Providers/AppServiceProvider.php`: provider/store class configuration and container bindings.
- `tests/Unit/Rag/*` and `tests/Feature/Rag/*`: contract, synchronizer, observer, and after-commit coverage.

### Task 1: Normalize the documentable and transformer contracts

**Files:**
- Create: `app/Rag/Contracts/Documentable.php`
- Create: `app/Rag/Contracts/DocumentTransformer.php`
- Create: `app/Rag/Concerns/SyncsDocuments.php`
- Create: `app/Observers/DocumentObserver.php`
- Modify: `app/Models/Vehicle.php`
- Modify: `app/Rag/Contracts/DocumentTransformer.php`
- Modify: `app/Rag/Contracts/VehicleRagDocument.php`
- Modify: `tests/Unit/Rag/VehicleRagDocumentTest.php`
- Test: `tests/Unit/Rag/DocumentableTest.php`

- [ ] **Step 1: Write failing contract and transformer tests**

Add tests that assert `Vehicle` implements `Documentable`, uses `SyncsDocuments`, declares `VehicleRagDocument::class`, and declares `['vehicleDetails']` as its required relations. Update the existing transformer test to assert these exact output rules:

```php
$this->assertSame('vehicle:42', $document->getId());
$this->assertSame(Vehicle::class, $document->sourceType);
$this->assertSame('vehicle:42', $document->sourceName);
$this->assertStringNotContainsString('owned by', $document->getContent());
$this->assertArrayNotHasKey('owner_name', $document->metadata);
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `ddev php artisan test tests/Unit/Rag/DocumentableTest.php tests/Unit/Rag/VehicleRagDocumentTest.php`

Expected: FAIL because the contracts, trait, and namespaced document ID do not exist.

- [ ] **Step 3: Implement the contracts, trait, and safe vehicle transformer**

Create `app/Rag/Contracts/DocumentTransformer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

use Illuminate\Database\Eloquent\Model;
use NeuronAI\RAG\Document;

interface DocumentTransformer
{
    public static function build(Model $model): Document;
}
```

Create `app/Rag/Contracts/Documentable.php`:

```php
<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

interface Documentable
{
    /** @return class-string<DocumentTransformer> */
    public static function documentTransformer(): string;

    /** @return list<string> */
    public static function documentRelations(): array;

    public function documentKey(): string;
}
```

Create `app/Rag/Concerns/SyncsDocuments.php`:

```php
<?php

declare(strict_types=1);

namespace App\Rag\Concerns;

use App\Observers\DocumentObserver;

trait SyncsDocuments
{
    public static function bootSyncsDocuments(): void
    {
        static::observe(DocumentObserver::class);
    }
}
```

Create `app/Observers/DocumentObserver.php` as an empty observer class at this step
so the trait can be booted while the transformer tests instantiate `Vehicle`:

```php
<?php

declare(strict_types=1);

namespace App\Observers;

class DocumentObserver
{
}
```

Change `Vehicle` to implement `Documentable`, use `SyncsDocuments`, and add:

```php
public static function documentTransformer(): string
{
    return VehicleRagDocument::class;
}

public static function documentRelations(): array
{
    return ['vehicleDetails'];
}

public function documentKey(): string
{
    return 'vehicle:'.$this->getKey();
}
```

Keep the static transformer API and reduce it to `DocumentTransformer::build(Model $model): Document`; delete `asJson()`. Update tests to call `VehicleRagDocument::build($vehicle)`. `VehicleRagDocument` must reject an unloaded or null `vehicleDetails` relation with `LogicException('Vehicle details relationship must be loaded.')`; it must not call `load()` itself. Build the existing vehicle prose without owner name/email, set `id` and `sourceName` to `$vehicle->documentKey()`, set `sourceType` to `Vehicle::class`, and retain only scalar metadata.

- [ ] **Step 4: Run the focused tests to verify they pass**

Run: `ddev php artisan test tests/Unit/Rag/DocumentableTest.php tests/Unit/Rag/VehicleRagDocumentTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the opt-in document contract**

```bash
git add app/Rag app/Models/Vehicle.php tests/Unit/Rag
```

### Task 2: Implement embedding and vector-store synchronization

**Files:**
- Create: `app/Rag/RagDocumentSynchronizer.php`
- Create: `config/rag.php`
- Modify: `.env.example`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Rag/RagDocumentSynchronizerTest.php`

- [ ] **Step 1: Write failing synchronizer tests using interface fakes**

Create fake implementations of `EmbeddingsProviderInterface` and `VectorStoreInterface` in the test. Assert that an upsert embeds the transformed document, calls `deleteBy(Vehicle::class, 'vehicle:42')`, then adds the embedded document. Assert delete calls `deleteBy(Vehicle::class, 'vehicle:42')` without embedding.

```php
$synchronizer->upsert($vehicle);

$this->assertSame(['vehicle document'], $embeddings->embeddedTexts);
$this->assertSame([[Vehicle::class, 'vehicle:42']], $store->deletedSources);
$this->assertSame(['vehicle:42'], $store->addedDocumentIds);
```

- [ ] **Step 2: Run the unit test to verify it fails**

Run: `ddev php artisan test tests/Unit/Rag/RagDocumentSynchronizerTest.php`

Expected: FAIL because `RagDocumentSynchronizer` does not exist.

- [ ] **Step 3: Implement the synchronizer and configurable bindings**

Create `app/Rag/RagDocumentSynchronizer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Rag;

use App\Rag\Contracts\Documentable;
use Illuminate\Database\Eloquent\Model;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

class RagDocumentSynchronizer
{
    public function __construct(
        private readonly EmbeddingsProviderInterface $embeddings,
        private readonly VectorStoreInterface $store,
    ) {
    }

    public function upsert(Documentable&Model $model): void
    {
        $transformer = $model::documentTransformer();
        $document = $transformer::build($model);
        $document = $this->embeddings->embedDocument($document);

        $this->store
            ->deleteBy($document->sourceType, $document->sourceName)
            ->addDocument($document);
    }

    public function delete(string $modelClass, string $documentKey): void
    {
        $this->store->deleteBy($modelClass, $documentKey);
    }
}
```

Create `config/rag.php` with `embeddings_provider` and `vector_store` environment-driven class names. In `AppServiceProvider::register()`, bind each Neuron interface by resolving its configured class through the container and throw `LogicException` with a clear configuration message when the value is absent. This keeps backend choice outside model and job code; tests bind fakes directly into the container.

Add the matching empty configuration entries to `.env.example`:

```dotenv
RAG_EMBEDDINGS_PROVIDER=
RAG_VECTOR_STORE=
```

- [ ] **Step 4: Run the synchronizer test to verify it passes**

Run: `ddev php artisan test tests/Unit/Rag/RagDocumentSynchronizerTest.php`

Expected: PASS.

- [ ] **Step 5: Commit vector synchronization**

```bash
git add app/Rag/RagDocumentSynchronizer.php config/rag.php app/Providers/AppServiceProvider.php tests/Unit/Rag/RagDocumentSynchronizerTest.php
```

### Task 3: Dispatch after-commit upsert and delete jobs

**Files:**
- Create: `app/Jobs/UpsertRagDocument.php`
- Create: `app/Jobs/DeleteRagDocument.php`
- Create: `app/Observers/DocumentObserver.php`
- Test: `tests/Feature/Rag/DocumentSynchronizationTest.php`

- [ ] **Step 1: Write failing observer and job tests**

Use `Bus::fake()` and a `Vehicle` factory. Assert that creation and update dispatch `UpsertRagDocument(Vehicle::class, $vehicle->getKey())`; assert deletion dispatches `DeleteRagDocument(Vehicle::class, 'vehicle:{id}')`. Assert the observer implements `ShouldHandleEventsAfterCommit` and both jobs use `ShouldQueue` and `Queueable`.

```php
Bus::assertDispatched(UpsertRagDocument::class, fn (UpsertRagDocument $job): bool =>
    $job->modelClass === Vehicle::class && $job->modelId === $vehicle->getKey(),
);
```

- [ ] **Step 2: Run the feature test to verify it fails**

Run: `ddev php artisan test tests/Feature/Rag/DocumentSynchronizationTest.php`

Expected: FAIL because no observer or jobs exist.

- [ ] **Step 3: Implement observer and jobs**

Replace the Task 1 observer stub. `DocumentObserver` must implement
`ShouldHandleEventsAfterCommit` and dispatch jobs only from `created`, `updated`,
and `deleted`. Its methods accept `Model $model`, return immediately for a
non-`Documentable` model, and dispatch using the model class and key.

`UpsertRagDocument` stores `public string $modelClass` and `public int|string $modelId`. Its `handle(RagDocumentSynchronizer $synchronizer)` method:

```php
$model = $this->modelClass::query()
    ->with($this->modelClass::documentRelations())
    ->find($this->modelId);

if ($model instanceof Documentable) {
    $synchronizer->upsert($model);
}
```

It intentionally returns when the row no longer exists, covering a delete that occurs after an upsert job was queued. `DeleteRagDocument` stores the model class and document key; its handler calls `$synchronizer->delete($this->modelClass, $this->documentKey)`.

- [ ] **Step 4: Run the feature test to verify it passes**

Run: `ddev php artisan test tests/Feature/Rag/DocumentSynchronizationTest.php`

Expected: PASS.

- [ ] **Step 5: Commit lifecycle job dispatch**

```bash
git add app/Jobs app/Observers/DocumentObserver.php tests/Feature/Rag/DocumentSynchronizationTest.php
```

### Task 4: Fan out VehicleDetails changes and verify the complete flow

**Files:**
- Create: `app/Observers/VehicleDetailsObserver.php`
- Modify: `app/Models/VehicleDetails.php`
- Modify: `tests/Feature/Rag/DocumentSynchronizationTest.php`

- [ ] **Step 1: Add failing dependent-update tests**

Create one details row with two related vehicles, fake the bus, update its horsepower, and assert two `UpsertRagDocument` jobs for those vehicle IDs. Assert that a details row itself never dispatches a document job.

- [ ] **Step 2: Run the dependent-update test to verify it fails**

Run: `ddev php artisan test tests/Feature/Rag/DocumentSynchronizationTest.php`

Expected: FAIL because changing `VehicleDetails` has no fan-out observer.

- [ ] **Step 3: Implement the details observer**

Register `VehicleDetailsObserver` from `VehicleDetails::booted()` with:

```php
static::observe(VehicleDetailsObserver::class);
```

Make `VehicleDetailsObserver` implement `ShouldHandleEventsAfterCommit`. In `created` and `updated`, iterate `$details->vehicles()->pluck('id')` and dispatch `UpsertRagDocument` for each `Vehicle::class` ID. Do not implement a details delete handler because the required foreign key prevents deleting details while related vehicles exist.

- [ ] **Step 4: Run migration, seed, formatting, and all tests**

Run: `ddev php artisan migrate:fresh --seed`

Expected: Exit code 0.

Run: `ddev exec vendor/bin/pint app config database tests`

Expected: Exit code 0.

Run: `ddev php artisan test`

Expected: PASS.

- [ ] **Step 5: Commit dependent synchronization and documentation**

```bash
git add app/Models/VehicleDetails.php app/Observers/VehicleDetailsObserver.php tests/Feature/Rag/DocumentSynchronizationTest.php docs/superpowers/plans/2026-07-18-rag-document-synchronization.md
```
