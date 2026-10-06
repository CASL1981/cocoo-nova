<div x-data="{
        toasts: [],
        push(type, message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message });
            setTimeout(() => this.remove(id), 4500);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
     }"
     x-on:toast.window="push($event.detail.type ?? 'success', $event.detail.message)"
     class="position-fixed top-0 end-0 p-3" style="z-index: 1090; margin-top: .5rem;">

    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast show align-items-center text-white border-0 mb-2 shadow-lg"
             style="min-width: 380px; font-size: 1.05rem;"
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
