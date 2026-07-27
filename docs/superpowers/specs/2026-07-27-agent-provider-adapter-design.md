# Agent Provider Adapter Design

## Goal

Allow every current and future Neuron agent to use the same configured chat provider
without provider-specific construction in agent classes. The selected provider is
controlled by environment configuration.

## Scope

This design covers Neuron chat providers used by agents and workflows. It does not
change RAG embeddings, Qdrant configuration, or RAG document indexing.

## Configuration

`config/agents.php` exposes the following settings:

| Environment variable | Default | Purpose |
| --- | --- | --- |
| `AGENT_PROVIDER` | `ollama` | Provider identifier: `ollama`, `openai`, or `anthropic`. |
| `AGENT_MODEL` | none | Model identifier passed to the selected provider. |
| `AGENT_API_KEY` | none | API key for OpenAI and Anthropic. |
| `AGENT_BASE_URL` | `http://host.docker.internal:11434/api` | Base URL for Ollama. |
| `AGENT_TIMEOUT` | `180` | HTTP timeout in seconds for Ollama. |

`AGENT_MODEL` is required for all provider selections. `AGENT_API_KEY` is required
when `AGENT_PROVIDER` is `openai` or `anthropic`. `AGENT_BASE_URL` and
`AGENT_TIMEOUT` apply only to Ollama.

The existing `RAG_OLLAMA_*` settings remain dedicated to embeddings and are not used
by chat agents.

## Adapter

`App\Neuron\AgentProvider` is the single provider-selection boundary. Its public
method has this signature:

```php
public static function configuredProvider(): AIProviderInterface
```

The method reads `config('agents')` and uses a `match` expression to create the
corresponding Neuron provider:

- `ollama`: `Ollama`, using model, base URL, and timeout;
- `openai`: Neuron's OpenAI provider, using model and API key;
- `anthropic`: Neuron's Anthropic provider, using model and API key.

`configuredProvider()` is static. It does not cache a provider instance; each call
returns a provider constructed from the current configuration. This keeps the factory
deterministic in tests and avoids sharing mutable provider state between agents. The
class does not have a Laravel container binding.

## Validation And Errors

`configuredProvider()` validates required values before creating a provider.

- An unsupported `AGENT_PROVIDER` value throws `InvalidArgumentException` that names
  the setting and lists `ollama`, `openai`, and `anthropic`.
- A missing or blank `AGENT_MODEL` throws `InvalidArgumentException` naming
  `AGENT_MODEL`.
- A missing or blank `AGENT_API_KEY` for OpenAI or Anthropic throws
  `InvalidArgumentException` naming `AGENT_API_KEY`.
- A missing or blank `AGENT_BASE_URL` for Ollama throws `InvalidArgumentException`
  naming `AGENT_BASE_URL`.

Validation occurs when an agent resolves its provider, so invalid configuration fails
before a request is sent to an external model service.

## Agent Integration

`VehicleAgent` and `TranslationAgent` retain their existing constructors. Their
protected `provider()` methods delegate to `AgentProvider::configuredProvider()`.

No agent class imports an individual provider implementation or reads environment
configuration. Future agents follow the same pattern, ensuring the global provider
selection automatically applies to every workflow that uses them.

## Tests

Unit tests for `AgentProvider` verify:

1. Ollama selection returns the Ollama provider with the configured model and base
   URL.
2. OpenAI selection returns the OpenAI provider with the configured model and API
   key.
3. Anthropic selection returns the Anthropic provider with the configured model and
   API key.
4. Unsupported provider values and missing required settings fail with clear
   `InvalidArgumentException` messages.

Existing agent tests verify their provider methods delegate to the static factory
rather than directly constructing Ollama, without adding constructor dependencies.
