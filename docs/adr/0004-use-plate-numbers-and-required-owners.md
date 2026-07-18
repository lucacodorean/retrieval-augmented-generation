# ADR 0004: Use Plate Numbers and Required Owners

## Status

Accepted

## Context

The vehicle's existing `index` field represents its plate number, and vehicle
ownership is modelled by the existing user relationship.

## Decision

Store the plate number in the string `vehicles.index` column. Store ownership in the
required `vehicles.user_id` foreign key and prevent deleting a user while vehicles
still reference that user.

## Consequences

Plate numbers retain their original formatting, including letters and leading zeros.
Every persisted vehicle has a relational owner, and ownership must be reassigned or
the vehicle removed before its user can be deleted.
