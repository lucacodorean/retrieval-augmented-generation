# Vehicle RAG Documents Design

## Goal

Prepare the vehicle domain for future RAG ingestion. The application will produce a
Neuron `Document` for each vehicle but will not embed, persist, retrieve, or query
those documents in this increment.

## Data model

`VehicleDetails` represents shareable vehicle specifications: brand, model,
horsepower, and fuel type. Many vehicles may reference the same specifications.

`Vehicle` represents an individual vehicle. It has a unique VIN, a string `index`
that stores its plate number, a required `user_id` owner foreign key, and a required
`vehicle_details_id` foreign key.

The relationships are:

- A vehicle belongs to one vehicle-details record.
- A vehicle-details record has many vehicles.

The `vehicle_details` migration must run before `vehicles`. The foreign key belongs
on `vehicles`, not `vehicle_details`.

## Model and factory changes

The implementation will correct relationship names, return types, and foreign keys
to match the data model. `Vehicle` will expose a `vehicleDetails` belongs-to
relationship. `VehicleDetails` will expose a `vehicles` has-many relationship.

The brand and fuel enums will be backed enums and cast on `VehicleDetails`. Factories
will create valid scalar values and provide a vehicle-details association when
creating a vehicle. The database seeder will create shared details followed by
vehicles that reference them.

## RAG document transformation

`app/Rag/Contracts/VehicleRagDocument` will accept a `Vehicle` with its
`vehicleDetails` relationship loaded and return a `NeuronAI\RAG\Document`.

Each document describes one individual vehicle in readable prose. It includes its
VIN and index plus the associated brand, model, horsepower, and fuel. It does not
include user names, email addresses, passwords, or other owner attributes.

Document metadata provides stable identifiers and future retrieval filters:

- `vehicle_id`
- `vehicle_details_id`
- `owner_id` when present
- `vin`
- `brand`
- `model`
- `fuel`
- `hp`

The document ID will be the deterministic namespaced key `vehicle:{vehicle ID}` so a
future vector-store ingestion process can upsert and delete it without colliding with
documents from other model types.

No documents are stored in the application database. The relational models remain
the source of truth; documents are created at a future ingestion boundary.

## Error handling

The transformer requires a non-null loaded `vehicleDetails` relationship. Passing a
vehicle without it is a programming error and should fail clearly rather than create
an incomplete document. The transformer also omits `owner_id` from an in-memory
vehicle with no owner, although persisted vehicles require an owner.

## Tests

Tests will verify:

- migrations create the foreign key on `vehicles` and migrate cleanly;
- model relationships represent many vehicles sharing one details record;
- factories and seeding create valid records with those relationships;
- the transformer produces the expected document content and metadata;
- a missing details relationship fails explicitly; and
- owner information is excluded from document content and metadata except for the
  owner ID.

## Deferred scope

This increment deliberately excludes embedding providers, vector stores, ingestion
commands, document synchronization, queues, agents, and retrieval APIs. Those
decisions require the target vector-store and embedding-provider requirements.
