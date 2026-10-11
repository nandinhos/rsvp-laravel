import { reactive } from 'vue';

/**
 * Store singleton de toasts com auto-dismiss. Padrão de notificação com ação
 * de "desfazer" (undo) absorvido do rsvp-credencial, adaptado ao Inertia:
 * o undo dispara uma nova visita ao servidor (rota inversa), não altera estado local.
 */
const state = reactive({
    toasts: [],
});

let counter = 0;
const DEFAULT_TIMEOUT = 7000;

function dismiss(id) {
    const index = state.toasts.findIndex((t) => t.id === id);
    if (index !== -1) {
        const [removed] = state.toasts.splice(index, 1);
        if (removed?.timer) clearTimeout(removed.timer);
    }
}

/**
 * @param {{ type?: 'success'|'warning'|'info', title: string, message?: string,
 *          undoLabel?: string, onUndo?: () => void, timeout?: number }} toast
 */
function push(toast) {
    const id = `toast-${Date.now()}-${counter++}`;
    const timeout = toast.timeout ?? DEFAULT_TIMEOUT;
    const timer = setTimeout(() => dismiss(id), timeout);

    state.toasts.push({
        id,
        type: toast.type ?? 'success',
        title: toast.title,
        message: toast.message ?? '',
        undoLabel: toast.undoLabel ?? null,
        onUndo: toast.onUndo ?? null,
        timer,
    });

    return id;
}

export function useToast() {
    return {
        toasts: state.toasts,
        push,
        dismiss,
        success: (title, message, opts = {}) => push({ ...opts, type: 'success', title, message }),
        info: (title, message, opts = {}) => push({ ...opts, type: 'info', title, message }),
        warning: (title, message, opts = {}) => push({ ...opts, type: 'warning', title, message }),
    };
}
