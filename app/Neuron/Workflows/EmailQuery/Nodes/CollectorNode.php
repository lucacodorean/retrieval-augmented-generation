<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\EmailSendRequestEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use LogicException;
use NeuronAI\Workflow\Events\ParallelEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;

class CollectorNode extends Node
{
    public function __invoke(ParallelEvent $event, EmailQueryWorkflowState $state): EmailSendRequestEvent
    {
        $state->setCurrentStep(NodeState::COLLECTING_TRANSLATIONS);
        $state->setTranslations([]);

        $translations = [];
        foreach ($event->getAllResults() as $branch => $translation) {
            if (! $translation instanceof Translation) {
                throw new LogicException("Collector result for branch [{$branch}] must be a Translation.");
            }

            $language = $translation->language->value;
            if (isset($translations[$language])) {
                throw new LogicException("Collector received duplicate translations for language [{$language}].");
            }

            $translations[$language] = $translation;
        }

        foreach (Language::cases() as $language) {
            if (! isset($translations[$language->value])) {
                throw new LogicException("Collector is missing a translation for language [{$language->value}].");
            }
        }

        $state->setTranslations($translations);
        $state->setCurrentStep(NodeState::COLLECTED_TRANSLATIONS);

        return new EmailSendRequestEvent;
    }
}
