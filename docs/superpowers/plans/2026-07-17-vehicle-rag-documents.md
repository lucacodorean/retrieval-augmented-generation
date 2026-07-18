# Vehicle RAG Documents Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Normalize the vehicle schema and generate a deterministic Neuron RAG document for each vehicle through `app/Rag/Contracts/VehicleRagDocument`.

**Architecture:** `VehicleDetails` is the shareable specifications aggregate and a `Vehicle` references it through `vehicle_details_id`. The transformer receives a vehicle whose details relation is explicitly loaded, combines the two records into readable text and scalar metadata, and assigns the vehicle ID as the document ID. It creates no embeddings and performs no vector-store I/O.

**Tech Stack:** PHP 8.3, Laravel 13 Eloquent and migrations, PHPUnit 12, Faker, Neuron AI 3.15.

---

### Task 1: Specify the normalized vehicle schema and model behavior

**Files:**
- Create: `tests/Feature/VehiclePersistenceTest.php`
- Modify: `app/Models/Vehicle.php`
- Modify: `app/Models/VehicleDetails.php`
- Modify: `app/Enum/VehicleBrand.php`
- Modify: `app/Enum/Fuel.php`
- Modify: `database/factories/VehicleFactory.php`
- Modify: `database/factories/VehicleDetailsFactory.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `database/migrations/2026_07_17_142803_create_vehicle_details_table.php`
- Create: `database/migrations/2026_07_17_142804_create_vehicles_table.php`

- [ ] **Step 1: Write the failing persistence tests**

```php
<?php

namespace Tests\Feature;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_many_vehicles_can_share_one_details_record(): void
    {
        $details = VehicleDetails::factory()->create([
            'brand' => VehicleBrand::Nissan,
            'model' => 'Leaf',
            'fuel' => Fuel::Electric,
            'hp' => 150,
        ]);

        $vehicles = Vehicle::factory(2)->for($details, 'vehicleDetails')->create();

        $this->assertCount(2, $details->vehicles);
        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => $vehicle->vehicleDetails->is($details),
        ));
    }

    public function test_vehicle_details_enum_attributes_are_cast_to_backed_enums(): void
    {
        $details = VehicleDetails::factory()->create([
            'brand' => VehicleBrand::Suzuki,
            'fuel' => Fuel::Gas,
        ]);

        $this->assertSame(VehicleBrand::Suzuki, $details->brand);
        $this->assertSame(Fuel::Gas, $details->fuel);
    }

    public function test_vehicle_factory_creates_required_relationships_and_unique_vins(): void
    {
        $vehicles = Vehicle::factory(2)->create();

        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => $vehicle->vehicleDetails()->exists(),
        ));
        $this->assertCount(2, $vehicles->pluck('vin')->unique());
    }
}
```

- [ ] **Step 2: Run the persistence tests to verify they fail**

Run: `php artisan test tests/Feature/VehiclePersistenceTest.php`

Expected: FAIL because the `vehicles` table and corrected relationships do not exist.

- [ ] **Step 3: Implement the normalized persistence layer**

Replace `app/Enum/VehicleBrand.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum VehicleBrand: string
{
    case Nissan = 'Nissan';
    case Suzuki = 'Suzuki';
    case Volkswagen = 'Volkswagen';
    case Dacia = 'Dacia';
}
```

Replace `app/Enum/Fuel.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum Fuel: string
{
    case Diesel = 'diesel';
    case Gas = 'gas';
    case Electric = 'electric';
    case Hybrid = 'hybrid';
}
```

Replace `app/Models/Vehicle.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['index', 'vin', 'user_id', 'vehicle_details_id'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicleDetails(): BelongsTo
    {
        return $this->belongsTo(VehicleDetails::class);
    }
}
```

Replace `app/Models/VehicleDetails.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use Database\Factories\VehicleDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['brand', 'model', 'hp', 'fuel'])]
class VehicleDetails extends Model
{
    /** @use HasFactory<VehicleDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'brand' => VehicleBrand::class,
            'fuel' => Fuel::class,
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
```

Replace `database/migrations/2026_07_17_142803_create_vehicle_details_table.php` with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('hp');
            $table->string('fuel');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_details');
    }
};
```

Create `database/migrations/2026_07_17_142804_create_vehicles_table.php` with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_details_id')->constrained();
            $table->string('index');
            $table->string('vin')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
```

Replace `database/factories/VehicleDetailsFactory.php` with:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use App\Models\VehicleDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleDetailsFactory extends Factory
{
    protected $model = VehicleDetails::class;

    public function definition(): array
    {
        return [
            'brand' => $this->faker->randomElement(VehicleBrand::cases()),
            'model' => $this->faker->word(),
            'fuel' => $this->faker->randomElement(Fuel::cases()),
            'hp' => $this->faker->numberBetween(90, 300),
        ];
    }
}
```

Replace `database/factories/VehicleFactory.php` with:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_details_id' => VehicleDetails::factory(),
            'index' => $this->faker->bothify('VEH-####'),
            'vin' => $this->faker->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
        ];
    }
}
```

Replace the body of `DatabaseSeeder::run()` in `database/seeders/DatabaseSeeder.php` with:

```php
$users = User::factory(10)->create();
$details = VehicleDetails::factory(5)->create();

Vehicle::factory(10)
    ->recycle($users)
    ->recycle($details)
    ->create();
```

- [ ] **Step 4: Run the persistence tests to verify they pass**

Run: `php artisan test tests/Feature/VehiclePersistenceTest.php`

Expected: PASS with 3 tests.

- [ ] **Step 5: Commit the normalized persistence changes**

```bash
git add app/Enum app/Models database/factories database/migrations database/seeders tests/Feature/VehiclePersistenceTest.php
git commit -m "feat: normalize vehicle persistence"
```

### Task 2: Specify RAG document transformation

**Files:**
- Create: `tests/Unit/Rag/VehicleRagDocumentTest.php`
- Create: `app/Rag/Contracts/VehicleRagDocument.php`

- [ ] **Step 1: Write the failing transformer tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Enum\Fuel;use App\Enum\VehicleBrand;use App\Models\Vehicle;use App\Models\VehicleDetails;use App\Rag\Contracts\VehicleRagDocument;use LogicException;use Tests\TestCase;

class VehicleRagDocumentTest extends TestCase
{
    public function test_it_transforms_a_vehicle_and_its_details_into_a_stable_rag_document(): void
    {
        $details = new VehicleDetails([
            'brand' => VehicleBrand::Nissan,
            'model' => 'Leaf',
            'hp' => 150,
            'fuel' => Fuel::Electric,
        ]);
        $details->setAttribute('id', 12);

        $vehicle = new Vehicle([
            'index' => 'VEH-0001',
            'vin' => '1N4AZ1CP0KC300001',
            'user_id' => 9,
        ]);
        $vehicle->setAttribute('id', 42);
        $vehicle->setRelation('vehicleDetails', $details);

        $document = (new VehicleRagDocument())->transform($vehicle);

        $this->assertSame(42, $document->id);
        $this->assertSame(
            'Vehicle VEH-0001 with VIN 1N4AZ1CP0KC300001 is a Nissan Leaf with 150 hp and electric fuel.',
            $document->content,
        );
        $this->assertSame([
            'vehicle_id' => 42,
            'vehicle_details_id' => 12,
            'owner_id' => 9,
            'vin' => '1N4AZ1CP0KC300001',
            'brand' => 'Nissan',
            'model' => 'Leaf',
            'fuel' => 'electric',
            'hp' => 150,
        ], $document->metadata);
    }

    public function test_it_omits_owner_metadata_when_the_vehicle_has_no_owner(): void
    {
        $details = new VehicleDetails([
            'brand' => VehicleBrand::Dacia,
            'model' => 'Spring',
            'hp' => 65,
            'fuel' => Fuel::Electric,
        ]);
        $details->setAttribute('id', 4);

        $vehicle = new Vehicle([
            'index' => 'VEH-0002',
            'vin' => 'VF1AAAAA000000002',
        ]);
        $vehicle->setAttribute('id', 8);
        $vehicle->setRelation('vehicleDetails', $details);

        $document = (new VehicleRagDocument())->transform($vehicle);

        $this->assertArrayNotHasKey('owner_id', $document->metadata);
    }

    public function test_it_requires_vehicle_details_to_be_loaded(): void
    {
        $vehicle = new Vehicle(['index' => 'VEH-0003', 'vin' => 'VF1AAAAA000000003']);
        $vehicle->setAttribute('id', 3);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Vehicle details relationship must be loaded.');

        (new VehicleRagDocument())->transform($vehicle);
    }
}
```

- [ ] **Step 2: Run the transformer tests to verify they fail**

Run: `ddev php artisan test tests/Unit/Rag/VehicleRagDocumentTest.php`

Expected: FAIL because `App\Rag\Contracts\VehicleRagDocument` does not exist.

- [ ] **Step 3: Implement the transformer**

Create `app/Rag/Contracts/VehicleRagDocument.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

use App\Models\Vehicle;
use LogicException;
use NeuronAI\RAG\Document;

class VehicleRagDocument implements DocumentTransformer
{
    public function transform(Vehicle $vehicle): Document
    {
        if (! $vehicle->relationLoaded('vehicleDetails')) {
            throw new LogicException('Vehicle details relationship must be loaded.');
        }

        $details = $vehicle->vehicleDetails;

        $document = new Document(sprintf(
            'Vehicle %s with VIN %s is a %s %s with %d hp and %s fuel.',
            $vehicle->index,
            $vehicle->vin,
            $details->brand->value,
            $details->model,
            $details->hp,
            $details->fuel->value,
        ));

        $document->id = $vehicle->getKey();
        $document->addMetadata('vehicle_id', $vehicle->getKey());
        $document->addMetadata('vehicle_details_id', $details->getKey());

        if ($vehicle->user_id !== null) {
            $document->addMetadata('owner_id', $vehicle->user_id);
        }

        return $document
            ->addMetadata('vin', $vehicle->vin)
            ->addMetadata('brand', $details->brand->value)
            ->addMetadata('model', $details->model)
            ->addMetadata('fuel', $details->fuel->value)
            ->addMetadata('hp', $details->hp);
    }
}
```

- [ ] **Step 4: Run the transformer tests to verify they pass**

Run: `ddev php artisan test tests/Unit/Rag/VehicleRagDocumentTest.php`

Expected: PASS with 3 tests.

- [ ] **Step 5: Commit the transformer**

```bash
git add app/Rag/Contracts/VehicleRagDocument.php tests/Unit/Rag/VehicleRagDocumentTest.php
git commit -m "feat: transform vehicles into rag documents"
```

### Task 3: Verify database setup and the full test suite

**Files:**
- Modify: `docs/superpowers/specs/2026-07-17-vehicle-rag-documents-design.md`
- Modify: `docs/adr/0001-keep-vehicle-data-normalized.md`
- Modify: `docs/adr/0002-transform-vehicles-into-rag-documents.md`
- Modify: `docs/adr/0003-defer-vector-persistence.md`

- [ ] **Step 1: Rebuild the local database from migrations and seed it**

Run: `php artisan migrate:fresh --seed`

Expected: Exit code 0; the `vehicle_details` migration runs before `vehicles`, and seeding creates users, shared details, and vehicles.

- [ ] **Step 2: Run the complete automated suite**

Run: `php artisan test`

Expected: PASS, including the six tests added in Tasks 1 and 2.

- [ ] **Step 3: Apply formatting**

Run: `vendor/bin/pint --dirty`

Expected: Exit code 0.

- [ ] **Step 4: Re-run the complete suite after formatting**

Run: `php artisan test`

Expected: PASS.

- [ ] **Step 5: Commit the approved documentation**

```bash
git add docs/superpowers/specs/2026-07-17-vehicle-rag-documents-design.md docs/superpowers/plans/2026-07-17-vehicle-rag-documents.md docs/adr
git commit -m "docs: record vehicle rag design"
```
