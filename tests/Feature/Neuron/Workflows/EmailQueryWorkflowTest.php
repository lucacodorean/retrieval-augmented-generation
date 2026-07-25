<?php

declare(strict_types=1);

namespace Tests\Feature\Neuron\Workflows;

use App\Neuron\Agents\FrenchTranslationAgent;
use App\Neuron\Agents\RomanianTranslationAgent;
use App\Neuron\Agents\VehicleAgent;
use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflow;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

use function Amp\delay;

class EmailQueryWorkflowTest extends TestCase
{
    public function test_it_runs_the_query_and_collects_both_translations_without_changing_the_original_response(): void
    {
        $query = 'Which electric vehicles are available?';
        $response = [
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
        $vehicleAgent->expects('ask')->with($query)->andReturn($response);
        $romanianAgent = Mockery::mock(RomanianTranslationAgent::class);
        $romanianAgent->expects('translate')
            ->with($response['response']['natural-lang'])
            ->andReturn('Doua vehicule electrice sunt disponibile.');
        $frenchAgent = Mockery::mock(FrenchTranslationAgent::class);
        $frenchAgent->expects('translate')
            ->with($response['response']['natural-lang'])
            ->andReturn('Deux vehicules electriques sont disponibles.');

        $result = (new EmailQueryWorkflow(
            $query,
            $vehicleAgent,
            $romanianAgent,
            $frenchAgent,
        ))->init()->run();

        $this->assertInstanceOf(EmailQueryWorkflowState::class, $result);
        $this->assertSame($response, $result->originalResponse());
        $this->assertSame($response['response']['serialized'], $result->originalResponse()['response']['serialized']);
        $this->assertEquals([
            Language::ROMANIAN->value => new Translation(
                Language::ROMANIAN,
                'Doua vehicule electrice sunt disponibile.',
            ),
            Language::FRENCH->value => new Translation(
                Language::FRENCH,
                'Deux vehicules electriques sont disponibles.',
            ),
        ], $result->translations());
        $this->assertSame(NodeState::EMAILS_PUSHED, $result->currentStep());
    }

    public function test_it_propagates_a_translation_failure_instead_of_returning_a_successful_state(): void
    {
        $query = 'Which electric vehicles are available?';
        $response = [
            'response' => [
                'natural-lang' => 'Two electric vehicles are available.',
                'serialized' => [
                    ['record' => ['type' => 'vehicle', 'id' => 42], 'score' => 0.91],
                ],
            ],
        ];
        $vehicleAgent = Mockery::mock(VehicleAgent::class);
        $vehicleAgent->expects('ask')->with($query)->andReturn($response);
        $romanianAgent = Mockery::mock(RomanianTranslationAgent::class);
        $romanianAgent->expects('translate')
            ->with($response['response']['natural-lang'])
            ->andThrow(new RuntimeException('Romanian translation failed.'));
        $frenchAgent = Mockery::mock(FrenchTranslationAgent::class);
        $frenchAgent->allows('translate')->andReturn('Deux vehicules electriques sont disponibles.');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Romanian translation failed.');

        (new EmailQueryWorkflow(
            $query,
            $vehicleAgent,
            $romanianAgent,
            $frenchAgent,
        ))->init()->run();
    }

    public function test_empty_translation_output_prevents_successful_collection(): void
    {
        $query = 'Which electric vehicles are available?';
        $sourceText = 'Two electric vehicles are available.';
        $vehicleAgent = Mockery::mock(VehicleAgent::class);
        $vehicleAgent->expects('ask')->with($query)->andReturn([
            'response' => [
                'natural-lang' => $sourceText,
                'serialized' => [],
            ],
        ]);
        $romanianAgent = Mockery::mock(RomanianTranslationAgent::class);
        $romanianAgent->expects('translate')->with($sourceText)->andReturn('');
        $frenchAgent = Mockery::mock(FrenchTranslationAgent::class);
        $frenchAgent->allows('translate')->andReturn('Deux vehicules electriques sont disponibles.');
        $state = new EmailQueryWorkflowState;

        try {
            (new EmailQueryWorkflow(
                $query,
                $vehicleAgent,
                $romanianAgent,
                $frenchAgent,
                state: $state,
            ))->init()->run();
            $this->fail('Expected an UnexpectedValueException for an empty translation.');
        } catch (UnexpectedValueException $exception) {
            $this->assertSame('Translation text cannot be empty.', $exception->getMessage());
        }

        $this->assertSame([], $state->translations());
        $this->assertNotSame(NodeState::COLLECTED_TRANSLATIONS, $state->currentStep());
    }

    public function test_it_runs_translation_branches_concurrently(): void
    {
        $query = 'Which electric vehicles are available?';
        $sourceText = 'Two electric vehicles are available.';
        $vehicleAgent = Mockery::mock(VehicleAgent::class);
        $vehicleAgent->expects('ask')->with($query)->andReturn([
            'response' => [
                'natural-lang' => $sourceText,
                'serialized' => [],
            ],
        ]);
        $active = 0;
        $maxActive = 0;
        $romanianAgent = Mockery::mock(RomanianTranslationAgent::class);
        $romanianAgent->expects('translate')->with($sourceText)->andReturnUsing(
            function () use (&$active, &$maxActive): string {
                $active++;
                $maxActive = max($maxActive, $active);

                try {
                    delay(0.01);

                    return 'Doua vehicule electrice sunt disponibile.';
                } finally {
                    $active--;
                }
            },
        );
        $frenchAgent = Mockery::mock(FrenchTranslationAgent::class);
        $frenchAgent->expects('translate')->with($sourceText)->andReturnUsing(
            function () use (&$active, &$maxActive): string {
                $active++;
                $maxActive = max($maxActive, $active);

                try {
                    delay(0.01);

                    return 'Deux vehicules electriques sont disponibles.';
                } finally {
                    $active--;
                }
            },
        );

        (new EmailQueryWorkflow(
            $query,
            $vehicleAgent,
            $romanianAgent,
            $frenchAgent,
        ))->init()->run();

        $this->assertSame(2, $maxActive);
    }
}
