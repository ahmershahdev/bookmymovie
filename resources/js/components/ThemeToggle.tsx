import { AnimatePresence, motion } from 'motion/react';
import { useTheme } from '@/lib/theme';
import { cn } from '@/lib/utils';

/** Sun / moon switch; the icon rotates out as the other rotates in. */
export default function ThemeToggle({ className }: { className?: string }) {
    const [theme, toggle] = useTheme();
    const light = theme === 'light';

    return (
        <button type="button" onClick={toggle} aria-label={light ? 'Switch to dark mode' : 'Switch to light mode'} title={light ? 'Dark mode' : 'Light mode'}
            className={cn('btn btn-ghost btn-icon', className)}>
            <AnimatePresence mode="wait" initial={false}>
                <motion.svg key={theme} width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" aria-hidden="true"
                    initial={{ rotate: -90, scale: 0.5, opacity: 0 }} animate={{ rotate: 0, scale: 1, opacity: 1 }} exit={{ rotate: 90, scale: 0.5, opacity: 0 }} transition={{ duration: 0.3 }}>
                    {light ? (
                        <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z" />
                    ) : (
                        <>
                            <circle cx="12" cy="12" r="4" />
                            <path d="M12 2.5v2M12 19.5v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2.5 12h2M19.5 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" />
                        </>
                    )}
                </motion.svg>
            </AnimatePresence>
        </button>
    );
}
