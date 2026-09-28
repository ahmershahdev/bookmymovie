import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useRef } from 'react';
import Icon from '@/components/Icon';
import { dismiss, notify, useToasts } from '@/lib/toast';
import { useShared } from '@/lib/utils';

/** Flash messages from the server and client-side notices, bottom centre. */
export default function Toaster() {
    const toasts = useToasts();
    const { flash } = useShared();
    const shown = useRef<string | null>(null);

    useEffect(() => {
        if (flash?.status && flash.id !== shown.current) {
            shown.current = flash.id;
            notify(flash.status, 'success', 5200);
        }
    }, [flash]);

    return (
        <div className="pointer-events-none fixed inset-x-4 bottom-4 z-[120] flex flex-col items-center gap-2 sm:bottom-6" aria-live="polite" role="status" data-print-hide>
            <AnimatePresence initial={false}>
                {toasts.map((toast) => (
                    <motion.div key={toast.id} layout initial={{ opacity: 0, y: 24, scale: 0.96 }} animate={{ opacity: 1, y: 0, scale: 1 }} exit={{ opacity: 0, y: 12, scale: 0.96 }}
                        transition={{ duration: 0.45, ease: [0.16, 1, 0.3, 1] }}
                        className="glass pointer-events-auto flex max-w-lg items-center gap-3 py-2.5 pl-3 pr-2 text-sm shadow-2xl shadow-black/60">
                        <span className={`grid h-6 w-6 shrink-0 place-items-center ${toast.tone === 'error' ? 'bg-signal text-noir' : toast.tone === 'success' ? 'bg-mint text-noir' : 'bg-volt text-noir'}`}>
                            <Icon name={toast.tone === 'error' ? 'alert' : toast.tone === 'success' ? 'check' : 'info'} size={14} stroke={2} />
                        </span>
                        <span className="pr-2">{toast.message}</span>
                        <button type="button" onClick={() => dismiss(toast.id)} className="grid h-7 w-7 place-items-center text-mute hover:text-paper" aria-label="Dismiss">
                            <Icon name="close" size={14} />
                        </button>
                    </motion.div>
                ))}
            </AnimatePresence>
        </div>
    );
}
