{{--
    Contenedor global de notificaciones (evento Livewire "toast" despachado por el trait Notifies).
    Cada notificación se cierra sola: éxito/info a los 4 s y error/advertencia a los 7 s
    (más tiempo para leer el motivo). El temporizador se pausa mientras el cursor está encima.
--}}
<div x-data="{
        toasts: [],
        durations: { success: 4000, info: 4000, warning: 7000, error: 7000 },
        push(type, message) {
            this.toasts.push({ id: Date.now() + Math.random(), type, message });
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
     }"
     x-on:toast.window="push($event.detail.type ?? 'success', $event.detail.message)"
     class="position-fixed top-0 end-0 p-3" style="z-index: 1090; margin-top: .5rem;">

    <template x-for="toast in toasts" :key="toast.id">
        <div x-data="{
                timer: null,
                start() { this.timer = setTimeout(() => remove(toast.id), durations[toast.type] ?? 4000); },
                pause() { clearTimeout(this.timer); }
             }"
             x-init="start()"
             x-on:mouseenter="pause()"
             x-on:mouseleave="start()"
             x-transition.opacity.duration.300ms
             class="toast show align-items-center text-white border-0 mb-2 shadow-lg"
             style="min-width: 380px; max-width: 480px; font-size: 1.05rem;"
             :class="{
                'bg-success': toast.type === 'success',
                'bg-danger': toast.type === 'error',
                'bg-warning text-dark': toast.type === 'warning',
                'bg-info': toast.type === 'info'
             }">
            <div class="d-flex align-items-center py-2">
                <div class="toast-body flex-grow-1" x-text="toast.message"></div>
                <button type="button" class="btn-close btn-close-white me-3" x-on:click="remove(toast.id)"></button>
            </div>
        </div>
    </template>
</div>
