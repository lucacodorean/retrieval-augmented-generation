# Agent Provider Adapter Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Configure every current and future Neuron agent to use Ollama, OpenAI, or Anthropic through one environment-selected provider factory.

**Architecture:** `App\Neuron\AgentProvider` is the only application class that imports concrete Neuron chat providers. Its static `configuredProvider()` method reads `config('agents')`, validates the selected provider's required settings, and returns an `AIProviderInterface`. `VehicleAgent` and `TranslationAgent` retain their existing constructors and delegate their `provider()` methods to this static factory.

**Tech Stack:** PHP 8.3, Laravel 13 configuration and container, Neuron AI 3.15, PHPUnit 12, Laravel Pint.

---

## File Structure

- Create: `config/agents.php` - maps `AGENT_*` environment values to application configuration.
- Create: `app/Neuron/AgentProvider.php` - validates configuration and constructs Neuron chat providers.
- Create: `tests/Unit/Neuron/AgentProviderTest.php` - provider selection and invalid-configuration coverage.
- Modify: `app/Neuron/Agents/VehicleAgent.php` - delegates provider resolution to `AgentProvider`.
- Modify: `app/Neuron/Agents/TranslationAgent.php` - delegates provider resolution to `AgentProvider`.
- Modify: `tests/Feature/Neuron/Agents/VehicleAgentTest.php` - supplies the new dependency and verifies delegation.
- Modify: `tests/Unit/Neuron/Agents/TranslationAgentTest.php` - supplies the new dependency and verifies delegation.
- Modify: `.env.example` - documents the chat-agent environment contract.

### Task 1: Add The Agent Configuration Contract

**Files:**
- Create: `config/agents.php`
- Modify: `.env.example:67-73`
- Test: `tests/Unit/Neuron/AgentProviderTest.php`

- [ ] **Step 1: Write the failing configuration-default test**

Create `tests/Unit/Neuron/AgentProviderTest.php` with this setup and test:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron;

use Tests\TestCase;

class AgentProviderTest extends TestCase
{
    public function test_it_exposes_the_ollama_agent_configuration_defaults(): void
    {
        $this->assertSame('ollama', config('agents.provider'));
        $this->assertSame('http://host.docker.internal:11434/api', config('agents.base_url'));
        $this->assertSame(180.0, config('agents.timeout'));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php`

Expected: FAIL because the `agents` configuration namespace does not exist.

- [ ] **Step 3: Add the configuration file and environment examples**

Create `config/agents.php`:

```php
<?php

declare(strict_types=1);

return [
    'provider' => env('AGENT_PROVIDER', 'ollama'),
    'model' => env('AGENT_MODEL'),
    'api_key' => env('AGENT_API_KEY'),
    'base_url' => env('AGENT_BASE_URL', 'http://host.docker.internal:11434/api'),
    'timeout' => (float) env('AGENT_TIMEOUT', 180),
];
```

Append these values after the existing RAG configuration in `.env.example`:

```dotenv
AGENT_PROVIDER=ollama
AGENT_MODEL=
AGENT_API_KEY=
AGENT_BASE_URL=http://host.docker.internal:11434/api
AGENT_TIMEOUT=180
```

- [ ] **Step 4: Run the configuration test to verify it passes**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php`

Expected: PASS with 3 assertions.

### Task 2: Implement And Test Provider Selection

**Files:**
- Create: `app/Neuron/AgentProvider.php`
- Modify: `tests/Unit/Neuron/AgentProviderTest.php`

- [ ] **Step 1: Add failing provider-selection and validation tests**

Add imports to `tests/Unit/Neuron/AgentProviderTest.php`:

```php
use App\Neuron\AgentProvider;
use InvalidArgumentException;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;
```

Add these tests:

```php
public function test_it_creates_an_ollama_provider(): void
{
    config()->set('agents', [
        'provider' => 'ollama',
        'model' => 'qwen3:8b',
        'api_key' => null,
        'base_url' => 'http://ollama.test/api',
        'timeout' => 42.0,
    ]);

    $this->assertInstanceOf(Ollama::class, AgentProvider::configuredProvider());
}

public function test_it_creates_an_openai_provider(): void
{
    config()->set('agents', [
        'provider' => 'openai',
        'model' => 'gpt-4.1-mini',
        'api_key' => 'openai-key',
        'base_url' => null,
        'timeout' => 180.0,
    ]);

    $this->assertInstanceOf(OpenAI::class, AgentProvider::configuredProvider());
}

public function test_it_creates_an_anthropic_provider(): void
{
    config()->set('agents', [
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-4-5',
        'api_key' => 'anthropic-key',
        'base_url' => null,
        'timeout' => 180.0,
    ]);

    $this->assertInstanceOf(Anthropic::class, AgentProvider::configuredProvider());
}

public function test_it_rejects_an_unknown_provider(): void
{
    config()->set('agents', ['provider' => 'gemini', 'model' => 'any', 'api_key' => null]);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('AGENT_PROVIDER must be one of: ollama, openai, anthropic.');

    AgentProvider::configuredProvider();
}

public function test_it_requires_a_model(): void
{
    config()->set('agents', ['provider' => 'ollama', 'model' => ' ', 'api_key' => null]);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('AGENT_MODEL is required.');

    AgentProvider::configuredProvider();
}

public function test_it_requires_an_api_key_for_remote_providers(string $provider): void
{
    config()->set('agents', ['provider' => $provider, 'model' => 'model', 'api_key' => ' ']);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('AGENT_API_KEY is required.');

    AgentProvider::configuredProvider();
}

public static function remoteProviders(): array
{
    return [['openai'], ['anthropic']];
}
```

Add `#[DataProvider('remoteProviders')]` to `test_it_requires_an_api_key_for_remote_providers()` and import `PHPUnit\Framework\Attributes\DataProvider`.

- [ ] **Step 2: Run the provider tests to verify they fail**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php`

Expected: FAIL because `App\Neuron\AgentProvider` is undefined.

- [ ] **Step 3: Implement the adapter and container binding**

Create `app/Neuron/AgentProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Neuron;

use InvalidArgumentException;
use NeuronAI\HttpClient\AmpHttpClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;

class AgentProvider
{
    public static function configuredProvider(): AIProviderInterface
    {
        $provider = config('agents.provider');
        $model = self::required('model', 'AGENT_MODEL');

        return match ($provider) {
            'ollama' => new Ollama(
                self::required('base_url', 'AGENT_BASE_URL'),
                $model,
                httpClient: new AmpHttpClient(timeout: (float) config('agents.timeout')),
            ),
            'openai' => new OpenAI(self::required('api_key', 'AGENT_API_KEY'), $model),
            'anthropic' => new Anthropic(self::required('api_key', 'AGENT_API_KEY'), $model),
            default => throw new InvalidArgumentException(
                'AGENT_PROVIDER must be one of: ollama, openai, anthropic.',
            ),
        };
    }

    private static function required(string $key, string $environmentVariable): string
    {
        $value = config("agents.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$environmentVariable} is required.");
        }

        return $value;
    }
}
```

- [ ] **Step 4: Run the provider tests to verify they pass**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php`

Expected: PASS with all provider-selection and validation cases green.

### Task 3: Delegate Existing Agents To The Adapter

**Files:**
- Modify: `app/Neuron/Agents/VehicleAgent.php:7-70`
- Modify: `app/Neuron/Agents/TranslationAgent.php:7-38`
- Modify: `tests/Feature/Neuron/Agents/VehicleAgentTest.php:7-87`
- Modify: `tests/Unit/Neuron/Agents/TranslationAgentTest.php:7-107`

- [ ] **Step 1: Update agent tests to require an adapter dependency**

In `VehicleAgentTest`, retain every existing direct construction with only its
`VehicleSearchTool` dependency.

Replace `test_the_production_provider_uses_the_configured_ollama_timeout()` with this
delegation test:

```php
public function test_the_production_provider_uses_the_configured_agent_provider(): void
{
    config()->set('agents', [
        'provider' => 'openai',
        'model' => 'gpt-4.1-mini',
        'api_key' => 'test-key',
    ]);

    $providerMethod = new ReflectionMethod(VehicleAgent::class, 'provider');
    $provider = $providerMethod->invoke(new VehicleAgent(
        new VehicleSearchTool(new VehicleAgentFakeRetriever([])),
    ));

    $this->assertInstanceOf(OpenAI::class, $provider);
}
```

Remove the `AmpHttpClient`, `Ollama`, and `ReflectionProperty` imports; import
`NeuronAI\Providers\OpenAI\OpenAI`.

In `TranslationAgentTest`, retain the existing zero-argument concrete-agent
construction, including dynamic construction from the data provider.

Replace the Ollama timeout test with the same OpenAI delegation assertion, invoking a
zero-argument `FrenchTranslationAgent`. Remove the obsolete Ollama and
Amp client imports.

- [ ] **Step 2: Run agent tests to verify they fail**

Run: `ddev php artisan test tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: FAIL because the agent constructors do not yet accept `AgentProvider` and
their provider methods still directly construct Ollama.

- [ ] **Step 3: Delegate each agent's provider method**

In `VehicleAgent`, remove the `AmpHttpClient` and `Ollama` imports, import
`App\Neuron\AgentProvider`, and retain its original constructor:

```php
public function __construct(
    private readonly VehicleSearchTool $vehicleSearchTool,
) {
    parent::__construct();
}
```

Replace its `provider()` body with:

```php
return AgentProvider::configuredProvider();
```

In `TranslationAgent`, remove the `AmpHttpClient` and `Ollama` imports, import
`App\Neuron\AgentProvider`, retain the inherited zero-argument construction, and
replace its `provider()` body with:

```php
return AgentProvider::configuredProvider();
```

- [ ] **Step 4: Run agent tests to verify they pass**

Run: `ddev php artisan test tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: PASS. Fake-provider behavior remains unchanged, while production-provider
resolution selects OpenAI through the adapter.

### Task 4: Verify The Complete Change

**Files:**
- Modify: `app/Neuron/AgentProvider.php`
- Modify: `app/Neuron/Agents/VehicleAgent.php`
- Modify: `app/Neuron/Agents/TranslationAgent.php`
- Modify: `config/agents.php`
- Modify: `.env.example`
- Test: `tests/Unit/Neuron/AgentProviderTest.php`
- Test: `tests/Feature/Neuron/Agents/VehicleAgentTest.php`
- Test: `tests/Unit/Neuron/Agents/TranslationAgentTest.php`

- [ ] **Step 1: Run the targeted provider and agent suite**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: PASS with no failures.

- [ ] **Step 2: Format changed PHP files**

Run: `ddev php vendor/bin/pint app/Neuron/AgentProvider.php app/Neuron/Agents/VehicleAgent.php app/Neuron/Agents/TranslationAgent.php config/agents.php tests/Unit/Neuron/AgentProviderTest.php tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: Pint reports PASS.

- [ ] **Step 3: Verify no whitespace errors**

Run: `git diff --check`

Expected: no output and exit code 0.

- [ ] **Step 4: Do not commit**

The user requested no commits without explicit instruction. Leave all intended changes
unstaged and report the exact verification output.
