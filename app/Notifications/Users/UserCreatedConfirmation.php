<?php

namespace App\Notifications\Users;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirma al administrador que creó el usuario que la cuenta y la notificación
 * de clave temporal se enviaron correctamente. No incluye la contraseña:
 * por buenas prácticas, la clave solo debe circular hacia su dueño final.
 */
class UserCreatedConfirmation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly User $createdUser)
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
            ->subject('Usuario creado en '.config('app.name'))
            ->greeting('Hola '.$notifiable->name.',')
            ->line("Creaste exitosamente el usuario {$this->createdUser->name} {$this->createdUser->lastname} ({$this->createdUser->email}) en ".config('app.name').'.')
            ->line('Se le envió su contraseña temporal de acceso a su correo registrado.');
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
