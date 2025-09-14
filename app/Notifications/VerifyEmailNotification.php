<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Conferma il tuo indirizzo email')
            ->greeting('Ciao!')
            ->line('Per completare la registrazione, conferma il tuo indirizzo email.')
            ->action('Conferma email', $url)
            ->line('Se non hai creato un account, ignora questa email.')
            ->salutation('Un saluto,<br>Centro Pilates - Ada Turco');
    }
}
