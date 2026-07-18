# ADR 0010: Use Model-Specific Read-Only Retrieval Tools

## Status

Accepted

## Context

Agents need semantic access to domain records, while vector collections and source
record types remain isolated.

## Decision

Use generic retrieval infrastructure with model-specific read-only tools. Each model
declares how its collection is searched and how retrieved metadata maps back to
current source records.

## Consequences

Agents receive narrowly scoped capabilities. New models reuse retrieval mechanics
without sharing collection access or exposing raw vectors.
