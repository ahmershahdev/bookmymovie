import { useEffect, useState } from 'react';
import Icon from '@/components/Icon';
import { disablePush, enablePush, pushState, type PushState } from '@/lib/push';
import { cn, useShared } from '@/lib/utils';

/**
 * "Notify me" switch for browser push. Permission is only ever requested
 * from this click, never on page load.
 */
export default function PushToggle({ className, label = 'Browser alerts' }: { className?: string; label?: string }) {
    const { site, auth } = useShared();
    const [state, setState] = useState<PushState | 'loading'>('loading');
    const [busy, setBusy] = useState(false);

    useEffect(() => { pushState().then(setState).catch(() => setState('unsupported')); }, []);

    if (!auth.user || !site.push_key || state === 'unsupported' || state === 'loading') return null;

    const toggle = async () => {
        setBusy(true);
        try {
            setState(state === 'on' ? await disablePush() : await enablePush(site.push_key as string));
        } catch {
            setState('off');
        } finally {
            setBusy(false);
        }
    };

    if (state === 'denied') {
        return <p className={cn('flex items-center gap-2 text-xs text-mute', className)}><Icon name="alert" size={14} /> Notifications are blocked in this browser's site settings.</p>;
    }

    return (
        <button type="button" role="switch" aria-checked={state === 'on'} disabled={busy} onClick={toggle}
            className={cn('flex items-center gap-2.5 text-xs font-semibold uppercase tracking-[.08em] transition disabled:opacity-50 [font-stretch:115%]', className)}>
            <span className={cn('relative h-5 w-9 border transition-colors', state === 'on' ? 'border-volt bg-volt' : 'border-line-2 bg-ink-3')}>
                <span className={cn('absolute top-0.5 h-3.5 w-3.5 transition-all', state === 'on' ? 'left-[1.1rem] bg-noir' : 'left-0.5 bg-mute')} />
            </span>
            <Icon name="megaphone" size={14} /> {label}: {state === 'on' ? 'on' : 'off'}
        </button>
    );
}
