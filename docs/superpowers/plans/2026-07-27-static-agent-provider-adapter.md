# Static Agent Provider Adapter Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the configured agent provider factory static so agents retain their original constructors.

**Architecture:** `AgentProvider::configuredProvider()` remains the single provider-selection boundary but becomes a static factory. `VehicleAgent` and `TranslationAgent` call it directly from their existing `provider()` overrides; the Laravel singleton binding and all constructor injection are removed.

**Tech Stack:** PHP 8.3, Laravel 13 configuration, Neuron AI 3.15, PHPUnit 12, Laravel Pint.

---

## File Structure

- Modify: `app/Neuron/AgentProvider.php` - make provider construction and validation static.
- Modify: `app/Neuron/Agents/VehicleAgent.php` - restore its one-argument constructor and use the static factory.
- Modify: `app/Neuron/Agents/TranslationAgent.php` - restore zero-argument construction and use the static factory.
- Modify: `app/Providers/AppServiceProvider.php` - remove the obsolete factory singleton binding.
- Modify: `tests/Unit/Neuron/AgentProviderTest.php` - call the factory statically.
- Modify: `tests/Feature/Neuron/Agents/VehicleAgentTest.php` - remove factory construction arguments.
- Modify: `tests/Unit/Neuron/Agents/TranslationAgentTest.php` - restore zero-argument concrete-agent construction.

### Task 1: Make The Factory Static

**Files:**
- Modify: `tests/Unit/Neuron/AgentProviderTest.php`
- Modify: `app/Neuron/AgentProvider.php`

- [ ] **Step 1: Change a factory test to require static invocation**

Replace one configured-provider invocation in `AgentProviderTest` with:

```php
$provider = AgentProvider::configuredProvider();

$this->assertInstanceOf(Ollama::class, $provider);
```

Keep the existing runtime configuration fixture for Ollama.

- [ ] **Step 2: Run the focused test to verify it fails**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php`

Expected: FAIL because `configuredProvider()` is not static.

- [ ] **Step 3: Convert factory methods to static methods**

In `app/Neuron/AgentProvider.php`, change the factory and helper declarations and
replace instance calls with `self::`:

```php
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
```

Replace every remaining `app(AgentProvider::class)->configuredProvider()` in the same
test with `AgentProvider::configuredProvider()`.

- [ ] **Step 4: Run the focused factory tests to verify they pass**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php`

Expected: PASS with all provider selection and invalid-configuration cases green.

### Task 2: Restore Agent Constructors

**Files:**
- Modify: `tests/Feature/Neuron/Agents/VehicleAgentTest.php`
- Modify: `tests/Unit/Neuron/Agents/TranslationAgentTest.php`
- Modify: `app/Neuron/Agents/VehicleAgent.php`
- Modify: `app/Neuron/Agents/TranslationAgent.php`

- [ ] **Step 1: Write constructor-free agent test expectations**

In `VehicleAgentTest`, remove `app(AgentProvider::class)` from every construction so
the test uses:

```php
$agent = new VehicleAgent($tool);
```

In the production-provider test, construct the agent with only its search tool:

```php
$provider = $providerMethod->invoke(new VehicleAgent(
    new VehicleSearchTool(new VehicleAgentFakeRetriever([])),
));
```

In `TranslationAgentTest`, remove `app(AgentProvider::class)` from every concrete and
dynamic construction:

```php
$agent = new FrenchTranslationAgent;
$agent = new $agentClass;
```

- [ ] **Step 2: Run focused agent tests to verify they fail**

Run: `ddev php artisan test tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: FAIL because both base classes still require `AgentProvider` constructor
arguments.

- [ ] **Step 3: Restore constructors and call the static factory**

In `VehicleAgent`, remove the `AgentProvider` constructor property and retain:

```php
public function __construct(private readonly VehicleSearchTool $vehicleSearchTool)
{
    parent::__construct();
}
```

Replace its provider method with:

```php
protected function provider(): AIProviderInterface
{
    return AgentProvider::configuredProvider();
}
```

In `TranslationAgent`, remove its constructor entirely so it inherits zero-argument
construction from `Agent`. Replace its provider method with the same static call:

```php
protected function provider(): AIProviderInterface
{
    return AgentProvider::configuredProvider();
}
```

- [ ] **Step 4: Run focused agent tests to verify they pass**

Run: `ddev php artisan test tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: PASS. Fake provider behavior and OpenAI provider-selection assertions remain
green without constructor changes.

### Task 3: Remove Container Registration And Verify

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Neuron/AgentProvider.php`
- Modify: `app/Neuron/Agents/VehicleAgent.php`
- Modify: `app/Neuron/Agents/TranslationAgent.php`
- Modify: `tests/Unit/Neuron/AgentProviderTest.php`
- Modify: `tests/Feature/Neuron/Agents/VehicleAgentTest.php`
- Modify: `tests/Unit/Neuron/Agents/TranslationAgentTest.php`

- [ ] **Step 1: Remove the obsolete service binding**

Remove the `use App\Neuron\AgentProvider;` import and this line from
`AppServiceProvider::register()`:

```php
$this->app->singleton(AgentProvider::class);
```

Do not alter the existing embeddings or Qdrant bindings.

- [ ] **Step 2: Run the complete relevant suite**

Run: `ddev php artisan test tests/Unit/Neuron/AgentProviderTest.php tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php tests/Unit/Providers/AppServiceProviderTest.php`

Expected: PASS. The provider factory works without a container binding and the RAG
bindings remain unchanged.

- [ ] **Step 3: Format and verify the diff**

Run: `ddev php vendor/bin/pint app/Neuron/AgentProvider.php app/Neuron/Agents/VehicleAgent.php app/Neuron/Agents/TranslationAgent.php app/Providers/AppServiceProvider.php tests/Unit/Neuron/AgentProviderTest.php tests/Feature/Neuron/Agents/VehicleAgentTest.php tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: Pint reports PASS.

Run: `git diff --check`

Expected: no output and exit code 0.

- [ ] **Step 4: Do not commit**

The user requested no commits without explicit instruction. Leave intended changes
unstaged and report verification output.
