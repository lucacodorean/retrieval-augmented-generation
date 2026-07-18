# RAG Result Resources Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract model-specific RAG search-result mapping to independent Neuron resources.

**Architecture:** `RagSearchable` returns a class implementing a resource contract rather than mapping models itself. `VehicleResource` serializes safe vehicle fields, and `SearchModelTool` resolves it for authoritative records before adding the retrieved score in unchanged document order.

**Tech Stack:** Laravel 13, PHP 8.3, Neuron AI, PHPUnit 12, Laravel Pint.

---

### Task 1: Define and expose result resources

**Files:**
- Create: `app/Neuron/Resources/RagResultResource.php`
- Create: `../../../app/Neuron/Resources/VehicleResource.php`
- Modify: `app/Rag/Contracts/RagSearchable.php`
- Modify: `app/Models/Vehicle.php`
- Test: `../../../tests/Unit/Neuron/Resources/VehicleResourceTest.php`
- Test: `tests/Unit/Rag/RagSearchableTest.php`

- [ ] Write a failing resource test that expects `VehicleResource` to map a loaded `Vehicle` to the existing safe response fields.
- [ ] Run `ddev php artisan test tests/Unit/Neuron/Resources/VehicleRagResultResourceTest.php` and confirm failure because the resource does not exist.
- [ ] Create `RagResultResource` with `public function toArray(Model $model): array`; implement the vehicle resource and return it from `Vehicle::ragResultResource()`.
- [ ] Remove `Vehicle::toRagResult()` and replace the corresponding `RagSearchableTest` assertion with the configured resource assertion.
- [ ] Re-run both focused tests and confirm they pass.

### Task 2: Resolve resources in retrieval

**Files:**
- Modify: `app/Neuron/Tools/SearchModelTool.php`
- Test: `tests/Feature/Neuron/Tools/SearchModelToolTest.php`

- [ ] Change the search-tool expectation to use resource output while retaining the existing `score` envelope and vector result order after a missing record.
- [ ] Run `ddev php artisan test tests/Feature/Neuron/Tools/SearchModelToolTest.php` and confirm failure because the tool still calls `toRagResult()`.
- [ ] Resolve the class returned by `ragResultResource()`, map each loaded record with it, and append the document score.
- [ ] Re-run the focused test and confirm it passes.

### Task 3: Format and verify

**Files:**
- Modify: files from Tasks 1 and 2 only

- [ ] Run `ddev exec vendor/bin/pint app tests`.
- [ ] Run `ddev php artisan test`.
