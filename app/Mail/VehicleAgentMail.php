<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleAgentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{
     *     record: array{
     *         type: string,
     *         id: int,
     *         attributes: array{index: string, vin: string},
     *         relationships: array{
     *             vehicle_details: array{id: int, brand: string, model: string, hp: int, fuel: string}
     *         }
     *     },
     *     score: float
     * }>  $vehicles
     */
    public function __construct(
        public readonly string $agentText,
        public readonly string $language,
        public readonly array $vehicles,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->language.' - Vehicle Agent Mail',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.vehicle-agent-mail',
            with: [
                'agentText' => $this->agentText,
                'vehicles' => $this->vehicles,
            ]
        );
    }
}
