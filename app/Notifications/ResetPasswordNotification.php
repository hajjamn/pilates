<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function __construct($token)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Reimposta la tua password')
            ->greeting('Ciao!')
            ->line('Hai richiesto di reimpostare la password del tuo account.')
            ->action('Reimposta password', $url)
            ->line("Questo link scade tra {$minutes} minuti.")
            ->line('Se non hai richiesto il reset, ignora questa email.')
            ->salutation('Un saluto,<br>Centro Pilates - Ada Turco');
    }
}
