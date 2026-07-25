<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\VehicleAgentMail;
use Tests\TestCase;

class VehicleAgentMailTest extends TestCase
{
    public function test_it_renders_an_escaped_multiline_message_and_vehicle_details(): void
    {
        $mail = new VehicleAgentMail(
            agentText: "First line\n<script>alert('x')</script>",
            language: 'French',
            vehicles: [[
                'record' => [
                    'type' => 'vehicle',
                    'id' => 918273,
                    'attributes' => [
                        'index' => 'DU-181-FQ',
                        'vin' => 'ERHGWLWG9Z9DYU4WD',
                    ],
                    'relationships' => [
                        'vehicle_details' => [
                            'id' => 42,
                            'brand' => 'Suzuki',
                            'model' => '<strong>Swift</strong>',
                            'hp' => 175,
                            'fuel' => 'electric',
                        ],
                    ],
                ],
                'score' => 0.123456,
            ]],
        );

        $this->assertSame('French - Vehicle Agent Mail', $mail->envelope()->subject);
        $this->assertSame('mail.vehicle-agent-mail', $mail->content()->markdown);

        $html = $mail->render();

        $this->assertStringContainsString('&lt;script&gt;alert', $html);
        $this->assertStringContainsString('&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertMatchesRegularExpression('/First line<br\s*\/?>\s*&lt;script&gt;/', $html);
        $this->assertMatchesRegularExpression('/<h2[^>]*>\s*Vehicle details\s*<\/h2>/', $html);

        $this->assertMatchesRegularExpression(
            '/<tr[^>]*>\s*'
            .'<th[^>]*>\s*Index\s*<\/th>\s*'
            .'<th[^>]*>\s*VIN\s*<\/th>\s*'
            .'<th[^>]*>\s*Brand\s*<\/th>\s*'
            .'<th[^>]*>\s*Model\s*<\/th>\s*'
            .'<th[^>]*>\s*HP\s*<\/th>\s*'
            .'<th[^>]*>\s*Fuel\s*<\/th>\s*'
            .'<\/tr>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<tr[^>]*>\s*'
            .'<td[^>]*>\s*DU-181-FQ\s*<\/td>\s*'
            .'<td[^>]*>\s*ERHGWLWG9Z9DYU4WD\s*<\/td>\s*'
            .'<td[^>]*>\s*Suzuki\s*<\/td>\s*'
            .'<td[^>]*>\s*&lt;strong&gt;Swift&lt;\/strong&gt;\s*<\/td>\s*'
            .'<td[^>]*>\s*175\s*<\/td>\s*'
            .'<td[^>]*>\s*electric\s*<\/td>\s*'
            .'<\/tr>/',
            $html,
        );
        $this->assertStringNotContainsString('<strong>Swift</strong>', $html);

        $this->assertDoesNotMatchRegularExpression('/<td[^>]*>\s*42\s*<\/td>/', $html);
        $this->assertStringNotContainsString('0.123456', $html);
        $this->assertStringNotContainsString('918273', $html);
    }

    public function test_it_omits_vehicle_details_when_there_are_no_results(): void
    {
        $html = (new VehicleAgentMail(
            agentText: 'No matching vehicles were found.',
            language: 'French',
            vehicles: [],
        ))->render();

        $this->assertStringContainsString('No matching vehicles were found.', $html);
        $this->assertDoesNotMatchRegularExpression('/<h2[^>]*>\s*Vehicle details\s*<\/h2>/', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]*class="[^"]*\btable\b[^"]*"[^>]*>\s*<table\b/',
            $html,
        );
    }
}
