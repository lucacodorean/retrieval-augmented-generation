<?php

declare(strict_types=1);

namespace App\Neuron;

use InvalidArgumentException;
use NeuronAI\HttpClient\AmpHttpClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;

final class AgentProvider
{
    public static function configuredProvider(): AIProviderInterface
    {
        $model = self::required('agents.model', 'AGENT_MODEL is required.');

        return match (config('agents.provider')) {
            'ollama' => new Ollama(
                url: self::required('agents.base_url', 'AGENT_BASE_URL is required.'),
                model: $model,
                httpClient: new AmpHttpClient(timeout: (float) config('agents.timeout')),
            ),
            'openai' => new OpenAI(
                key: self::required('agents.api_key', 'AGENT_API_KEY is required.'),
                model: $model,
            ),
            'anthropic' => new Anthropic(
                key: self::required('agents.api_key', 'AGENT_API_KEY is required.'),
                model: $model,
            ),
            default => throw new InvalidArgumentException('AGENT_PROVIDER must be one of: ollama, openai, anthropic.'),
        };
    }

    private static function required(string $key, string $message): string
    {
        $value = config($key);

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }
}
