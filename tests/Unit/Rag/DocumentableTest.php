<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Models\Vehicle;
use App\Rag\Concerns\SyncsDocuments;
use App\Rag\Contracts\Documentable;
use App\Rag\Documents\VehicleDocument;
use Tests\TestCase;

class DocumentableTest extends TestCase
{
    public function test_vehicle_explicitly_opts_into_document_synchronization(): void
    {
        $vehicle = new Vehicle;

        $this->assertInstanceOf(Documentable::class, $vehicle);
        $this->assertContains(SyncsDocuments::class, class_uses(Vehicle::class));
        $this->assertSame(VehicleDocument::class, Vehicle::documentTransformer());
        $this->assertSame('vehicle-documents', Vehicle::ragCollection());
        $this->assertSame('vehicle:42', $vehicle->setAttribute('id', 42)->documentKey());
    }
}
