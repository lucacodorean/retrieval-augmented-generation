# Email Query Workflow State Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the email-query workflow store its authoritative vehicle response, translate only its natural-language text in isolated parallel branches, and collect typed translations in its final state.

**Architecture:** `EmailQueryWorkflowState` owns the original response, current step, and final translations. Separate Romanian and French agents avoid sharing mutable agent chat history across concurrent branches; each branch returns a typed `Translation` through its terminal event, and `CollectorNode` is the only component that merges branch results into the main state.

**Tech Stack:** PHP 8.3, Laravel 13, Neuron AI 3.15, PHPUnit 12, Laravel Pint, Amp 3

---

### Task 1: Define typed workflow state and translation results

**Files:**
- Create: `app/Neuron/Workflows/EmailQuery/Data/Translation.php`
- Modify: `app/Neuron/Workflows/EmailQuery/EmailQueryWorkflowState.php`
- Test: `tests/Unit/Neuron/Workflows/EmailQuery/EmailQueryWorkflowStateTest.php`

- [ ] **Step 1: Write failing state tests**

Test that the state stores and returns the complete agent response, exposes only its natural-language source text, stores translations by locale, and preserves serialized records unchanged.

```php
public function test_it_exposes_the_original_response_and_collected_translations(): void
{
    $response = [
        'response' => [
            'natural-lang' => 'Two vehicles match.',
            'serialized' => [['record' => ['type' => 'vehicle'], 'score' => 0.91]],
        ],
    ];
    $state = new EmailQueryWorkflowState;

    $state->setOriginalResponse($response);
    $state->setCurrentStep(NodeState::QUERY_OBTAINED);
    $state->setTranslations([
        Language::ROMANIAN->value => new Translation(Language::ROMANIAN, 'Doua vehicule corespund.'),
    ]);

    $this->assertSame($response, $state->originalResponse());
    $this->assertSame('Two vehicles match.', $state->sourceText());
    $this->assertSame(NodeState::QUERY_OBTAINED, $state->currentStep());
    $this->assertSame('Doua vehicule corespund.', $state->translations()['ro']->text);
    $this->assertSame($response['response']['serialized'], $state->originalResponse()['response']['serialized']);
}
```

- [ ] **Step 2: Run the state test and confirm it fails**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/EmailQueryWorkflowStateTest.php`

Expected: FAIL because `Translation` and the typed state methods do not exist.

- [ ] **Step 3: Implement the typed result and state methods**

Create an immutable result:

```php
final readonly class Translation
{
    public function __construct(
        public Language $language,
        public string $text,
    ) {}
}
```

Implement `EmailQueryWorkflowState` with private keys and these methods:

```php
public function setCurrentStep(NodeState $step): void;
public function currentStep(): ?NodeState;

/** @param array{response: array{natural-lang: string, serialized: list<array{record: array<string, mixed>, score: float}>}} $response */
public function setOriginalResponse(array $response): void;

/** @return array{response: array{natural-lang: string, serialized: list<array{record: array<string, mixed>, score: float}>}} */
public function originalResponse(): array;

public function sourceText(): string;

/** @param array<string, Translation> $translations */
public function setTranslations(array $translations): void;

/** @return array<string, Translation> */
public function translations(): array;
```

- [ ] **Step 4: Run the state test and confirm it passes**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/EmailQueryWorkflowStateTest.php`

Expected: PASS.

### Task 2: Store the authoritative query response

**Files:**
- Modify: `app/Neuron/Workflows/EmailQuery/Nodes/RunQueryNode.php`
- Test: `tests/Unit/Neuron/Workflows/EmailQuery/Nodes/RunQueryNodeTest.php`

- [ ] **Step 1: Write a failing query-node test**

Mock `VehicleAgent::ask()` for a known query, invoke the node with `EmailQueryWorkflowState`, and assert that the complete response and `QUERY_OBTAINED` step are stored.

```php
$agent = Mockery::mock(VehicleAgent::class);
$agent->expects('ask')->with('quiet city cars')->andReturn($response);
$state = new EmailQueryWorkflowState;
$node = new RunQueryNode('quiet city cars', $agent);

$event = $node(new StartEvent, $state);

$this->assertInstanceOf(QueryObtainedEvent::class, $event);
$this->assertSame($response, $state->originalResponse());
$this->assertSame(NodeState::QUERY_OBTAINED, $state->currentStep());
```

- [ ] **Step 2: Run the query-node test and confirm it fails**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/Nodes/RunQueryNodeTest.php`

Expected: FAIL because the response is currently discarded.

- [ ] **Step 3: Store the response through the custom state**

Change the node signature to require `EmailQueryWorkflowState`, call `setCurrentStep()` before and after the agent call, and call `setOriginalResponse($response)` before returning `QueryObtainedEvent`.

- [ ] **Step 4: Run the query-node test and confirm it passes**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/Nodes/RunQueryNodeTest.php`

Expected: PASS.

### Task 3: Add independent language translation agents

**Files:**
- Create: `app/Neuron/Agents/TranslationAgent.php`
- Create: `app/Neuron/Agents/FrenchTranslationAgent.php`
- Create: `app/Neuron/Agents/RomanianTranslationAgent.php`
- Test: `tests/Unit/Neuron/Agents/TranslationAgentTest.php`

- [ ] **Step 1: Write failing agent tests**

Give each concrete agent a `FakeAIProvider` response and assert that `translate()` returns its text and that the configured instructions identify the correct target language.

```php
$agent = new FrenchTranslationAgent;
$agent->setAiProvider(new FakeAIProvider(new AssistantMessage('Deux vehicules correspondent.')));

$this->assertSame(
    'Deux vehicules correspondent.',
    $agent->translate('Two vehicles match.'),
);
```

Repeat for Romanian with a separate agent instance.

- [ ] **Step 2: Run the agent tests and confirm they fail**

Run: `ddev php artisan test tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: FAIL because the translation agents do not exist.

- [ ] **Step 3: Implement the agents**

Create an abstract `TranslationAgent` extending Neuron `Agent`. Its `translate(string $text): string` sends a `UserMessage` and returns the assistant content. It uses the existing Ollama URL and `qwen3:8b`, has no tools, and builds a strict system prompt from an abstract `language(): Language` method:

```php
abstract class TranslationAgent extends Agent
{
    public function translate(string $text): string
    {
        return $this->chat(new UserMessage($text))->getMessage()->getContent() ?? '';
    }

    abstract protected function language(): Language;
}
```

The system prompt must instruct the model to return only a faithful translation in the target language, preserving factual values and identifiers. `FrenchTranslationAgent` and `RomanianTranslationAgent` return their corresponding enum values.

- [ ] **Step 4: Run the agent tests and confirm they pass**

Run: `ddev php artisan test tests/Unit/Neuron/Agents/TranslationAgentTest.php`

Expected: PASS.

### Task 4: Return typed translation results from isolated branches

**Files:**
- Modify: `app/Neuron/Workflows/EmailQuery/Events/TranslationRequestDoneEvent.php`
- Modify: `app/Neuron/Workflows/EmailQuery/Events/FrenchTranslationDoneEvent.php`
- Modify: `app/Neuron/Workflows/EmailQuery/Events/RomanianTranslationDoneEvent.php`
- Modify: `app/Neuron/Workflows/EmailQuery/Nodes/FrenchTranslationNode.php`
- Modify: `app/Neuron/Workflows/EmailQuery/Nodes/RomanianTranslationNode.php`
- Test: `tests/Unit/Neuron/Workflows/EmailQuery/Nodes/TranslationNodeTest.php`

- [ ] **Step 1: Write failing branch-node tests**

For each language, put an original response into `EmailQueryWorkflowState`, mock the appropriate agent to expect only the natural-language text, invoke the node, and assert that the `StopEvent` result is a `Translation` with the expected language and translated text.

```php
$agent = Mockery::mock(FrenchTranslationAgent::class);
$agent->expects('translate')->with('Two vehicles match.')->andReturn('Deux vehicules correspondent.');
$state = $this->stateWithOriginalResponse();

$event = (new FrenchTranslationNode($agent))(
    new FrenchTranslationRequestIssuedEvent,
    $state,
);

$this->assertEquals(
    new Translation(Language::FRENCH, 'Deux vehicules correspondent.'),
    $event->getResult(),
);
```

- [ ] **Step 2: Run the branch-node tests and confirm they fail**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/Nodes/TranslationNodeTest.php`

Expected: FAIL because nodes return only locale markers.

- [ ] **Step 3: Implement typed terminal results**

Change `TranslationRequestDoneEvent` to accept a `Translation` and pass it to `StopEvent`. Make each concrete done event accept translated text and construct the correctly localized `Translation`. Inject the corresponding language agent into each node, read `$state->sourceText()`, and return the concrete done event with translated text.

- [ ] **Step 4: Run the branch-node tests and confirm they pass**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/Nodes/TranslationNodeTest.php`

Expected: PASS.

### Task 5: Merge branch results in the collector

**Files:**
- Modify: `app/Neuron/Workflows/EmailQuery/Nodes/CollectorNode.php`
- Modify: `app/Neuron/Workflows/EmailQuery/Middlewares/CollectorNodePushInformMiddleware.php`
- Test: `tests/Unit/Neuron/Workflows/EmailQuery/Nodes/CollectorNodeTest.php`

- [ ] **Step 1: Write a failing collector test**

Build a `ParallelEvent`, populate its named results with Romanian and French `Translation` objects through `setResult()`, invoke the collector, and assert that translations are stored by locale in the main state while the original serialized records remain unchanged.

```php
$event = new ParallelEvent([]);
$event->setResult('ro', new Translation(Language::ROMANIAN, 'Doua vehicule corespund.'));
$event->setResult('fr', new Translation(Language::FRENCH, 'Deux vehicules correspondent.'));

$result = (new CollectorNode)($event, $state);

$this->assertInstanceOf(StopEvent::class, $result);
$this->assertSame('Doua vehicule corespund.', $state->translations()['ro']->text);
$this->assertSame('Deux vehicules correspondent.', $state->translations()['fr']->text);
$this->assertSame($serialized, $state->originalResponse()['response']['serialized']);
```

- [ ] **Step 2: Run the collector test and confirm it fails**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/Nodes/CollectorNodeTest.php`

Expected: FAIL because the collector does not read branch results.

- [ ] **Step 3: Implement collection and remove misleading push behavior**

Require `EmailQueryWorkflowState`, validate every result is a `Translation`, key results by `$translation->language->value`, store them with `setTranslations()`, set `COLLECTED_TRANSLATIONS`, and return an empty `StopEvent`. Remove `CollectorNodePushInformMiddleware` and its registration because collecting no longer represents pushing or email delivery.

- [ ] **Step 4: Run the collector test and confirm it passes**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery/Nodes/CollectorNodeTest.php`

Expected: PASS.

### Task 6: Wire and verify the complete workflow

**Files:**
- Modify: `app/Neuron/Workflows/EmailQuery/EmailQueryWorkflow.php`
- Modify: `app/Http/Controllers/EmailSendController.php`
- Test: `tests/Feature/Neuron/Workflows/EmailQueryWorkflowTest.php`

- [ ] **Step 1: Write a failing workflow test**

Construct the workflow with a mocked `VehicleAgent` and separate French and Romanian agents, execute it, and assert the final state contains the original response, both translations, and unchanged serialized records.

```php
$state = (new EmailQueryWorkflow(
    query: 'quiet city cars',
    vehicleAgent: $vehicleAgent,
    romanianAgent: $romanianAgent,
    frenchAgent: $frenchAgent,
))->init()->run();

$this->assertInstanceOf(EmailQueryWorkflowState::class, $state);
$this->assertSame($response, $state->originalResponse());
$this->assertSame('Doua vehicule corespund.', $state->translations()['ro']->text);
$this->assertSame('Deux vehicules correspondent.', $state->translations()['fr']->text);
```

Add a failure test where one translation agent throws and assert that workflow execution
throws the same exception rather than returning a state marked as collected:

```php
$frenchAgent->expects('translate')
    ->andThrow(new RuntimeException('translation failed'));

$this->expectException(RuntimeException::class);
$this->expectExceptionMessage('translation failed');

(new EmailQueryWorkflow(
    query: 'quiet city cars',
    vehicleAgent: $vehicleAgent,
    romanianAgent: $romanianAgent,
    frenchAgent: $frenchAgent,
))->init()->run();
```

- [ ] **Step 2: Run the workflow test and confirm it fails**

Run: `ddev php artisan test tests/Feature/Neuron/Workflows/EmailQueryWorkflowTest.php`

Expected: the success test FAILS because dependencies and custom state are not wired;
the failure test establishes that branch exceptions propagate.

- [ ] **Step 3: Wire custom state and separate agent instances**

Update `EmailQueryWorkflow` to accept `VehicleAgent`, `RomanianTranslationAgent`, and `FrenchTranslationAgent`; pass each to its node. Default to a new `EmailQueryWorkflowState` when no state is injected, while preserving persistence and resume-token constructor parameters. Update `EmailSendController` constructor injection and workflow construction accordingly.

- [ ] **Step 4: Run focused workflow tests**

Run: `ddev php artisan test tests/Unit/Neuron/Workflows/EmailQuery tests/Feature/Neuron/Workflows/EmailQueryWorkflowTest.php`

Expected: PASS.

- [ ] **Step 5: Format and run the full suite**

Run: `ddev exec vendor/bin/pint app/Neuron/Agents app/Neuron/Workflows/EmailQuery app/Http/Controllers/EmailSendController.php tests/Unit/Neuron tests/Feature/Neuron/Workflows`

Run: `ddev php artisan test`

Expected: Pint exits successfully and all tests pass.
