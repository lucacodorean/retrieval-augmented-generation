# Vehicle Agent Mail View Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Render the vehicle-agent message and safe serialized vehicle details with Laravel's Markdown mail renderer.

**Architecture:** `VehicleAgentMail` receives the natural-language text, language-specific subject prefix, and serialized result list, then configures a Laravel Markdown Blade view. The view uses Laravel mail components, escapes dynamic values, and conditionally renders the table only when results exist.

**Tech Stack:** PHP 8.3, Laravel 13 Mailables and Blade, PHPUnit 12, Laravel Pint

---

### Task 1: Define and verify the mail rendering contract

**Files:**
- Modify: `app/Mail/VehicleAgentMail.php`
- Test: `tests/Feature/Mail/VehicleAgentMailTest.php`

- [ ] **Step 1: Write failing rendering tests**

Construct the mailable with text containing HTML and one serialized vehicle result. Render it and assert the text is escaped; the six headers and their values are present in order; and both IDs and the score are absent. Add a second test asserting an empty result list renders the message without the results heading or table.

```php
$mail = new VehicleAgentMail(
    agentText: "Quiet <script>alert('x')</script> vehicle.",
    vehicles: [[
        'record' => [
            'type' => 'vehicle',
            'id' => 999,
            'attributes' => ['index' => 'DU-181-FQ', 'vin' => 'ERHGWLWG9Z9DYU4WD'],
            'relationships' => [
                'vehicle_details' => [
                    'id' => 42,
                    'brand' => 'Suzuki',
                    'model' => 'quos',
                    'hp' => 175,
                    'fuel' => 'electric',
                ],
            ],
        ],
        'score' => 0.123456,
    ]],
);

$html = $mail->render();

$this->assertStringContainsString('Quiet &lt;script&gt;', $html);
$this->assertStringContainsString('Vehicle details', $html);
$this->assertStringContainsString('DU-181-FQ', $html);
$this->assertStringNotContainsString('0.123456', $html);
$this->assertStringNotContainsString('>999<', $html);
$this->assertStringNotContainsString('>42<', $html);
```

- [ ] **Step 2: Run the focused test and confirm it fails**

Run: `ddev php artisan test tests/Feature/Mail/VehicleAgentMailTest.php`

Expected: FAIL because the mailable has no input contract and the placeholder view renders no content.

- [ ] **Step 3: Implement the mailable contract**

Add strict types and a constructor with immutable public values. Use `agentText` rather than Laravel's reserved mail-view `$message` variable.

```php
/** @param list<array{record: array<string, mixed>, score: float}> $vehicles */
public function __construct(
    public readonly string $agentText,
    public readonly string $language,
    public readonly array $vehicles,
) {}
```

Configure `content()` with `markdown: 'mail.vehicle-agent-mail'`, retain the current envelope, and remove unused imports.

- [ ] **Step 4: Run the focused test and confirm it still fails only on view output**

Run: `ddev php artisan test tests/Feature/Mail/VehicleAgentMailTest.php`

Expected: assertions now reach the rendered placeholder but fail because the message/table markup is absent.

### Task 2: Build the Laravel Markdown Blade view

**Files:**
- Modify: `resources/views/mail/vehicle-agent-mail.blade.php`
- Test: `tests/Feature/Mail/VehicleAgentMailTest.php`

- [ ] **Step 1: Implement email-safe markup**

Replace the custom HTML document with Laravel Markdown mail components. Render escaped
message text inside the message component:

```blade
<x-mail::message>
{!! nl2br(e($agentText)) !!}
</x-mail::message>
```

When `$vehicles` is non-empty, render a `Vehicle details` heading and an
`<x-mail::table>` with columns in this exact order: `Index`, `VIN`, `Brand`, `Model`,
`HP`, `Fuel`. Read values from:

```blade
@foreach ($vehicles as $result)
    @php
        $record = $result['record'];
        $details = $record['relationships']['vehicle_details'];
        $attributes = $record['attributes'];
    @endphp
@endforeach
```

Use escaped Blade output for every cell. Do not render vehicle-details ID, `score`, or
outer `record.id`. Wrap the complete heading and table in
`@if (count($vehicles) > 0)`.

- [ ] **Step 2: Run focused rendering tests**

Run: `ddev php artisan test tests/Feature/Mail/VehicleAgentMailTest.php`

Expected: PASS.

- [ ] **Step 3: Format and verify**

Run: `ddev exec vendor/bin/pint app/Mail/VehicleAgentMail.php tests/Feature/Mail/VehicleAgentMailTest.php`

Run: `ddev php artisan test tests/Feature/Mail/VehicleAgentMailTest.php`

Run: `ddev php artisan test`

Expected: formatting succeeds and all tests pass.
