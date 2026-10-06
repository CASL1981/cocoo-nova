<?php

namespace App\Notifications\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica al usuario recién creado su contraseña temporal de acceso.
 * No implementa ShouldQueue a propósito: por decisión de negocio, este correo
 * debe salir de forma síncrona, dentro del mismo request de creación del usuario.
 */
class TemporaryPasswordAssigned extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly string $temporaryPassword)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Acceso creado en '.config('app.name'))
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Se ha creado una cuenta para ti en '.config('app.name').'.')
            ->line('Tu contraseña temporal de acceso es:')
            ->line('**'.$this->temporaryPassword.'**')
            ->line('Por seguridad, se te pedirá definir una contraseña propia la primera vez que ingreses.')
            ->action('Iniciar sesión', route('login'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
