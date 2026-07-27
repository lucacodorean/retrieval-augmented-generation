<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron;

use App\Neuron\AgentProvider;
use InvalidArgumentException;
use NeuronAI\HttpClient\AmpHttpClient;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Tests\TestCase;

class AgentProviderTest extends TestCase
{
    private const AGENT_ENVIRONMENT = [
        'AGENT_PROVIDER' => 'test-provider',
        'AGENT_MODEL' => 'test-model',
        'AGENT_API_KEY' => 'test-api-key',
        'AGENT_BASE_URL' => 'https://example.test/api',
        'AGENT_TIMEOUT' => '60',
    ];

    /** @var array<string, array{environment: string|false, env: array{exists: bool, value: mixed}, server: array{exists: bool, value: mixed}}> */
    private array $originalAgentEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::AGENT_ENVIRONMENT as $name => $value) {
            $this->originalAgentEnvironment[$name] = [
                'environment' => getenv($name),
                'env' => [
                    'exists' => array_key_exists($name, $_ENV),
                    'value' => $_ENV[$name] ?? null,
                ],
                'server' => [
                    'exists' => array_key_exists($name, $_SERVER),
                    'value' => $_SERVER[$name] ?? null,
                ],
            ];

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalAgentEnvironment as $name => $original) {
            if ($original['environment'] === false) {
                putenv($name);
            } else {
                putenv("{$name}={$original['environment']}");
            }

            if ($original['env']['exists']) {
                $_ENV[$name] = $original['env']['value'];
            } else {
                unset($_ENV[$name]);
            }

            if ($original['server']['exists']) {
                $_SERVER[$name] = $original['server']['value'];
            } else {
                unset($_SERVER[$name]);
            }
        }

        parent::tearDown();
    }

    public function test_agent_provider_defaults_match_the_local_ollama_service(): void
    {
        $this->removeAgentEnvironment();

        $config = require base_path('config/agents.php');

        $this->assertSame('ollama', $config['provider']);
        $this->assertNull($config['model']);
        $this->assertNull($config['api_key']);
        $this->assertSame('http://host.docker.internal:11434/api', $config['base_url']);
        $this->assertSame(180.0, $config['timeout']);
    }

    public function test_it_resolves_the_configured_ollama_provider(): void
    {
        config()->set('agents.provider', 'ollama');
        config()->set('agents.model', 'llama3.2');
        config()->set('agents.base_url', 'http://ollama.test/api');
        config()->set('agents.timeout', 12.5);

        $provider = AgentProvider::configuredProvider();

        $this->assertInstanceOf(Ollama::class, $provider);
        $this->assertSame('http://ollama.test/api', $this->property($provider, 'url'));
        $this->assertSame('llama3.2', $this->property($provider, 'model'));
        $this->assertInstanceOf(AmpHttpClient::class, $provider->getHttpClient());
        $this->assertSame(12.5, $this->property($provider->getHttpClient(), 'timeout'));
    }

    #[DataProvider('invalidOllamaBaseUrls')]
    public function test_it_rejects_an_invalid_ollama_base_url(?string $baseUrl, bool $missing): void
    {
        config()->set('agents.provider', 'ollama');
        config()->set('agents.model', 'llama3.2');

        if ($missing) {
            config()->offsetUnset('agents.base_url');
        } else {
            config()->set('agents.base_url', $baseUrl);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_BASE_URL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_resolves_the_configured_openai_provider(): void
    {
        config()->set('agents.provider', 'openai');
        config()->set('agents.model', 'gpt-4.1-mini');
        config()->set('agents.api_key', 'test-openai-key');

        $provider = AgentProvider::configuredProvider();

        $this->assertInstanceOf(OpenAI::class, $provider);
        $this->assertSame('gpt-4.1-mini', $this->property($provider, 'model'));
    }

    public function test_it_resolves_the_configured_anthropic_provider(): void
    {
        config()->set('agents.provider', 'anthropic');
        config()->set('agents.model', 'claude-sonnet-4-5');
        config()->set('agents.api_key', 'test-anthropic-key');

        $provider = AgentProvider::configuredProvider();

        $this->assertInstanceOf(Anthropic::class, $provider);
        $this->assertSame('claude-sonnet-4-5', $this->property($provider, 'model'));
    }

    public function test_it_rejects_an_unknown_provider(): void
    {
        config()->set('agents.provider', 'unsupported');
        config()->set('agents.model', 'test-model');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_PROVIDER must be one of: ollama, openai, anthropic.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_blank_model(): void
    {
        config()->set('agents.provider', 'ollama');
        config()->set('agents.model', " \t\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_MODEL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_null_model(): void
    {
        config()->set('agents.provider', 'ollama');
        config()->set('agents.model', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_MODEL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_missing_model(): void
    {
        config()->set('agents.provider', 'ollama');
        config()->offsetUnset('agents.model');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_MODEL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_blank_openai_model(): void
    {
        config()->set('agents.provider', 'openai');
        config()->set('agents.model', '');
        config()->set('agents.api_key', 'test-openai-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_MODEL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_blank_anthropic_model(): void
    {
        config()->set('agents.provider', 'anthropic');
        config()->set('agents.model', '');
        config()->set('agents.api_key', 'test-anthropic-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_MODEL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_blank_openai_api_key(): void
    {
        config()->set('agents.provider', 'openai');
        config()->set('agents.model', 'gpt-4.1-mini');
        config()->set('agents.api_key', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_API_KEY is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_null_openai_api_key(): void
    {
        config()->set('agents.provider', 'openai');
        config()->set('agents.model', 'gpt-4.1-mini');
        config()->set('agents.api_key', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_API_KEY is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_missing_openai_api_key(): void
    {
        config()->set('agents.provider', 'openai');
        config()->set('agents.model', 'gpt-4.1-mini');
        config()->offsetUnset('agents.api_key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_API_KEY is required.');

        AgentProvider::configuredProvider();
    }

    #[DataProvider('remoteProviders')]
    public function test_model_validation_precedes_api_key_validation(string $provider): void
    {
        config()->set('agents.provider', $provider);
        config()->offsetUnset('agents.model');
        config()->offsetUnset('agents.api_key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_MODEL is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_blank_anthropic_api_key(): void
    {
        config()->set('agents.provider', 'anthropic');
        config()->set('agents.model', 'claude-sonnet-4-5');
        config()->set('agents.api_key', " \t\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_API_KEY is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_null_anthropic_api_key(): void
    {
        config()->set('agents.provider', 'anthropic');
        config()->set('agents.model', 'claude-sonnet-4-5');
        config()->set('agents.api_key', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_API_KEY is required.');

        AgentProvider::configuredProvider();
    }

    public function test_it_rejects_a_missing_anthropic_api_key(): void
    {
        config()->set('agents.provider', 'anthropic');
        config()->set('agents.model', 'claude-sonnet-4-5');
        config()->offsetUnset('agents.api_key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AGENT_API_KEY is required.');

        AgentProvider::configuredProvider();
    }

    /** @return array<string, array{?string, bool}> */
    public static function invalidOllamaBaseUrls(): array
    {
        return [
            'blank' => [" \t\n", false],
            'null' => [null, false],
            'missing' => [null, true],
        ];
    }

    /** @return array<string, array{string}> */
    public static function remoteProviders(): array
    {
        return [
            'OpenAI' => ['openai'],
            'Anthropic' => ['anthropic'],
        ];
    }

    private function property(object $object, string $property): mixed
    {
        return (new ReflectionProperty($object, $property))->getValue($object);
    }

    private function removeAgentEnvironment(): void
    {
        foreach (array_keys(self::AGENT_ENVIRONMENT) as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }
}
