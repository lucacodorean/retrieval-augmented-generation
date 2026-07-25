# README Workflows Design

## Goal

Extend the README with an introduction to Neuron's workflow model and apply those
concepts to the implemented multilingual email query workflow.

## Scope

Add one `Workflows` section to `README.md`. Keep the existing RAG lifecycle and index
synchronization content unchanged rather than presenting those concepts as Neuron
workflows.

## Concepts

The section briefly defines the four concepts needed to understand the implementation:

- nodes perform individual units of work;
- events route execution from one node to the next;
- workflow state retains the original response and collected translations;
- parallel events fan out isolated translation branches and join their results before
  collection.

## Implemented Workflow

An inline Mermaid flowchart mirrors `EmailQueryWorkflow` from query execution through
email delivery. It shows:

1. the workflow start and query node;
2. delegation after the query is obtained;
3. parallel Romanian and French translation branches;
4. collection after both translations complete;
5. email sending and workflow termination.

Every arrow is labelled with a friendly event name rather than a PHP class name. The
labels describe workflow start, query completion, each translation request and result,
parallel completion, the email-send request, and workflow stop.

## Accuracy Constraints

The diagram uses the current node order and event routing from
`app/Neuron/Workflows/EmailQuery`. It must not imply that serialized vehicle records are
translated: only the natural-language response enters the translation branches. The
collector joins both translations before email sending begins.

## Verification

Review the rendered Mermaid source for complete arrow labels and compare its nodes and
transitions with the workflow and node classes. Run the repository's Markdown checks if
any are configured.
