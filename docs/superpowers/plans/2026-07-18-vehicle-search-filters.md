# Vehicle Search Filters Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add validated optional vehicle metadata filters to semantic Qdrant retrieval.

**Architecture:** `VehicleSearchTool` normalizes public tool inputs and forwards a canonical filter array through `SearchModelTool` and `RagRetriever`. An application-owned `FilteredQdrantVectorStore` adds Qdrant `filter.must` serialization while `QdrantDocumentStore` preserves the unfiltered Neuron call for an empty filter array.

**Tech Stack:** PHP 8.3, Laravel 13, PHPUnit 12, Neuron AI, Qdrant.

---

## File Structure

- `app/Neuron/Tools/VehicleSearchTool.php`: declares and validates vehicle-specific tool inputs.
- `app/Neuron/Tools/SearchModelTool.php`: forwards canonical filters to generic retrieval.
- `app/Rag/RagRetriever.php`: embeds semantic text and forwards filters to the model collection.
- `app/Rag/Contracts/FilteredVectorStore.php`: defines filtered similarity capability without changing Neuron's interface.
- `app/Rag/FilteredQdrantVectorStore.php`: owns Qdrant payload filter query serialization.
- `app/Rag/QdrantDocumentStore.php`: delegates unfiltered and filtered similarity calls correctly.
- `app/Rag/QdrantDocumentStoreResolver.php`: constructs the application Qdrant store.
- `app/Neuron/Agents/VehicleAgent.php`: tells the model when to supply filters.
- Tests in the existing matching tool and RAG test files cover behavior at each boundary.

### Task 1: Define And Serialize Qdrant Filters

**Files:**
- Create: `app/Rag/Contracts/FilteredVectorStore.php`
- Create: `app/Rag/FilteredQdrantVectorStore.php`
- Modify: `app/Rag/QdrantDocumentStore.php`
- Modify: `app/Rag/QdrantDocumentStoreResolver.php`
- Test: `tests/Unit/Rag/QdrantDocumentStoreTest.php`

- [ ] **Step 1: Write failing Qdrant serialization tests**

```php
public function test_it_serializes_exact_and_inclusive_payload_filters_with_similarity_search(): void
{
    $store = new FilteredQdrantVectorStore('http://qdrant.test/collections/vehicles/', null, httpClient: $client);

    $store->similaritySearchWithFilters([0.1, 0.2], [
        'fuel' => 'electric', 'brand' => 'Nissan', 'model' => 'Leaf',
        'min_hp' => 120, 'max_hp' => 200,
    ]);

    $this->assertSame(['must' => [
        ['key' => 'fuel', 'match' => ['value' => 'electric']],
        ['key' => 'brand', 'match' => ['value' => 'Nissan']],
        ['key' => 'model', 'match' => ['value' => 'Leaf']],
        ['key' => 'hp', 'range' => ['gte' => 120, 'lte' => 200]],
    ]], $client->requests[1]->body['filter']);
}

public function test_document_store_uses_the_existing_similarity_call_without_filters(): void
{
    $store->similaritySearch([0.1, 0.2]);
    $this->assertSame([['similaritySearch', [0.1, 0.2]]], $delegate->calls);
}
```

- [ ] **Step 2: Run the focused test and verify RED**

Run: `ddev php artisan test tests/Unit/Rag/QdrantDocumentStoreTest.php`

Expected: FAIL because `FilteredQdrantVectorStore` and filtered similarity APIs do not exist.

- [ ] **Step 3: Implement the filtered-store boundary and Qdrant subclass**

```php
interface FilteredVectorStore extends VectorStoreInterface
{
    /** @param array<string, string|int> $filters */
    public function similaritySearchWithFilters(array $embedding, array $filters): iterable;
}

public function similaritySearchWithFilters(array $embedding, array $filters): iterable
{
    return $this->query($embedding, ['filter' => ['must' => $this->filterClauses($filters)]]);
}

private function filterClauses(array $filters): array
{
    $clauses = array_map(fn (string $key): array => [
        'key' => $key, 'match' => ['value' => $filters[$key]],
    ], array_keys(array_intersect_key($filters, array_flip(['fuel', 'brand', 'model']))));

    if (isset($filters['min_hp']) || isset($filters['max_hp'])) {
        $clauses[] = ['key' => 'hp', 'range' => array_filter([
            'gte' => $filters['min_hp'] ?? null,
            'lte' => $filters['max_hp'] ?? null,
        ], fn (?int $value): bool => $value !== null)];
    }

    return $clauses;
}
```

Implement `query()` with the existing `points/query` body and document mapping. Make `QdrantDocumentStore::similaritySearch(array $embedding, array $filters = [])` call the plain delegate for `[]`, otherwise call `FilteredVectorStore::similaritySearchWithFilters()`. Change the resolver default factory to `FilteredQdrantVectorStore`.

- [ ] **Step 4: Run the focused test and verify GREEN**

Run: `ddev php artisan test tests/Unit/Rag/QdrantDocumentStoreTest.php`

Expected: PASS.

### Task 2: Propagate Filters Through Retrieval

**Files:**
- Modify: `app/Rag/RagRetriever.php`
- Modify: `app/Neuron/Tools/SearchModelTool.php`
- Test: `tests/Unit/Rag/RagRetrieverTest.php`
- Test: `tests/Feature/Neuron/Tools/SearchModelToolTest.php`

- [ ] **Step 1: Write failing forwarding tests**

```php
$documents = $retriever->search(Vehicle::class, 'quiet commuter', 3, ['fuel' => 'electric', 'min_hp' => 120]);

$this->assertSame([
    ['embedText', 'quiet commuter'],
    ['resolve', 'vehicle-documents'],
    ['similaritySearch', [0.1, 0.2], ['fuel' => 'electric', 'min_hp' => 120]],
], $calls);
```

```php
$tool->search('efficient vehicles', 5, ['brand' => 'Nissan']);
$this->assertSame([['efficient vehicles', 5, ['brand' => 'Nissan']]], $retriever->calls);
```

- [ ] **Step 2: Run focused tests and verify RED**

Run: `ddev php artisan test tests/Unit/Rag/RagRetrieverTest.php tests/Feature/Neuron/Tools/SearchModelToolTest.php`

Expected: FAIL because the optional filter parameter is not accepted or forwarded.

- [ ] **Step 3: Add optional canonical filter arguments**

```php
public function search(string $modelClass, string $query, int $limit, array $filters = []): array
{
    $embedding = $this->embeddings->embedText($query);
    $documents = $this->stores->forCollection($modelClass::ragCollection())
        ->similaritySearch($embedding, $filters);

    return array_slice(iterator_to_array($documents, false), 0, $limit);
}
```

Give `SearchModelTool::search()` the same optional `array $filters = []` argument and forward it. Update fake retrievers and vector stores to record the third or fourth filter argument.

- [ ] **Step 4: Run focused tests and verify GREEN**

Run: `ddev php artisan test tests/Unit/Rag/RagRetrieverTest.php tests/Feature/Neuron/Tools/SearchModelToolTest.php`

Expected: PASS.

### Task 3: Validate And Normalize Vehicle Tool Inputs

**Files:**
- Modify: `app/Neuron/Tools/VehicleSearchTool.php`
- Test: `tests/Feature/Neuron/Tools/VehicleSearchToolTest.php`

- [ ] **Step 1: Write failing valid-filter and invalid-input tests**

```php
$tool->setInputs([
    'query' => 'quiet family car', 'fuel' => 'electric', 'brand' => 'Nissan',
    'model' => 'Leaf', 'min_hp' => 120, 'max_hp' => 200,
]);
$tool->execute();
$this->assertSame([['quiet family car', 5, [
    'fuel' => 'electric', 'brand' => 'Nissan', 'model' => 'Leaf',
    'min_hp' => 120, 'max_hp' => 200,
]]], $retriever->calls);

$this->expectException(InvalidArgumentException::class);
$tool->__invoke('vehicles', fuel: 'hydrogen');
```

Add separate tests that reject an unknown brand, negative bounds, and `min_hp` greater than `max_hp`; retain a no-filter test that records `[]` and produces the same results.

- [ ] **Step 2: Run the focused test and verify RED**

Run: `ddev php artisan test tests/Feature/Neuron/Tools/VehicleSearchToolTest.php`

Expected: FAIL because the properties, invocation arguments, and validation do not exist.

- [ ] **Step 3: Implement the minimal tool contract**

```php
public function __invoke(
    string $query,
    int $limit = 5,
    ?string $fuel = null,
    ?string $brand = null,
    ?string $model = null,
    ?int $min_hp = null,
    ?int $max_hp = null,
): array {
    if ($min_hp !== null && $max_hp !== null && $min_hp > $max_hp) {
        throw new InvalidArgumentException('min_hp must not exceed max_hp.');
    }

    return $this->results = $this->search->search($query, $limit, array_filter([
        'fuel' => $fuel === null ? null : Fuel::from($fuel)->value,
        'brand' => $brand === null ? null : VehicleBrand::from($brand)->value,
        'model' => $model,
        'min_hp' => $min_hp,
        'max_hp' => $max_hp,
    ], static fn (string|int|null $value): bool => $value !== null));
}
```

Declare all five optional `ToolProperty` values. Validate `min_hp` and `max_hp` as non-negative before forwarding.

- [ ] **Step 4: Run the focused test and verify GREEN**

Run: `ddev php artisan test tests/Feature/Neuron/Tools/VehicleSearchToolTest.php`

Expected: PASS.

### Task 4: Direct The Agent And Verify The Suite

**Files:**
- Modify: `app/Neuron/Agents/VehicleAgent.php`
- Test: `tests/Feature/Neuron/Agents/VehicleAgentTest.php`

- [ ] **Step 1: Write a failing instruction assertion**

```php
$instructions = $this->invokeMethod(new VehicleAgent($tool), 'instructions');
$this->assertStringContainsString('fuel, brand, model, minimum horsepower, or maximum horsepower', $instructions);
```

- [ ] **Step 2: Run the focused test and verify RED**

Run: `ddev php artisan test tests/Feature/Neuron/Agents/VehicleAgentTest.php`

Expected: FAIL because the agent does not direct filter use.

- [ ] **Step 3: Add the instruction**

```php
'When a question states fuel, brand, model, minimum horsepower, or maximum horsepower, pass the corresponding vehicle_search filter. Use min_hp and max_hp as inclusive bounds.',
```

- [ ] **Step 4: Run focused tests, full tests, and Pint**

Run: `ddev php artisan test tests/Feature/Neuron/Agents/VehicleAgentTest.php`

Expected: PASS.

Run: `ddev php artisan test`

Expected: PASS.

Run: `ddev exec vendor/bin/pint --test`

Expected: PASS.
