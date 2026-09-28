import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useId, useMemo, useRef, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import { cn } from '@/lib/utils';

export type Option = { value: string; label: string; hint?: string };

/**
 * A themed listbox replacing the native <select>, whose option popup the
 * browser draws in system colours. Keyboard support follows the WAI-ARIA
 * select-only combobox pattern: arrows, Home/End, type-ahead, Enter/Escape.
 */
export default function Dropdown({ label, value, onChange, options, error, hint, placeholder = 'Choose…', className, hideLabel = false, required, aside, name }: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: Option[] | Record<string, string> | Array<[string, string]>;
    error?: string;
    hint?: ReactNode;
    placeholder?: string;
    className?: string;
    hideLabel?: boolean;
    required?: boolean;
    aside?: ReactNode;
    name?: string;
}) {
    const id = useId();
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(0);
    const root = useRef<HTMLDivElement>(null);
    const list = useRef<HTMLUListElement>(null);
    const typed = useRef({ text: '', at: 0 });

    const items: Option[] = useMemo(() => {
        if (Array.isArray(options)) {
            return options.map((option) => (Array.isArray(option) ? { value: option[0], label: option[1] } : option));
        }
        return Object.entries(options).map(([optionValue, optionLabel]) => ({ value: optionValue, label: optionLabel }));
    }, [options]);

    const selected = items.find((item) => item.value === value);

    useEffect(() => {
        if (!open) return;
        setActive(Math.max(0, items.findIndex((item) => item.value === value)));
        const onDown = (event: PointerEvent) => {
            if (!root.current?.contains(event.target as Node)) setOpen(false);
        };
        document.addEventListener('pointerdown', onDown);
        return () => document.removeEventListener('pointerdown', onDown);
    }, [open, items, value]);

    useEffect(() => {
        if (open) list.current?.querySelector(`[data-index="${active}"]`)?.scrollIntoView({ block: 'nearest' });
    }, [active, open]);

    const choose = (index: number) => {
        const item = items[index];
        if (!item) return;
        onChange(item.value);
        setOpen(false);
    };

    const onKeyDown = (event: React.KeyboardEvent) => {
        const last = items.length - 1;
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                if (!open) setOpen(true);
                else setActive((index) => Math.min(last, index + 1));
                break;
            case 'ArrowUp':
                event.preventDefault();
                if (!open) setOpen(true);
                else setActive((index) => Math.max(0, index - 1));
                break;
            case 'Home':
                if (open) { event.preventDefault(); setActive(0); }
                break;
            case 'End':
                if (open) { event.preventDefault(); setActive(last); }
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                if (open) choose(active);
                else setOpen(true);
                break;
            case 'Escape':
                if (open) { event.preventDefault(); event.stopPropagation(); setOpen(false); }
                break;
            case 'Tab':
                setOpen(false);
                break;
            default:
                if (event.key.length === 1 && !event.metaKey && !event.ctrlKey) {
                    const now = Date.now();
                    typed.current.text = (now - typed.current.at > 600 ? '' : typed.current.text) + event.key.toLowerCase();
                    typed.current.at = now;
                    const match = items.findIndex((item) => item.label.toLowerCase().startsWith(typed.current.text));
                    if (match >= 0) {
                        if (open) setActive(match);
                        else onChange(items[match].value);
                    }
                }
        }
    };

    return (
        <div ref={root} className={cn('field relative', className)}>
            <div className={cn('flex items-baseline justify-between gap-3', hideLabel && 'sr-only')}>
                <span id={`${id}-label`} className="field-label">{label}{required && <span className="text-accent" aria-hidden="true"> *</span>}</span>
                {aside}
            </div>
            {name && <input type="hidden" name={name} value={value} />}
            <button type="button" role="combobox" aria-haspopup="listbox" aria-expanded={open} aria-controls={`${id}-list`} aria-labelledby={`${id}-label ${id}-value`}
                aria-invalid={error ? true : undefined} aria-activedescendant={open ? `${id}-option-${active}` : undefined}
                onClick={() => setOpen(!open)} onKeyDown={onKeyDown}
                className={cn('input flex items-center justify-between gap-3 text-left', open && '!border-accent')}>
                <span id={`${id}-value`} className={cn('truncate', !selected || selected.value === '' ? 'text-dim' : '')}>{selected?.label ?? placeholder}</span>
                <Icon name="chevron-down" size={16} className={cn('text-mute transition-transform duration-300', open && 'rotate-180 text-accent')} />
            </button>

            <AnimatePresence>
                {open && (
                    <motion.ul ref={list} id={`${id}-list`} role="listbox" aria-labelledby={`${id}-label`} data-lenis-prevent
                        initial={{ opacity: 0, y: -6, clipPath: 'inset(0 0 100% 0)' }} animate={{ opacity: 1, y: 0, clipPath: 'inset(0 0 0% 0)' }} exit={{ opacity: 0, y: -6, clipPath: 'inset(0 0 100% 0)' }}
                        transition={{ duration: 0.28, ease: [0.16, 1, 0.3, 1] }}
                        className="absolute inset-x-0 top-full z-50 mt-1 max-h-72 overflow-y-auto border border-line-2 bg-ink-2 p-1 shadow-2xl shadow-black/40">
                        {items.map((item, index) => {
                            const isSelected = item.value === value;
                            return (
                                <li key={item.value || `empty-${index}`} id={`${id}-option-${index}`} data-index={index} role="option" aria-selected={isSelected}
                                    onPointerEnter={() => setActive(index)} onClick={() => choose(index)}
                                    className={cn('flex cursor-pointer items-center justify-between gap-3 px-3 py-2.5 text-sm transition-colors',
                                        index === active ? 'bg-volt text-noir' : isSelected ? 'text-paper' : 'text-paper-2')}>
                                    <span className="truncate">{item.label}{item.hint && <span className="ml-2 opacity-60">{item.hint}</span>}</span>
                                    {isSelected && <Icon name="check" size={14} stroke={2} />}
                                </li>
                            );
                        })}
                    </motion.ul>
                )}
            </AnimatePresence>

            {hint && <p className="field-hint">{hint}</p>}
            {error && <p className="field-error" role="alert"><Icon name="alert" size={15} className="mt-0.5" /> {error}</p>}
        </div>
    );
}
