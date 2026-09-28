import { useSyncExternalStore } from 'react';

export interface Toast {
    id: number;
    message: string;
    tone: 'info' | 'success' | 'error';
}

const listeners = new Set<() => void>();
let toasts: Toast[] = [];
let counter = 0;

function emit() {
    listeners.forEach((listener) => listener());
}

export function notify(message: string, tone: Toast['tone'] = 'info', duration = 4200) {
    const id = ++counter;
    toasts = [...toasts.slice(-2), { id, message, tone }];
    emit();
    window.setTimeout(() => dismiss(id), duration);
}

export function dismiss(id: number) {
    toasts = toasts.filter((toast) => toast.id !== id);
    emit();
}

export function useToasts(): Toast[] {
    return useSyncExternalStore(
        (listener) => {
            listeners.add(listener);
            return () => listeners.delete(listener);
        },
        () => toasts,
        () => toasts,
    );
}
