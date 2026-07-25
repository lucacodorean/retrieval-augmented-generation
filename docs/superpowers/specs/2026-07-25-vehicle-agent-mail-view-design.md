# Vehicle Agent Mail View Design

## Goal

Render a readable email containing the agent's natural-language message and, when
available, a table of serialized vehicle results.

## Input Contract

`VehicleAgentMail` receives:

- a message string;
- a language name used to prefix the email subject;
- the `response.serialized` vehicle-result list returned by `VehicleAgent`.

Each list item contains `record` and `score`. The email ignores `score` and uses only
the safe fields already exposed by `VehicleResource`.

## Presentation

Use Laravel's Markdown mailable renderer and default responsive mail theme. The Blade
template uses `<x-mail::message>` and `<x-mail::table>` rather than a custom HTML
document.

The message appears before the results. It is escaped and retains line breaks.

## Vehicle Table

When serialized vehicles exist, display these columns in order:

1. `Index` from `record.attributes.index`;
2. `VIN` from `record.attributes.vin`;
3. `Brand` from `record.relationships.vehicle_details.brand`;
4. `Model` from `record.relationships.vehicle_details.model`;
5. `HP` from `record.relationships.vehicle_details.hp`;
6. `Fuel` from `record.relationships.vehicle_details.fuel`.

Do not display the vehicle-details ID, similarity score, or outer vehicle record ID.
When the serialized list is empty, omit the complete table section, including its
heading.

## Integration

`VehicleAgentMail` configures `mail.vehicle-agent-mail` through the `markdown` content
option and exposes the message and serialized vehicle list. The `.blade.php` extension
remains because Blade processes components, loops, and variables. The view performs
presentation only and does not query models or transform domain records.

## Tests

Rendering tests verify that:

1. the language prefixes the email subject;
2. the message is present and HTML is escaped;
3. all six columns and their values render in the required order for a populated result;
4. details ID, similarity score, and outer vehicle record ID are absent;
5. the table and results heading are absent for an empty list.
