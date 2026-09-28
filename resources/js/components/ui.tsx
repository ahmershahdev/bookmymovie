import { Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import { useEffect, useId, useState, type InputHTMLAttributes, type ReactNode, type TextareaHTMLAttributes } from 'react';
import Dropdown from '@/components/Dropdown';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import { cn, useShared } from '@/lib/utils';
import type { Crumb, PageLink } from '@/types';

/**
 * Breadcrumbs as a ticket stub trail: crumbs slide in one by one, links
 * underline on hover, the current page carries a volt marker, and on small
 * screens the middle of a long trail folds into "…" (tap to unfold).
 */
export function Breadcrumbs({ items, className }: { items?: Crumb[]; className?: string }) {
    const shared = useShared();
    const reduce = useReducedMotion();
    const [unfolded, setUnfolded] = useState(false);
    const crumbs = items ?? shared.meta?.breadcrumbs ?? [];
    if (crumbs.length < 2) return null;
    const fold = crumbs.length > 3 && !unfolded;

    return (
        <nav aria-label="Breadcrumb" className={cn('no-scrollbar overflow-x-auto', className)}>
            <ol className="flex w-max items-center gap-1 text-[11px] font-semibold uppercase tracking-[.1em] text-mute [font-stretch:115%]">
                {crumbs.map((item, index) => {
                    const last = index === crumbs.length - 1;
                    const hiddenOnMobile = fold && index > 0 && index < crumbs.length - 2;
                    return (
                        <motion.li key={`${item.label}-${index}`} className={cn('flex items-center gap-1', hiddenOnMobile && 'max-sm:hidden')}
                            initial={reduce ? false : { opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ duration: 0.5, delay: 0.08 * index, ease: [0.16, 1, 0.3, 1] }}>
                            {index > 0 && (
                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" className="text-dim rtl:rotate-180">
                                    <motion.path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" strokeWidth="1.5" initial={reduce ? false : { pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 0.4, delay: 0.08 * index + 0.1 }} />
                                </svg>
                            )}
                            {index === 1 && fold && (
                                <button type="button" onClick={() => setUnfolded(true)} className="px-2 py-1 text-dim hover:text-paper sm:hidden" aria-label="Show the full path">…</button>
                            )}
                            {last ? (
                                <span aria-current="page" className="flex items-center gap-2 bg-ink-3 px-2.5 py-1.5 text-paper">
                                    <span className="h-1.5 w-1.5 bg-volt" aria-hidden="true" />
                                    <span className="max-w-[14rem] truncate">{item.label}</span>
                                </span>
                            ) : item.url ? (
                                <Link href={item.url} className="group relative flex items-center gap-1.5 px-2 py-1.5 transition-colors hover:text-paper">
                                    {index === 0 && <Icon name="film" size={13} className="text-accent" />}
                                    {item.label}
                                    <span className="absolute inset-x-2 bottom-0.5 h-px origin-left scale-x-0 bg-volt transition-transform duration-300 group-hover:scale-x-100" aria-hidden="true" />
                                </Link>
                            ) : (
                                <span className="px-2 py-1.5">{item.label}</span>
                            )}
                        </motion.li>
                    );
                })}
            </ol>
        </nav>
    );
}

/** Section title: a numbered label over a condensed headline. */
export function SectionHeading({ label, title, description, id, index, accent, align = 'left', className, children }: {
    label?: string;
    title: string;
    description?: ReactNode;
    id?: string;
    index?: string;
    accent?: string[];
    align?: 'left' | 'center';
    className?: string;
    children?: ReactNode;
}) {
    return (
        <div className={cn(align === 'center' ? 'mx-auto max-w-4xl text-center' : 'max-w-4xl', className)}>
            {label && (
                <p className={cn('label flex items-center gap-3', align === 'center' && 'justify-center')}>
                    {index && <span className="num text-accent">{index}</span>}
                    {index && <span className="h-px w-8 bg-line-2" aria-hidden="true" />}
                    {label}
                </p>
            )}
            <SplitHeading as="h2" text={title} accent={accent} id={id} className="mt-5 text-[clamp(3rem,8vw,7.5rem)]" />
            {description && <p className={cn('lede mt-6 max-w-2xl', align === 'center' && 'mx-auto')}>{description}</p>}
            {children}
        </div>
    );
}

/** Top of an inner page: breadcrumbs, label, giant title, optional lede. */
export function PageHeader({ label, title, lede, crumbs, accent, children }: {
    label?: string | null;
    title: string;
    lede?: ReactNode;
    crumbs?: Crumb[];
    accent?: string[];
    children?: ReactNode;
}) {
    return (
        <header className="relative border-b border-line pb-12 pt-[calc(var(--header)+3rem)] sm:pb-16 sm:pt-[calc(var(--header)+4.5rem)]">
            <div className="shell">
                <Breadcrumbs items={crumbs} className="mb-10" />
                <div className="grid gap-8 lg:grid-cols-12 lg:items-end">
                    <div className="lg:col-span-8">
                        {label && <p className="label label-accent">{label}</p>}
                        <SplitHeading as="h1" text={title} accent={accent} className="mt-5 text-[clamp(3.5rem,11vw,10.5rem)]" />
                    </div>
                    {lede && <p className="lede lg:col-span-4 lg:pb-3">{lede}</p>}
                </div>
                {children}
            </div>
        </header>
    );
}

export function StarRating({ rating, size = 14, className }: { rating: number; size?: number; className?: string }) {
    const value = Math.max(0, Math.min(5, Number(rating) || 0));

    return (
        <span className={cn('inline-flex items-center gap-0.5', className)} role="img" aria-label={`Rated ${value.toFixed(1)} out of 5`}>
            {[1, 2, 3, 4, 5].map((star) => {
                const fill = Math.max(0, Math.min(1, value - (star - 1)));
                return (
                    <span key={star} className="relative inline-block" style={{ width: size, height: size }}>
                        <Icon name="star" size={size} className="absolute inset-0 text-dim" />
                        <span className="absolute inset-y-0 left-0 overflow-hidden" style={{ width: `${fill * 100}%` }}>
                            <Icon name="star" size={size} className="fill-accent text-accent" />
                        </span>
                    </span>
                );
            })}
        </span>
    );
}

export function EmptyState({ icon, title, children, action }: { icon: string; title: string; children?: ReactNode; action?: ReactNode }) {
    return (
        <div className="panel flex flex-col items-center px-6 py-20 text-center">
            <Icon name={icon} size={36} className="text-dim" />
            <p className="display mt-6 text-5xl sm:text-6xl">{title}</p>
            {children && <p className="lede mt-4 max-w-md">{children}</p>}
            {action && <div className="mt-8">{action}</div>}
        </div>
    );
}

export function Pagination({ links, className }: { links: PageLink[]; className?: string }) {
    if (links.length <= 3) return null;

    return (
        <nav aria-label="Pagination" className={cn('flex flex-wrap items-center gap-1', className)}>
            {links.map((link, index) => {
                const label = link.label.replace('&laquo;', '←').replace('&raquo;', '→').replace(/Previous|Next/, '').trim();
                return link.url ? (
                    <Link key={index} href={link.url} preserveScroll={false} aria-current={link.active ? 'page' : undefined}
                        className={cn('num grid h-11 min-w-11 place-items-center border px-3 text-sm transition', link.active ? 'border-paper bg-paper text-ink' : 'border-line-2 text-paper-2 hover:border-paper')}>
                        {label}
                    </Link>
                ) : (
                    <span key={index} className="num grid h-11 min-w-11 place-items-center border border-line px-3 text-sm text-dim">{label}</span>
                );
            })}
        </nav>
    );
}

export function Alert({ tone = 'info', icon, children }: { tone?: 'info' | 'error' | 'success'; icon?: string; children: ReactNode }) {
    return (
        <div className={`alert alert-${tone}`} role={tone === 'error' ? 'alert' : 'status'}>
            <Icon name={icon ?? (tone === 'error' ? 'alert' : tone === 'success' ? 'check' : 'info')} size={18} className="mt-0.5" />
            <div>{children}</div>
        </div>
    );
}

/* Forms --------------------------------------------------------------------- */

type FieldBase = {
    label: string;
    error?: string;
    hint?: ReactNode;
    aside?: ReactNode;
    className?: string;
};

export function Field({ label, error, hint, aside, className, inputClassName, id, required, ...input }: FieldBase & InputHTMLAttributes<HTMLInputElement> & { inputClassName?: string }) {
    const generated = useId();
    const inputId = id ?? generated;

    return (
        <div className={cn('field', className)}>
            <FieldLabel htmlFor={inputId} label={label} required={required} aside={aside} />
            <input id={inputId} required={required} aria-invalid={error ? true : undefined}
                aria-describedby={cn(hint ? `${inputId}-hint` : null, error ? `${inputId}-error` : null) || undefined}
                {...input} className={cn('input', input.type === 'file' && 'cursor-pointer', inputClassName)} />
            <FieldFoot id={inputId} hint={hint} error={error} />
        </div>
    );
}

export function TextArea({ label, error, hint, aside, className, id, required, ...input }: FieldBase & TextareaHTMLAttributes<HTMLTextAreaElement>) {
    const generated = useId();
    const inputId = id ?? generated;

    return (
        <div className={cn('field', className)}>
            <FieldLabel htmlFor={inputId} label={label} required={required} aside={aside} />
            <textarea id={inputId} required={required} aria-invalid={error ? true : undefined} className="input" {...input} />
            <FieldFoot id={inputId} hint={hint} error={error} />
        </div>
    );
}

/** Themed dropdown with the same props as a native <select> field. */
export function Select({ label, error, hint, aside, className, required, options, value, onChange, name }: FieldBase & {
    options: Record<string, string> | Array<[string, string]>;
    value?: string | number | readonly string[];
    onChange?: (event: { target: { value: string } }) => void;
    required?: boolean;
    name?: string;
}) {
    return (
        <Dropdown label={label} error={error} hint={hint} aside={aside} className={className} required={required} name={name}
            options={options} value={String(value ?? '')} onChange={(next) => onChange?.({ target: { value: next } })} />
    );
}

function FieldLabel({ htmlFor, label, required, aside }: { htmlFor: string; label: string; required?: boolean; aside?: ReactNode }) {
    return (
        <div className="flex items-baseline justify-between gap-3">
            <label htmlFor={htmlFor} className="field-label">
                {label}
                {required && <span className="text-accent" aria-hidden="true"> *</span>}
            </label>
            {aside}
        </div>
    );
}

function FieldFoot({ id, hint, error }: { id: string; hint?: ReactNode; error?: string }) {
    return (
        <>
            {hint && <p id={`${id}-hint`} className="field-hint">{hint}</p>}
            {error && (
                <p id={`${id}-error`} className="field-error" role="alert">
                    <Icon name="alert" size={15} className="mt-0.5" /> {error}
                </p>
            )}
        </>
    );
}

export function Checkbox({ children, error, ...input }: { children: ReactNode; error?: string } & InputHTMLAttributes<HTMLInputElement>) {
    return (
        <div>
            <label className="flex items-start gap-3 text-sm text-paper-2">
                <input type="checkbox" className="checkbox" {...input} />
                <span>{children}</span>
            </label>
            {error && <p className="field-error mt-2">{error}</p>}
        </div>
    );
}

/* Timers and meters ------------------------------------------------------------ */

/** Counts down to a hold expiry; calls onExpire once when it hits zero. */
export function useCountdown(expiresAt: string | null | undefined, onExpire?: () => void) {
    const compute = () => (expiresAt ? Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000)) : 0);
    const [remaining, setRemaining] = useState(compute);

    useEffect(() => {
        if (!expiresAt) return;
        setRemaining(compute());
        const timer = window.setInterval(() => {
            const next = compute();
            setRemaining(next);
            if (next === 0) {
                window.clearInterval(timer);
                onExpire?.();
            }
        }, 1000);
        return () => window.clearInterval(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [expiresAt]);

    return {
        remaining,
        label: `${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}`,
        urgent: remaining <= 120,
    };
}

const strengthChecks = [
    { label: '8+ characters', test: (value: string) => value.length >= 8 },
    { label: 'Upper & lower case', test: (value: string) => /[a-z]/.test(value) && /[A-Z]/.test(value) },
    { label: 'A number', test: (value: string) => /\d/.test(value) },
    { label: 'A symbol (bonus)', test: (value: string) => /[^A-Za-z0-9]/.test(value) },
];

export function PasswordMeter({ password, showChecks = true }: { password: string; showChecks?: boolean }) {
    const score = strengthChecks.filter((check) => check.test(password)).length;
    const tone = ['bg-signal', 'bg-signal', 'bg-volt', 'bg-mint', 'bg-mint'][score];
    const label = ['Too short', 'Weak', 'Fair', 'Strong', 'Excellent'][score];

    return (
        <div className="space-y-3">
            <div className="flex items-center gap-3">
                <div className="grid flex-1 grid-cols-4 gap-1">
                    {[1, 2, 3, 4].map((bar) => <span key={bar} className={cn('h-1 transition-colors duration-300', score >= bar ? tone : 'bg-line')} />)}
                </div>
                <span className="label w-24 text-right" aria-live="polite">{password ? label : ''}</span>
            </div>
            {showChecks && (
                <ul className="grid grid-cols-2 gap-1.5 text-xs">
                    {strengthChecks.map((check) => {
                        const passed = check.test(password);
                        return (
                            <li key={check.label} className={cn('flex items-center gap-1.5', passed ? 'text-mint' : 'text-mute')}>
                                <span className={cn('h-1.5 w-1.5', passed ? 'bg-mint' : 'bg-dim')} />
                                {check.label}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
