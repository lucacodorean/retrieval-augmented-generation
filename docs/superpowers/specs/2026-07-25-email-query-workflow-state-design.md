# Email Query Workflow State Design

## Goal

Give each email-query workflow node its real data-processing responsibility while
preserving safe serialized vehicle records and Neuron's parallel branch isolation.

The workflow ends after collecting Romanian and French translations. Queue-backed
workflow tracking and email delivery are separate changes.

## Output Contract

The final workflow state contains:

- the original `VehicleAgent` response;
- translations keyed by locale.

Only `response.natural-lang` is translated. `response.serialized` remains unchanged
and is not copied into translation branch results.

## Workflow State

`EmailQueryWorkflowState` extends Neuron's `WorkflowState`. It provides explicit
methods for storing and retrieving:

- the current workflow step;
- the original vehicle-agent response;
- the collected translations.

Nodes use these methods rather than accessing workflow-state string keys directly.
The workflow creates this state by default while still accepting an injected state for
resumption and tests.

## Node Responsibilities

### Run Query

`RunQueryNode` asks `VehicleAgent` the supplied query, stores its complete response in
the workflow state, and emits `QueryObtainedEvent`.

### Delegate

`DelegatorNode` creates one concrete request event per configured language and returns
them in a `ParallelEvent`. It does not copy the source response into each event.

### Translate

Each translation node reads `response.natural-lang` from its cloned branch state and
passes that text to its language-specific translator. It returns a terminal result
containing the target `Language` and translated text.

Translation nodes do not write results into branch state because Neuron clones state
for branch isolation and does not merge branch mutations back into the main state.

### Collect

`CollectorNode` reads all terminal branch results from
`ParallelEvent::getAllResults()`. It validates that each result has a language and
translated text, builds a locale-keyed translation map, stores that map in the main
workflow state, and terminates the workflow.

## Data Flow

```text
query
  -> RunQueryNode
  -> state.originalResponse
  -> DelegatorNode
       -> RomanianTranslationNode -> { language: ro, text: ... }
       -> FrenchTranslationNode   -> { language: fr, text: ... }
  -> CollectorNode
  -> state.translations
  -> stop
```

## Failure Behavior

A query or translation failure fails the workflow. The collector does not report a
partially translated result as successful. A future queue job is responsible for
recording the run as failed and applying its retry policy.

## Tests

Tests verify that:

1. `RunQueryNode` stores the complete agent response.
2. Translation nodes receive only the natural-language source text.
3. Translation terminal results contain both language and translated text.
4. `CollectorNode` merges all branch results by locale into the main state.
5. Serialized vehicle records remain identical to the original agent response.
6. The complete workflow returns the original response and both translations.
7. A failed branch prevents the workflow from reporting successful collection.
