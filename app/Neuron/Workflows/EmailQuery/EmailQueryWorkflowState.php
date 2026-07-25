<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery;

use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use LogicException;
use NeuronAI\Workflow\WorkflowState;

class EmailQueryWorkflowState extends WorkflowState
{
    private const string CURRENT_STEP = 'current_step';

    private const string ORIGINAL_RESPONSE = 'original_response';

    private const string TRANSLATIONS = 'translations';

    public function __construct(
        array $data = []
    ) {
        parent::__construct($data);
    }

    public function setCurrentStep(NodeState $step): void
    {
        $this->set(self::CURRENT_STEP, $step);
    }

    public function currentStep(): ?NodeState
    {
        return $this->get(self::CURRENT_STEP);
    }

    /** @param array{response: array{natural-lang: string, serialized: list<array{record: array<string, mixed>, score: float}>}} $response */
    public function setOriginalResponse(array $response): void
    {
        $this->set(self::ORIGINAL_RESPONSE, $response);
    }

    /** @return array{response: array{natural-lang: string, serialized: list<array{record: array<string, mixed>, score: float}>}} */
    public function originalResponse(): array
    {
        if (! $this->has(self::ORIGINAL_RESPONSE)) {
            throw new LogicException('Original response has not been set.');
        }

        return $this->get(self::ORIGINAL_RESPONSE);
    }

    public function sourceText(): string
    {
        return $this->originalResponse()['response']['natural-lang'];
    }

    /** @param array<string, Translation> $translations */
    public function setTranslations(array $translations): void
    {
        $this->set(self::TRANSLATIONS, $translations);
    }

    /** @return array<string, Translation> */
    public function translations(): array
    {
        return $this->get(self::TRANSLATIONS, []);
    }
}
