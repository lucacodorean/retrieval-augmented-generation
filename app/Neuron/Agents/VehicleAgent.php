<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use App\Neuron\AgentProvider;
use App\Neuron\Tools\VehicleSearchTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\ToolInterface;

class VehicleAgent extends Agent
{
    public function __construct(
        private readonly VehicleSearchTool $vehicleSearchTool
    ) {
        parent::__construct();
    }

    /** @return array{response: array{
     *     natural-lang: string,
     *     serialized: list<array{record: array<string, mixed>, score: float}>
     * }}
     **/
    public function ask(string $question): array
    {
        $message = $this->chat(new UserMessage($question))->getMessage();

        return [
            'response' => [
                'natural-lang' => $message->getContent() ?? '',
                'serialized' => $this->serializedResults(),
            ],
        ];
    }

    /** @return list<array{record: array<string, mixed>, score: float}>
     * @throws \JsonException
     */
    private function serializedResults(): array
    {
        $results = [];

        foreach ($this->getChatHistory()->getMessages() as $message) {
            if (! $message instanceof ToolResultMessage) {
                continue;
            }

            foreach ($message->getTools() as $tool) {
                if ($tool->getName() === 'vehicle_search') {
                    $results = json_decode($tool->getResult(), true, flags: JSON_THROW_ON_ERROR);
                }
            }
        }

        return $results;
    }

    protected function provider(): AIProviderInterface
    {
        return AgentProvider::configuredProvider();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You answer questions about globally available vehicles.',
                'Use vehicle_search in order to populate the serialized part of the answer.',
                'Vehicle search is read-only. Never claim to create, update, or delete vehicles.',
            ],
        );
    }

    /** @return list<ToolInterface> */
    protected function tools(): array
    {
        return [$this->vehicleSearchTool];
    }
}
