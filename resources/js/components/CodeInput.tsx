import { useId, useRef } from 'react';
import Icon from '@/components/Icon';
import { cn } from '@/lib/utils';

/**
 * Eight separate boxes for an emailed one-time code. One real input sits
 * underneath (so paste, autofill "one-time-code" and screen readers work);
 * the boxes only draw what it holds.
 */
export default function CodeInput({ value, onChange, error, label = 'Verification code', length = 8 }: {
    value: string;
    onChange: (value: string) => void;
    error?: string;
    label?: string;
    length?: number;
}) {
    const id = useId();
    const input = useRef<HTMLInputElement>(null);

    return (
        <div className="field">
            <label htmlFor={id} className="field-label">{label}</label>
            <div className="group relative" onClick={() => input.current?.focus()}>
                <input ref={input} id={id} value={value} maxLength={length} required inputMode="text" autoComplete="one-time-code" autoCapitalize="characters" spellCheck={false}
                    aria-invalid={error ? true : undefined} aria-describedby={`${id}-hint`}
                    onChange={(event) => onChange(event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, length))}
                    className="absolute inset-0 z-10 h-full w-full cursor-text opacity-0" />
                <div className="grid gap-1.5" style={{ gridTemplateColumns: `repeat(${length}, minmax(0, 1fr))` }} aria-hidden="true">
                    {Array.from({ length }, (_, slot) => {
                        const char = value[slot] ?? '';
                        const caret = slot === Math.min(value.length, length - 1);
                        return (
                            <span key={slot} className={cn('num grid aspect-[4/5] place-items-center border text-2xl font-semibold transition-colors duration-200',
                                char ? 'border-accent text-paper' : 'border-line-2 text-dim',
                                caret && 'group-focus-within:border-paper', error && 'border-signal')}>
                                {char || (caret ? <span className="h-6 w-px animate-blink bg-accent" /> : '')}
                            </span>
                        );
                    })}
                </div>
            </div>
            <p id={`${id}-hint`} className="field-hint">Letters and numbers from your email. Pasting the whole code works.</p>
            {error && <p className="field-error" role="alert"><Icon name="alert" size={15} className="mt-0.5" /> {error}</p>}
        </div>
    );
}
