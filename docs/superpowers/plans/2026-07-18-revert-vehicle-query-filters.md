# Revert Vehicle Query Filters Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore vehicle retrieval to semantic query plus optional result limit, with no exact query-side filters.

**Architecture:** Keep document metadata and semantic retrieval intact. Remove filter arguments from the vehicle tool, generic retrieval pipeline, and Qdrant store adapter so every retrieval invokes the native vector-store similarity search. Preserve result serialization and the existing agent endpoint.

**Tech Stack:** Laravel 13, PHP 8.3, NeuronAI, Qdrant, PHPUnit, Laravel Pint.

---

### Task 1: Define The Minimal Public Contract

**Files:**
- Modify: `tests/Feature/Neuron/Tools/VehicleSearchToolTest.php`
- Modify: `tests/Feature/Neuron/Tools/SearchModelToolTest.php`
- Modify: `tests/Unit/Rag/RagRetrieverTest.php`
- Modify: `tests/Unit/Rag/QdrantDocumentStoreTest.php`

- [ ] **Step 1: Remove filter-specific tests and fake-retriever filter parameters.**
- [ ] **Step 2: Assert the vehicle tool exposes only required `query`, optional `limit`, forwards `[query, limit]`, and still captures serialized results.**
- [ ] **Step 3: Run focused DDEV tests and confirm they fail because production methods still accept and pass filter arguments.**

### Task 2: Remove Filter Plumbing

**Files:**
- Modify: `app/Neuron/Tools/VehicleSearchTool.php`
- Modify: `app/Neuron/Tools/SearchModelTool.php`
- Modify: `app/Rag/RagRetriever.php`
- Modify: `app/Rag/QdrantDocumentStore.php`
- Modify: `app/Rag/QdrantDocumentStoreResolver.php`
- Delete: `app/Rag/Contracts/FilteredVectorStore.php`
- Delete: `app/Rag/FilteredQdrantVectorStore.php`

- [ ] **Step 1: Reduce all search signatures to query and limit; delegate to ordinary `similaritySearch`.**
- [ ] **Step 2: Resolve the native `QdrantVectorStore` rather than the filtering adapter.**
- [ ] **Step 3: Run the focused tests and confirm they pass.**

### Task 3: Remove Filter Guidance And Document The Current Capability

**Files:**
- Modify: `app/Neuron/Agents/VehicleAgent.php`
- Modify: `README.md`

- [ ] **Step 1: Delete agent instructions that map natural language constraints to filter properties.**
- [ ] **Step 2: State that exact metadata filters are a future optional capability, not current vehicle-search behavior.**
- [ ] **Step 3: Run `ddev php artisan test` and `ddev exec vendor/bin/pint`; both commands must pass.**
