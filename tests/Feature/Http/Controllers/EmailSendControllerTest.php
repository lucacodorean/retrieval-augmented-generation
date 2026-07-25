<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Neuron\Agents\FrenchTranslationAgent;
use App\Neuron\Agents\RomanianTranslationAgent;
use App\Neuron\Agents\VehicleAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmailSendControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_returns_an_explicitly_serialized_final_state(): void
    {
        $query = 'Which electric vehicles are available?';
        $originalResponse = [
            'response' => [
                'natural-lang' => 'Two electric vehicles are available.',
                'serialized' => [
                    [
                        'record' => [
                            'type' => 'vehicle',
                            'id' => 42,
                            'attributes' => [
                                'index' => 'EV-0042',
                                'vin' => '1N4AZ1CP0KC300042',
                            ],
                            'relationships' => [
                                'vehicle_details' => [
                                    'id' => 142,
                                    'brand' => 'Nissan',
                                    'model' => 'Leaf',
                                    'hp' => 147,
                                    'fuel' => 'electric',
                                ],
                            ],
                        ],
                        'score' => 0.91,
                    ],
                    [
                        'record' => [
                            'type' => 'vehicle',
                            'id' => 84,
                            'attributes' => [
                                'index' => 'EV-0084',
                                'vin' => '5YJ3E1EA7KF300084',
                            ],
                            'relationships' => [
                                'vehicle_details' => [
                                    'id' => 184,
                                    'brand' => 'Tesla',
                                    'model' => 'Model 3',
                                    'hp' => 283,
                                    'fuel' => 'electric',
                                ],
                            ],
                        ],
                        'score' => 0.87,
                    ],
                ],
            ],
        ];
        $vehicleAgent = Mockery::mock(VehicleAgent::class);
        $vehicleAgent->expects('ask')->with($query)->andReturn($originalResponse);
        $romanianAgent = Mockery::mock(RomanianTranslationAgent::class);
        $romanianAgent->expects('translate')
            ->with($originalResponse['response']['natural-lang'])
            ->andReturn('Doua vehicule electrice sunt disponibile.');
        $frenchAgent = Mockery::mock(FrenchTranslationAgent::class);
        $frenchAgent->expects('translate')
            ->with($originalResponse['response']['natural-lang'])
            ->andReturn('Deux vehicules electriques sont disponibles.');
        $this->app->instance(VehicleAgent::class, $vehicleAgent);
        $this->app->instance(RomanianTranslationAgent::class, $romanianAgent);
        $this->app->instance(FrenchTranslationAgent::class, $frenchAgent);

        $response = $this->postJson('/api/vehicles/send', ['query' => $query]);

        $response->assertOk()->assertJsonStructure(['workflow_id', 'final_state']);
        $this->assertIsString($response->json('workflow_id'));
        $this->assertSame([
            'original_response' => $originalResponse,
            'translations' => [
                'ro' => [
                    'language' => 'ro',
                    'text' => 'Doua vehicule electrice sunt disponibile.',
                ],
                'fr' => [
                    'language' => 'fr',
                    'text' => 'Deux vehicules electriques sont disponibles.',
                ],
            ],
        ], $response->json('final_state'));
    }
}
