import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import Icon from '@/components/Icon';
import { responsiveSrcSet } from '@/components/Poster';
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
            <video ref={video} key={trailer.src} poster={still} autoPlay muted playsInline preload="metadata" loop={!onEnded}
                onEnded={onEnded} aria-hidden="true" tabIndex={-1} className={cn('h-full w-full object-cover', className)}>
                <Sources trailer={trailer} />
            </video>
        );
    }

    return still ? <img src={still} srcSet={responsiveSrcSet(still)} sizes="100vw" alt="" aria-hidden="true" decoding="async" fetchPriority="high" className={cn('h-full w-full object-cover', className)} /> : null;
}

/** WebM (VP9, about a third smaller) first; MP4 for Safari and older devices. */
function Sources({ trailer }: { trailer: Trailer }) {
    return (
        <>
            {trailer.webm && <source src={trailer.webm} type="video/webm" />}
            <source src={trailer.src} type="video/mp4" />
        </>
    );
}

/** Renders at the end of <body>, outside any stacking context of the page. */
function Portal({ children }: { children: ReactNode }) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);
    return mounted ? createPortal(children, document.body) : null;
}

const clock = (seconds: number) => {
    const safe = Number.isFinite(seconds) ? Math.max(0, seconds) : 0;
    return `${Math.floor(safe / 60)}:${String(Math.floor(safe % 60)).padStart(2, '0')}`;
};

/**
 * Custom trailer player. Controls fade after two seconds of stillness and
 * come back on any movement. Keys: Space/K play, M mute, F full screen,
 * ←/→ seek five seconds, Esc closes.
 */
function Player({ trailer, title, onClose, onEnded }: { trailer: Trailer; title: string; onClose: () => void; onEnded: () => void }) {
    const t = useT();
    const frame = useRef<HTMLDivElement>(null);
    const video = useRef<HTMLVideoElement>(null);
    const idle = useRef(0);
    const [state, setState] = useState({ playing: false, muted: false, time: 0, duration: 0, buffered: 0, full: false, ended: false });
    const [awake, setAwake] = useState(true);
    const [scrubbing, setScrubbing] = useState(false);

    const wake = () => {
        setAwake(true);
        window.clearTimeout(idle.current);
        idle.current = window.setTimeout(() => setAwake(false), 2200);
    };

    const toggle = () => {
        const node = video.current;
        if (!node) return;
        if (node.paused) node.play().catch(() => { node.muted = true; node.play().catch(() => undefined); });
        else node.pause();
    };
    const seek = (delta: number) => { if (video.current) video.current.currentTime = Math.min(state.duration, Math.max(0, video.current.currentTime + delta)); };
    const mute = () => { if (video.current) video.current.muted = !video.current.muted; };
    const fullscreen = () => (document.fullscreenElement ? document.exitFullscreen() : frame.current?.requestFullscreen?.())?.catch?.(() => undefined);

    useEffect(() => {
        const node = video.current;
        if (!node) return;
        const sync = () => setState((current) => ({
            ...current,
            playing: !node.paused,
            muted: node.muted,
            time: node.currentTime,
            duration: node.duration || current.duration,
            buffered: node.buffered.length ? node.buffered.end(node.buffered.length - 1) : 0,
            ended: node.ended,
        }));
        const events = ['play', 'pause', 'timeupdate', 'volumechange', 'loadedmetadata', 'progress', 'ended'];
        events.forEach((name) => node.addEventListener(name, sync));
        node.play().catch(() => { node.muted = true; node.play().catch(() => undefined); });
        wake();
        return () => events.forEach((name) => node.removeEventListener(name, sync));
    }, [trailer.src]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        const onFull = () => setState((current) => ({ ...current, full: Boolean(document.fullscreenElement) }));
        const onKey = (event: KeyboardEvent) => {
            if ((event.target as HTMLElement).tagName === 'INPUT') return;
            const key = event.key.toLowerCase();
            if (key === ' ' || key === 'k') { event.preventDefault(); toggle(); }
            else if (key === 'm') mute();
            else if (key === 'f') fullscreen();
            else if (key === 'arrowright') seek(5);
            else if (key === 'arrowleft') seek(-5);
            else if (key === 'escape' && !document.fullscreenElement) onClose();
            else return;
            wake();
        };
        document.addEventListener('fullscreenchange', onFull);
        window.addEventListener('keydown', onKey);
        return () => { document.removeEventListener('fullscreenchange', onFull); window.removeEventListener('keydown', onKey); };
    }); // re-bound each render so the handlers see fresh state

    const scrubTo = (clientX: number, bar: HTMLElement) => {
        const rect = bar.getBoundingClientRect();
        const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
        if (video.current && state.duration) video.current.currentTime = ratio * state.duration;
    };
    const pct = state.duration ? (state.time / state.duration) * 100 : 0;
    const shown = awake || !state.playing || scrubbing;

    return (
        <div ref={frame} onPointerMove={wake} className={cn('group/player relative aspect-video w-full max-w-6xl overflow-hidden bg-noir', !shown && 'cursor-none')}>
            <video ref={video} key={trailer.src} poster={trailer.poster ?? undefined} playsInline preload="auto" onClick={toggle} onEnded={onEnded}
                className="h-full w-full object-contain" aria-label={`${trailer.label}: ${title}`}>
                <Sources trailer={trailer} />
            </video>

            {/* Big centre button when paused or finished. */}
            <AnimatePresence>
                {!state.playing && (
                    <motion.button type="button" onClick={() => { if (state.ended && video.current) video.current.currentTime = 0; toggle(); }}
                        initial={{ opacity: 0, scale: 0.8 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 1.2 }} transition={{ duration: 0.3, ease }}
                        className="absolute left-1/2 top-1/2 grid h-20 w-20 -translate-x-1/2 -translate-y-1/2 place-items-center bg-volt text-noir shadow-2xl"
                        aria-label={state.ended ? t('Replay') : t('Play')}>
                        <Icon name={state.ended ? 'refresh' : 'play'} size={30} />
                    </motion.button>
                )}
            </AnimatePresence>

            {/* Top bar: what is playing, and the way out. */}
            <div className={cn('absolute inset-x-0 top-0 flex items-start justify-between gap-4 bg-gradient-to-b from-noir/80 to-transparent p-4 transition-opacity duration-300 sm:p-5', shown ? 'opacity-100' : 'opacity-0')}>
                <p className="min-w-0 text-paper">
                    <span className="label label-accent block">{trailer.label}</span>
                    <span className="headline block truncate text-xl sm:text-2xl">{title}</span>
                </p>
                <button type="button" onClick={onClose} className="group/x grid h-11 w-11 shrink-0 place-items-center border border-paper/30 bg-noir/60 text-paper backdrop-blur transition hover:border-volt hover:bg-volt hover:text-noir" aria-label={t('Close trailer')}>
                    <Icon name="close" size={18} className="transition-transform duration-500 group-hover/x:rotate-90" />
                </button>
            </div>

            {/* Bottom controls. */}
            <div className={cn('absolute inset-x-0 bottom-0 bg-gradient-to-t from-noir/90 via-noir/50 to-transparent px-4 pb-3 pt-12 transition-opacity duration-300 sm:px-5', shown ? 'opacity-100' : 'pointer-events-none opacity-0')}>
                <div role="slider" tabIndex={0} aria-label={t('Seek')} aria-valuemin={0} aria-valuemax={Math.round(state.duration)} aria-valuenow={Math.round(state.time)} aria-valuetext={`${clock(state.time)} of ${clock(state.duration)}`}
                    className="group/bar relative h-5 cursor-pointer touch-none"
                    onPointerDown={(event) => { setScrubbing(true); event.currentTarget.setPointerCapture(event.pointerId); scrubTo(event.clientX, event.currentTarget); }}
                    onPointerMove={(event) => scrubbing && scrubTo(event.clientX, event.currentTarget)}
                    onPointerUp={() => setScrubbing(false)}
                    onKeyDown={(event) => { if (event.key === 'ArrowRight') seek(5); if (event.key === 'ArrowLeft') seek(-5); }}>
                    <span className="absolute inset-x-0 top-1/2 h-[3px] -translate-y-1/2 bg-paper/20 transition-[height] group-hover/bar:h-[5px]" />
                    <span className="absolute left-0 top-1/2 h-[3px] -translate-y-1/2 bg-paper/35 transition-[height] group-hover/bar:h-[5px]" style={{ width: `${state.duration ? (state.buffered / state.duration) * 100 : 0}%` }} />
                    <span className="absolute left-0 top-1/2 h-[3px] -translate-y-1/2 bg-volt transition-[height] group-hover/bar:h-[5px]" style={{ width: `${pct}%` }} />
                    <span className="absolute top-1/2 h-3.5 w-3.5 -translate-x-1/2 -translate-y-1/2 scale-0 bg-volt transition-transform group-hover/bar:scale-100" style={{ left: `${pct}%` }} />
                </div>
                <div className="mt-1 flex items-center gap-1 text-paper">
                    <button type="button" onClick={toggle} className="grid h-10 w-10 place-items-center transition hover:text-accent" aria-label={state.playing ? t('Pause') : t('Play')}>
                        <Icon name={state.playing ? 'pause' : 'play'} size={20} />
                    </button>
                    <button type="button" onClick={() => seek(-5)} className="hidden h-10 w-10 place-items-center transition hover:text-accent sm:grid" aria-label={t('Back 5 seconds')}><Icon name="skip-back" size={18} /></button>
                    <button type="button" onClick={() => seek(5)} className="hidden h-10 w-10 place-items-center transition hover:text-accent sm:grid" aria-label={t('Forward 5 seconds')}><Icon name="skip-forward" size={18} /></button>
                    <button type="button" onClick={mute} className="grid h-10 w-10 place-items-center transition hover:text-accent" aria-label={state.muted ? t('Unmute') : t('Mute')}>
                        <Icon name={state.muted ? 'mute' : 'volume'} size={19} />
                    </button>
                    <span className="num ms-2 text-xs text-paper/80">{clock(state.time)} <span className="text-paper/40">/ {clock(state.duration)}</span></span>
                    <button type="button" onClick={onClose} className="ms-auto flex h-9 items-center gap-2 border border-paper/30 px-3 text-[10px] font-bold uppercase tracking-[.1em] transition hover:border-volt hover:bg-volt hover:text-noir [font-stretch:115%]">
                        {t('Exit trailer')} <Icon name="logout" size={14} />
                    </button>
                    <button type="button" onClick={fullscreen} className="grid h-10 w-10 place-items-center transition hover:text-accent" aria-label={state.full ? t('Exit full screen') : t('Full screen')}>
                        <Icon name={state.full ? 'shrink' : 'expand'} size={18} />
                    </button>
                </div>
            </div>
        </div>
    );
}

/**
 * Full-screen trailer overlay. When a film has more than one cut they are
 * listed underneath, labelled, so the difference is clear; the next cut
 * starts when one ends.
 */
export function TrailerModal({ title, trailers, open, onClose, initial = 0 }: { title: string; trailers: Trailer[]; open: boolean; onClose: () => void; initial?: number }) {
    const t = useT();
    const [index, setIndex] = useState(initial);

    useEffect(() => {
        if (!open) return;
        setIndex(initial);
        lockScroll(true);
        return () => lockScroll(false);
    }, [open, initial]);

    const current = trailers[index] ?? trailers[0];

    return (
        <Portal>
        <AnimatePresence>
            {open && current && (
                <motion.div role="dialog" aria-modal="true" aria-label={t('Trailer: :title', { title })} data-lenis-prevent
                    initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 0.3 }}
                    className="fixed inset-0 z-[120] overflow-y-auto overscroll-contain bg-ink/95 backdrop-blur-md" onClick={onClose}>
                    {/* min-h-full + my-auto centres when it fits and scrolls from the top when it does not. */}
                    <div className="flex min-h-full flex-col items-center gap-5 px-4 py-6 sm:px-8 sm:py-10">
                    <motion.div className="my-auto w-full max-w-6xl [@media(min-aspect-ratio:16/9)]:max-w-[calc((100svh-9rem)*16/9)]" initial={{ scale: 0.96, y: 20 }} animate={{ scale: 1, y: 0 }} exit={{ scale: 0.97, opacity: 0 }} transition={{ duration: 0.5, ease }} onClick={(event) => event.stopPropagation()}>
                        <Player trailer={current} title={title} onClose={onClose} onEnded={() => index < trailers.length - 1 && setIndex(index + 1)} />
                    </motion.div>

                    {trailers.length > 1 && (
                        <div className="grid w-full max-w-6xl gap-2 sm:grid-cols-2" role="tablist" aria-label={t('Choose a cut')} onClick={(event) => event.stopPropagation()}>
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
                    <p className="num hidden text-[11px] text-mute sm:block">Space {t('play/pause')} · M {t('mute')} · F {t('full screen')} · ← → {t('seek')} · Esc {t('close')}</p>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
        </Portal>
    );
}
