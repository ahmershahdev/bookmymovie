import { AnimatePresence, motion, useAnimationControls } from 'motion/react';
import { useEffect, useId, useRef, useState, type InputHTMLAttributes, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import { cn } from '@/lib/utils';

const ease = [0.16, 1, 0.3, 1] as const;

/**
 * Eye that opens and closes: the lid and the slash are drawn as paths so
 * the toggle animates rather than swapping icons.
 */
function Eye({ open }: { open: boolean }) {
    return (
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <motion.path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" animate={{ scaleY: open ? 1 : 0.35, opacity: open ? 1 : 0.7 }} style={{ originY: '50%' }} transition={{ duration: 0.35, ease }} />
            <motion.circle cx="12" cy="12" r="3" animate={{ scale: open ? 1 : 0, opacity: open ? 1 : 0 }} transition={{ duration: 0.3, ease }} />
            <motion.path d="M4 4l16 16" initial={false} animate={{ pathLength: open ? 0 : 1, opacity: open ? 0 : 1 }} transition={{ duration: 0.35, ease }} />
        </svg>
    );
}

/**
 * Password input with the show/hide toggle inside it, a caps-lock warning
 * and an optional "aside" link (forgot password) in the label row.
 */
export function PasswordField({ label, error, hint, aside, visible, onVisible, className, id, ...input }: {
    label: string; error?: string; hint?: ReactNode; aside?: ReactNode; visible?: boolean; onVisible?: (visible: boolean) => void; className?: string;
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'type'>) {
    const generated = useId();
    const inputId = id ?? generated;
    const [ownVisible, setOwnVisible] = useState(false);
    const [caps, setCaps] = useState(false);
    const shown = visible ?? ownVisible;
    const toggle = () => (onVisible ? onVisible(!shown) : setOwnVisible(!shown));

    return (
        <div className={cn('field', className)}>
            <div className="mb-2 flex items-baseline justify-between gap-3">
                <label htmlFor={inputId} className="label">{label}{input.required && <span className="text-accent"> *</span>}</label>
                {aside}
            </div>
            <div className="group/pw input-wrap relative">
                <Icon name="lock" size={17} className="input-icon" />
                <input id={inputId} type={shown ? 'text' : 'password'} aria-invalid={error ? true : undefined}
                    aria-describedby={cn(hint ? `${inputId}-hint` : null, error ? `${inputId}-error` : null, caps ? `${inputId}-caps` : null) || undefined}
                    onKeyUp={(event) => setCaps(event.getModifierState?.('CapsLock') ?? false)}
                    onBlur={() => setCaps(false)}
                    {...input} className="input has-icon pe-24 tracking-wide" />
                <button type="button" onClick={toggle} aria-pressed={shown} aria-controls={inputId} aria-label={shown ? 'Hide password' : 'Show password'}
                    className="absolute inset-y-1.5 end-1.5 flex items-center gap-2 px-3 text-[10px] font-bold uppercase tracking-[.1em] text-mute transition-colors hover:bg-ink-3 hover:text-paper focus-visible:text-paper [font-stretch:115%]">
                    <Eye open={shown} />
                    <span className="relative inline-grid overflow-hidden">
                        <AnimatePresence mode="popLayout" initial={false}>
                            <motion.span key={shown ? 'hide' : 'show'} initial={{ y: '100%' }} animate={{ y: 0 }} exit={{ y: '-100%' }} transition={{ duration: 0.3, ease }}>
                                {shown ? 'Hide' : 'Show'}
                            </motion.span>
                        </AnimatePresence>
                    </span>
                </button>
            </div>
            <AnimatePresence>
                {caps && (
                    <motion.p id={`${inputId}-caps`} initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }} exit={{ opacity: 0, height: 0 }} className="mt-2 flex items-center gap-1.5 text-xs text-accent">
                        <Icon name="alert" size={13} /> Caps Lock is on
                    </motion.p>
                )}
            </AnimatePresence>
            {hint && !error && <p id={`${inputId}-hint`} className="field-hint">{hint}</p>}
            {error && <p id={`${inputId}-error`} className="field-error" role="alert"><Icon name="alert" size={15} className="mt-0.5" /> {error}</p>}
        </div>
    );
}

/**
 * The primary action. Hover slides the arrow; while the request runs a light
 * sweeps across and three dots pulse; errors shake the button once.
 */
export function SubmitButton({ processing, hasErrors, children, busyLabel, className, icon = 'arrow-right' }: {
    processing: boolean; hasErrors?: boolean; children: ReactNode; busyLabel?: string; className?: string; icon?: string;
}) {
    const controls = useAnimationControls();
    const wasProcessing = useRef(false);

    useEffect(() => {
        if (wasProcessing.current && !processing && hasErrors) {
            controls.start({ x: [0, -9, 8, -6, 4, 0], transition: { duration: 0.45 } });
        }
        wasProcessing.current = processing;
    }, [processing, hasErrors, controls]);

    return (
        <motion.button type="submit" disabled={processing} animate={controls} whileTap={{ scale: 0.985 }} aria-busy={processing}
            className={cn('group/submit btn btn-primary btn-lg relative w-full overflow-hidden', className)}>
            <span className={cn('flex items-center justify-center gap-3 transition-opacity', processing && 'opacity-0')}>
                {children}
                <span className="relative grid h-5 w-5 place-items-center overflow-hidden">
                    <Icon name={icon} size={18} className="transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover/submit:translate-x-[140%]" />
                    <Icon name={icon} size={18} className="absolute -translate-x-[140%] transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover/submit:translate-x-0" />
                </span>
            </span>
            <AnimatePresence>
                {processing && (
                    <motion.span className="absolute inset-0 flex items-center justify-center gap-3" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}>
                        <span className="flex gap-1" aria-hidden="true">
                            {[0, 1, 2].map((dot) => (
                                <motion.span key={dot} className="h-1.5 w-1.5 bg-noir" animate={{ opacity: [0.25, 1, 0.25], y: [0, -3, 0] }} transition={{ duration: 0.9, repeat: Infinity, delay: dot * 0.15 }} />
                            ))}
                        </span>
                        {busyLabel}
                    </motion.span>
                )}
            </AnimatePresence>
            {processing && (
                <motion.span aria-hidden="true" className="pointer-events-none absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-white/40 to-transparent"
                    initial={{ left: '-35%' }} animate={{ left: '105%' }} transition={{ duration: 1.1, repeat: Infinity, ease: 'easeInOut' }} />
            )}
        </motion.button>
    );
}

/** "or" rule between social sign-in and the form. */
export function Divider({ children }: { children: ReactNode }) {
    return (
        <div className="my-7 flex items-center gap-4" role="separator">
            <span className="h-px flex-1 bg-line" /><span className="label">{children}</span><span className="h-px flex-1 bg-line" />
        </div>
    );
}

const EMAIL_DOMAINS = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'live.com', 'proton.me', 'protonmail.com', 'ymail.com', 'aol.com'];

/** Edit distance; only used to spot one- or two-letter domain typos. */
function distance(a: string, b: string): number {
    const row = Array.from({ length: b.length + 1 }, (_, index) => index);
    for (let i = 1; i <= a.length; i++) {
        let previous = row[0];
        row[0] = i;
        for (let j = 1; j <= b.length; j++) {
            const current = row[j];
            row[j] = Math.min(row[j] + 1, row[j - 1] + 1, previous + (a[i - 1] === b[j - 1] ? 0 : 1));
            previous = current;
        }
    }
    return row[b.length];
}

/** "ayesha@gmial.com" becomes "ayesha@gmail.com"; null when nothing looks off. */
export function emailSuggestion(email: string): string | null {
    const [local, domain] = email.trim().toLowerCase().split('@');
    if (!local || !domain || !domain.includes('.') || EMAIL_DOMAINS.includes(domain)) return null;
    const match = EMAIL_DOMAINS.map((known) => [known, distance(domain, known)] as const).sort((a, b) => a[1] - b[1])[0];
    return match && match[1] > 0 && match[1] <= 2 ? `${local}@${match[0]}` : null;
}

export const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

/**
 * Text input with a leading icon and a live status mark on the right: a
 * tick once the value is valid, the error when the server rejects it.
 */
export function AuthInput({ label, icon, error, hint, aside, valid, className, id, required, prefix, ...input }: {
    label: string; icon: string; error?: string; hint?: ReactNode; aside?: ReactNode; valid?: boolean; className?: string; prefix?: string;
} & InputHTMLAttributes<HTMLInputElement>) {
    const generated = useId();
    const inputId = id ?? generated;

    return (
        <div className={cn('field', className)}>
            <div className="flex items-baseline justify-between gap-3">
                <label htmlFor={inputId} className="field-label">{label}{required && <span className="text-accent" aria-hidden="true"> *</span>}</label>
                {aside}
            </div>
            <div className="input-wrap relative">
                <Icon name={icon} size={17} className="input-icon" />
                {prefix && <span className="num pointer-events-none absolute inset-y-0 start-[2.6rem] flex items-center text-mute">{prefix}</span>}
                <input id={inputId} required={required} aria-invalid={error ? true : undefined}
                    aria-describedby={cn(hint ? `${inputId}-hint` : null, error ? `${inputId}-error` : null) || undefined}
                    {...input} className={cn('input has-icon pe-11', prefix && '!ps-[3.6rem]')} />
                <AnimatePresence>
                    {valid && !error && (
                        <motion.span key="ok" initial={{ scale: 0, rotate: -45 }} animate={{ scale: 1, rotate: 0 }} exit={{ scale: 0 }} transition={{ type: 'spring', stiffness: 500, damping: 26 }}
                            className="absolute inset-y-0 end-3.5 my-auto grid h-5 w-5 place-items-center bg-mint text-noir" aria-hidden="true">
                            <Icon name="check" size={12} stroke={2.5} />
                        </motion.span>
                    )}
                </AnimatePresence>
            </div>
            {hint && !error && <div id={`${inputId}-hint`} className="field-hint">{hint}</div>}
            {error && <p id={`${inputId}-error`} className="field-error" role="alert"><Icon name="alert" size={15} className="mt-0.5" /> {error}</p>}
        </div>
    );
}

/** Email input that offers to fix common domain typos before submitting. */
export function EmailField({ value, onChange, error, hint, label = 'Email address', ...rest }: {
    value: string; onChange: (value: string) => void; error?: string; hint?: ReactNode; label?: string;
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'onChange'>) {
    const suggestion = emailSuggestion(value);

    return (
        <AuthInput label={label} icon="mail" type="email" inputMode="email" autoCapitalize="none" spellCheck={false} placeholder="you@example.com"
            value={value} onChange={(event) => onChange(event.target.value.trim())} error={error} valid={EMAIL_PATTERN.test(value) && !suggestion}
            hint={suggestion ? (
                <span>Did you mean <button type="button" className="link font-semibold text-accent" onClick={() => onChange(suggestion)}>{suggestion}</button>?</span>
            ) : hint}
            required maxLength={150} autoComplete="email" {...rest} />
    );
}

/** Pointer-tracking glow for .auth-card (CSS variables only, no re-renders). */
export function spotlight(event: React.PointerEvent<HTMLElement>) {
    const box = event.currentTarget.getBoundingClientRect();
    event.currentTarget.style.setProperty('--spot-x', `${event.clientX - box.left}px`);
    event.currentTarget.style.setProperty('--spot-y', `${event.clientY - box.top}px`);
}

/** A small reassurance strip under the primary action. */
export function TrustRow() {
    return (
        <ul className="mt-5 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[10px] font-semibold uppercase tracking-[.12em] text-dim [font-stretch:115%]">
            <li className="flex items-center gap-1.5"><Icon name="lock" size={12} /> Encrypted</li>
            <li className="flex items-center gap-1.5"><Icon name="shield" size={12} /> Bot-protected</li>
            <li className="flex items-center gap-1.5"><Icon name="mail" size={12} /> No spam, ever</li>
        </ul>
    );
}
