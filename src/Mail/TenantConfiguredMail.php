<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantConfiguredMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $tenant
     * @param  \Illuminate\Database\Eloquent\Model|null  $user  Usuário criado (null quando apenas atualização)
     * @param  string|null  $plainPassword  Senha em texto para o primeiro acesso (null = email de atualização)
     */
    public function __construct(
        public $tenant,
        public $user,
        public ?string $plainPassword = null
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->tenant->getAttribute('name') ?? config('app.name');
        $subject = $this->plainPassword
            ? __('Seu ambiente foi configurado - :name', ['name' => $name])
            : __('Atualização do seu ambiente - :name', ['name' => $name]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'laravel-raptor::emails.tenant-configured',
        );
    }
}
