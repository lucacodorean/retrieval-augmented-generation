# Model-Specific RAG Retrieval Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Provide a reusable read-only semantic retrieval tool, starting with vehicles.

**Architecture:** Models opt into `RagSearchable`, which declares collection access and safe source-record mapping. `RagRetriever` embeds a query and searches the selected collection; `SearchModelTool` reloads authoritative records while preserving Qdrant rank and score.

**Tech Stack:** Laravel 13, Neuron AI, Ollama, Qdrant, PHPUnit 12.

---

### Task 1: Add searchable-model contracts

**Files:**
- Create: `app/Rag/Contracts/RagSearchable.php`
- Modify: `app/Models/Vehicle.php`
- Test: `tests/Unit/Rag/RagSearchableTest.php`

- [ ] Write a failing test asserting `Vehicle` is `RagSearchable`, returns `vehicle-documents`, and maps a vehicle to safe JSON fields.
- [ ] Run: `ddev php artisan test tests/Unit/Rag/RagSearchableTest.php`
- [ ] Implement `RagSearchable` with `ragCollection()`, `loadRagRecords(array $ids)`, and `toRagResult(Model $model): array`; implement it on `Vehicle` without owner PII.
- [ ] Re-run the test and format: `ddev exec vendor/bin/pint app tests`.

### Task 2: Implement generic retrieval

**Files:**
- Create: `app/Rag/RagRetriever.php`
- Create: `app/Neuron/Tools/SearchModelTool.php`
- Test: `tests/Unit/Rag/RagRetrieverTest.php`
- Test: `tests/Feature/Neuron/Tools/SearchModelToolTest.php`

- [ ] Write failing tests using fake embeddings and Qdrant stores. Assert query embedding, selected collection search, score preservation, SQL reload by metadata IDs, and omission of deleted source records.
- [ ] Run: `ddev php artisan test tests/Unit/Rag/RagRetrieverTest.php tests/Feature/Neuron/Tools/SearchModelToolTest.php`
- [ ] Implement `RagRetriever::search(string $modelClass, string $query, int $limit): array` and `SearchModelTool::search(string $query, int $limit = 5): array`.
- [ ] Return records in Qdrant rank order as `['record' => ..., 'score' => ...]`; never expose embeddings or raw document content.
- [ ] Re-run focused tests and Pint.

### Task 3: Verify vehicle search

**Files:**
- Modify: `tests/Feature/Neuron/Tools/SearchModelToolTest.php`
- Modify: `docs/superpowers/specs/2026-07-18-model-specific-rag-retrieval-design.md`

- [ ] Add a test for a vehicle query that returns current vehicle fields and score but no owner PII or vector values.
- [ ] Run: `ddev php artisan test tests/Feature/Neuron/Tools/SearchModelToolTest.php`
- [ ] Run full verification:

```bash
ddev php artisan test
```

- [ ] Commit when Git is available:

```bash
git add app/Rag app/Models/Vehicle.php tests docs
```
