import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import Icon from '@/components/Icon';
import { useT } from '@/lib/i18n';
import { lockScroll } from '@/lib/scroll';
import { cn } from '@/lib/utils';
import type { Trailer } from '@/types';

const ease = [0.16, 1, 0.3, 1] as const;

/**
 * A silent, looping trailer behind a hero. Falls back to the still when the
 * visitor prefers reduced motion or is on a data saver connection, and pauses
 * while the tab is hidden so it never plays unseen.
 */
export function BackdropVideo({ trailer, image, className, onEnded }: { trailer?: Trailer | null; image?: string | null; className?: string; onEnded?: () => void }) {
    const video = useRef<HTMLVideoElement>(null);
    const [motionOk, setMotionOk] = useState(false);

    useEffect(() => {
        const saveData = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection?.saveData;
        setMotionOk(!window.matchMedia('(prefers-reduced-motion: reduce)').matches && !saveData);
    }, []);

    useEffect(() => {
        const node = video.current;
        if (!node) return;
        const sync = () => (document.hidden ? node.pause() : node.play().catch(() => undefined));
        document.addEventListener('visibilitychange', sync);
        return () => document.removeEventListener('visibilitychange', sync);
    }, [trailer?.src, motionOk]);

    const still = trailer?.poster ?? image ?? undefined;

    if (trailer && motionOk) {
        return (
            <video ref={video} key={trailer.src} src={trailer.src} poster={still} autoPlay muted playsInline preload="metadata" loop={!onEnded}
                onEnded={onEnded} aria-hidden="true" tabIndex={-1} className={cn('h-full w-full object-cover', className)} />
        );
    }

    return still ? <img src={still} alt="" aria-hidden="true" decoding="async" className={cn('h-full w-full object-cover', className)} /> : null;
}

/**
 * Full-screen trailer player with sound. When a film has more than one cut
 * they are listed side by side, labelled, so the difference is clear.
 */
export function TrailerModal({ title, trailers, open, onClose, initial = 0 }: { title: string; trailers: Trailer[]; open: boolean; onClose: () => void; initial?: number }) {
    const t = useT();
    const [index, setIndex] = useState(initial);
    const close = useRef<HTMLButtonElement>(null);
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        if (!open) return;
        setIndex(initial);
        lockScroll(true);
        window.setTimeout(() => close.current?.focus(), 60);
        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && onCloseRef.current();
        window.addEventListener('keydown', onKey);
        return () => {
            lockScroll(false);
            window.removeEventListener('keydown', onKey);
        };
    }, [open, initial]);

    const current = trailers[index] ?? trailers[0];

    return (
        <AnimatePresence>
            {open && current && (
                <motion.div role="dialog" aria-modal="true" aria-label={t('Trailer: :title', { title })} data-lenis-prevent
                    initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 0.3 }}
                    className="fixed inset-0 z-[90] flex flex-col bg-ink/95 backdrop-blur-md" onClick={onClose}>
                    <div className="shell flex h-[var(--header)] shrink-0 items-center justify-between gap-4" onClick={(event) => event.stopPropagation()}>
                        <p className="min-w-0">
                            <span className="label label-accent block">{current.label}</span>
                            <span className="headline block truncate text-2xl">{title}</span>
                        </p>
                        <button ref={close} type="button" onClick={onClose} className="btn btn-primary btn-sm shrink-0">{t('Close')} <Icon name="close" size={16} /></button>
                    </div>

                    <div className="shell flex min-h-0 flex-1 flex-col items-center justify-center gap-5 pb-8" onClick={(event) => event.stopPropagation()}>
                        <motion.video key={current.src} src={current.src} poster={current.poster ?? undefined} controls autoPlay playsInline preload="auto"
                            initial={{ opacity: 0, scale: 0.97 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.5, ease }}
                            className="aspect-video max-h-[72vh] w-full max-w-6xl bg-noir object-contain" />

                        {trailers.length > 1 && (
                            <div className="grid w-full max-w-6xl gap-2 sm:grid-cols-2" role="tablist" aria-label={t('Choose a cut')}>
                                {trailers.map((trailer, position) => (
                                    <button key={trailer.src} type="button" role="tab" aria-selected={position === index} onClick={() => setIndex(position)}
                                        className={cn('group grid grid-cols-[7rem_1fr] items-center gap-4 border p-2 text-left transition',
                                            position === index ? 'border-accent bg-ink-2' : 'border-line-2 hover:border-paper')}>
                                        <span className="relative block aspect-video overflow-hidden bg-ink-3">
                                            {trailer.poster && <img src={trailer.poster} alt="" className="h-full w-full object-cover" loading="lazy" />}
                                            <span className="absolute inset-0 grid place-items-center bg-ink/30"><Icon name={position === index ? 'volume' : 'play'} size={18} /></span>
                                        </span>
                                        <span>
                                            <span className="num block text-[11px] text-mute">{String(position + 1).padStart(2, '0')} / {String(trailers.length).padStart(2, '0')}</span>
                                            <span className={cn('headline block text-xl', position === index && 'text-accent')}>{trailer.label}</span>
                                        </span>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
