import { Link } from '@inertiajs/react';
import axios from 'axios';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import Icon from '@/components/Icon';
import { cn, money, route, useShared } from '@/lib/utils';

type Context = {
    top: { title: string; slug: string; rating: number; reviews: number } | null;
    cheapest: { title: string; slug: string; time: string; cinema: string; price: number } | null;
    formats: { format: string; cinemas: string[] }[];
    nowShowing: number;
    offers: number;
    user: null | {
        first_name: string;
        points: number;
        bookings: number;
        next: null | { film: string; number: string; when: string; in: string; cinema: string; paid: boolean };
        watchlist: { title: string; slug: string; when: string }[];
        pick: null | { title: string; slug: string; rating: number; because: string | null };
    };
};

type Site = ReturnType<typeof useShared>['site'];

/**
 * Answers are plain strings with two bits of markup, so they can be typed
 * out a character at a time: **bold** and a new line per list item.
 */
type Answer = { text: string; action?: { label: string; href: string } };
type Question = { id: string; label: string; answer: (ctx: Context, site: Site) => Answer };
type Message = { id: number; from: 'bot' | 'you'; text: string; action?: Answer['action']; at: string };

const NAME = 'Usher';
const ease = [0.16, 1, 0.3, 1] as const;
const clock = () => new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

/**
 * The questions. Each answer is written from live data, never a canned price.
 * Signed-in customers get their own set first: a pick based on the genres
 * they book, their watchlist, their next show and their points.
 */
function useQuestions(signedIn: boolean): Question[] {
    return useMemo((): Question[] => [
        ...(signedIn ? [
            {
                id: 'pick', label: 'Pick a film for me',
                answer: (ctx: Context): Answer => ctx.user?.pick
                    ? { text: `Try **${ctx.user.pick.title}**${ctx.user.pick.rating ? `, rated **${ctx.user.pick.rating}★**` : ''}. ${ctx.user.pick.because ? `You book a lot of ${ctx.user.pick.because.toLowerCase()}, and you have not seen this one yet.` : 'It is the best rated film you have not seen yet.'}`, action: { label: `Open ${ctx.user.pick.title}`, href: route('movies.show', ctx.user.pick.slug) } }
                    : { text: 'You have seen everything we are showing. New titles arrive every week.', action: { label: 'Coming soon', href: route('movies.status', 'coming-soon') } },
            },
            {
                id: 'watchlist', label: 'Is my watchlist on?',
                answer: (ctx: Context): Answer => ctx.user?.watchlist.length
                    ? { text: `Seats are on sale for:\n${ctx.user.watchlist.map((film) => `**${film.title}**, next show ${film.when}`).join('\n')}`, action: { label: `Book ${ctx.user.watchlist[0].title}`, href: route('movies.show', ctx.user.watchlist[0].slug) + '#showtimes' } }
                    : { text: 'Nothing on your watchlist has a show on sale right now. Add films with the heart button and they show up here once seats open.', action: { label: 'My watchlist', href: route('user.wishlist') } },
            },
        ] : []),
        {
            id: 'good', label: "What's good this week?",
            answer: (ctx) => ctx.top
                ? { text: `The crowd favourite right now is **${ctx.top.title}**, rated **${ctx.top.rating}★** across ${ctx.top.reviews} reviews, most from verified bookings. ${ctx.nowShowing} films are showing in total.`, action: { label: `Open ${ctx.top.title}`, href: route('movies.show', ctx.top.slug) } }
                : { text: 'Nothing is showing just now. New titles are on the way.', action: { label: 'Coming soon', href: route('movies.status', 'coming-soon') } },
        },
        {
            id: 'cheap', label: 'Cheapest seat tonight?',
            answer: (ctx) => ctx.cheapest
                ? { text: `Tonight's lowest price is **${money(ctx.cheapest.price)}** for **${ctx.cheapest.title}** at ${ctx.cheapest.cinema}, ${ctx.cheapest.time}. No booking fee on top.`, action: { label: 'Pick seats', href: route('movies.show', ctx.cheapest.slug) + '#showtimes' } }
                : { text: "Tonight's shows have all started. Tomorrow's matinees are usually the best value.", action: { label: 'See showtimes', href: route('movies.index') } },
        },
        signedIn
            ? {
                id: 'next', label: 'When is my next show?',
                answer: (ctx) => ctx.user?.next
                    ? { text: `**${ctx.user.next.film}** at ${ctx.user.next.cinema}, ${ctx.user.next.when} (${ctx.user.next.in}). ${ctx.user.next.paid ? 'It is paid, just show the QR code at the door.' : 'Pay at the counter before the show.'}`, action: { label: 'Open e-ticket', href: route('user.booking.show', ctx.user.next.number) } }
                    : { text: 'You have no upcoming shows. Shall we find you one?', action: { label: 'Browse films', href: route('movies.index') } },
            }
            : {
                id: 'how', label: 'How does booking work?',
                answer: () => ({ text: 'Pick a film and a showtime, tap your seats on the live map (they are held for 10 minutes), add snacks if you like, then confirm. Your QR e-ticket arrives straight away. No card is needed to book.', action: { label: 'Create a free account', href: route('user.register') } }),
            },
        signedIn
            ? {
                id: 'points', label: 'How many points do I have?',
                answer: (ctx) => ({ text: `You have **${(ctx.user?.points ?? 0).toLocaleString('en-PK')} points**, worth ${money(ctx.user?.points ?? 0)} off your next booking. You earn 5 points for every PKR 100 you pay.`, action: { label: 'Points history', href: route('user.dashboard') } }),
            }
            : {
                id: 'points', label: 'What are loyalty points?',
                answer: () => ({ text: 'Members earn **5 points for every PKR 100** they pay, and each point is PKR 1 off a later booking. Tick "use my points" at checkout.', action: { label: 'Gift cards & points', href: route('gift-cards') } }),
            },
        {
            id: 'formats', label: 'Where can I watch in IMAX or Dolby?',
            answer: (ctx) => ({ text: ctx.formats.length ? ctx.formats.map((row) => `**${row.format}:** ${row.cinemas.join(', ')}`).join('\n') : 'Premium screens are being set up. Check back soon.', action: { label: 'All cinemas', href: route('cinemas.index') } }),
        },
        {
            id: 'cancel', label: 'Can I cancel a booking?',
            answer: () => ({ text: 'Yes. Unpaid bookings can be cancelled **free until 2 hours** before the show from My bookings. Seats go back on sale and any points or gift card balance you used come straight back.', action: { label: 'Refund policy', href: route('refund') } }),
        },
        {
            id: 'offline', label: 'Will my ticket work without signal?',
            answer: () => ({ text: 'Yes. Open the e-ticket once while online and it is saved on your phone, QR code included. You can also add it to Apple or Google Wallet.', action: { label: 'How e-tickets work', href: route('eticket.info') } }),
        },
        {
            id: 'food', label: 'Can I pre-order snacks?',
            answer: () => ({ text: 'At checkout you can add **popcorn, nachos, drinks and combos**. They are ready at the snack counter when you arrive, so you skip the queue.' }),
        },
        {
            id: 'offers', label: 'Any offers running?',
            answer: (ctx) => ({ text: ctx.offers ? `**${ctx.offers} codes** are live right now. Enter one at checkout; the discount shows before you confirm.` : 'No codes are live today, but gift cards and loyalty points always work.', action: { label: 'See offers', href: route('offers') } }),
        },
        {
            id: 'pay', label: 'How can I pay?',
            answer: () => ({ text: 'Pay online with **JazzCash or Easypaisa** where the cinema supports it, or reserve now and **pay at the box office** before the show. Gift cards and points can cover part or all of it.' }),
        },
        {
            id: 'human', label: 'I need a human',
            answer: (_ctx, site) => ({ text: `Of course. Email **${site.support_email}** or call **${site.support_phone}**. We reply within one working day.`, action: { label: 'Contact form', href: route('contact') } }),
        },
    ], [signedIn]);
}

/** Renders **bold** and line breaks from an answer string (never HTML). */
function Rich({ text }: { text: string }) {
    return (
        <>
            {text.split('\n').map((line, row) => (
                <Fragment key={row}>
                    {row > 0 && <br />}
                    {line.split(/(\*\*[^*]*\*{0,2})/g).map((part, index) => part.startsWith('**')
                        ? <strong key={index} className="font-semibold text-paper">{part.replace(/\*/g, '')}</strong>
                        : <Fragment key={index}>{part}</Fragment>)}
                </Fragment>
            ))}
        </>
    );
}

/** Types a reply out, word by word, then reports that it is done. */
function Typewriter({ text, onDone, onTick }: { text: string; onDone: () => void; onTick: () => void }) {
    const reduce = useReducedMotion();
    const [shown, setShown] = useState(reduce ? text.length : 0);

    useEffect(() => {
        if (reduce) { onDone(); return; }
        let position = 0;
        const timer = window.setInterval(() => {
            // Jump a word at a time: reads naturally and finishes long answers quickly.
            const next = text.indexOf(' ', position + 1);
            position = next === -1 ? text.length : next;
            setShown(position);
            onTick();
            if (position >= text.length) { window.clearInterval(timer); onDone(); }
        }, 38);
        return () => window.clearInterval(timer);
    }, [text]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <>
            <Rich text={text.slice(0, shown)} />
            {shown < text.length && <span className="ms-0.5 inline-block h-[1em] w-[2px] translate-y-[2px] animate-pulse bg-accent" aria-hidden="true" />}
        </>
    );
}

/** The avatar: a ticket-stub mark whose bars bounce while Usher is "thinking". */
function Mark({ busy, size = 40 }: { busy: boolean; size?: number }) {
    return (
        <span className="relative grid shrink-0 place-items-center bg-volt text-noir" style={{ width: size, height: size }} aria-hidden="true">
            <span className="flex h-[45%] items-end gap-[3px]">
                {[0.55, 1, 0.75].map((height, index) => (
                    <motion.span key={index} className="w-[3px] bg-noir" style={{ height: `${height * 100}%` }}
                        animate={busy ? { scaleY: [1, 0.35, 1] } : { scaleY: 1 }} transition={busy ? { duration: 0.6, repeat: Infinity, delay: index * 0.12 } : { duration: 0.2 }} />
                ))}
            </span>
        </span>
    );
}

/**
 * Usher, the site assistant. Visitors tap one of its questions; there is no
 * free-text box, so nothing a visitor writes is ever sent, stored or echoed
 * back. Replies are built in the browser from live listings (and, when signed
 * in, the customer's own bookings) and always end with a link to act on.
 */
export default function Assistant({ className }: { className?: string }) {
    const { auth, site } = useShared();
    const questions = useQuestions(Boolean(auth.user));
    const [open, setOpen] = useState(false);
    const [ctx, setCtx] = useState<Context | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [thinking, setThinking] = useState(false);
    const [typingId, setTypingId] = useState<number | null>(null);
    const [asked, setAsked] = useState<string[]>([]);
    const [teaser, setTeaser] = useState(false);
    const log = useRef<HTMLDivElement>(null);
    const firstChip = useRef<HTMLButtonElement>(null);
    const counter = useRef(0);
    const busy = thinking || typingId !== null;

    // Braces matter: scrollTo() returns a Promise in current Chrome, and an
    // effect must return nothing or a cleanup function.
    const scrollDown = () => { log.current?.scrollTo({ top: log.current.scrollHeight }); };

    // A one-time nudge, a few seconds into the first visit.
    useEffect(() => {
        let seen = true;
        try { seen = localStorage.getItem('bmm-usher-seen') === '1'; } catch { /* storage blocked */ }
        if (seen) return;
        const timer = window.setTimeout(() => setTeaser(true), 9000);
        return () => window.clearTimeout(timer);
    }, []);

    useEffect(() => {
        if (!open) return;
        setTeaser(false);
        try { localStorage.setItem('bmm-usher-seen', '1'); } catch { /* storage blocked */ }
        axios.get(route('assistant.context')).then(({ data }) => setCtx(data)).catch(() => undefined);
        if (messages.length === 0) {
            const name = auth.user?.first_name;
            const id = counter.current++;
            setMessages([{ id, from: 'bot', at: clock(), text: name
                ? `Salaam ${name}! I'm **${NAME}**. I can check your next show, your points and your watchlist, or pick a film for you. Tap a question below.`
                : `Salaam! I'm **${NAME}**, your guide to ${site.name}. Tap a question below about films, prices, seats or booking.` }]);
            setTypingId(id);
        }
        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && setOpen(false);
        window.addEventListener('keydown', onKey);
        window.setTimeout(() => firstChip.current?.focus({ preventScroll: true }), 350);
        return () => window.removeEventListener('keydown', onKey);
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(scrollDown, [messages, thinking]);

    const reply = (answer: Answer) => {
        const id = counter.current++;
        setMessages((current) => [...current, { id, from: 'bot', at: clock(), text: answer.text, action: answer.action }]);
        setThinking(false);
        setTypingId(id);
    };

    const ask = (question: Question) => {
        if (thinking) return;
        setTypingId(null); // a new question finishes the reply being typed
        setAsked((current) => [...current, question.id]);
        setMessages((current) => [...current, { id: counter.current++, from: 'you', at: clock(), text: question.label }]);
        setThinking(true);
        window.setTimeout(() => reply(ctx ? question.answer(ctx, site) : { text: 'One second, I am still checking the listings. Please tap the question again in a moment.' }), 450 + Math.random() * 300);
    };

    const restart = () => {
        setMessages([]);
        setAsked([]);
        setTypingId(null);
        const id = counter.current++;
        setMessages([{ id, from: 'bot', at: clock(), text: 'Fresh start. What can I help you find?' }]);
        setTypingId(id);
    };

    const remaining = questions.filter((question) => !asked.includes(question.id));

    return (
        <div className={cn('relative', className)} data-print-hide>
            <AnimatePresence>
                {open && (
                    <motion.section role="dialog" aria-label={`${NAME}, the ${site.name} assistant`} data-lenis-prevent
                        initial={{ opacity: 0, y: 24, scale: 0.94, filter: 'blur(6px)' }} animate={{ opacity: 1, y: 0, scale: 1, filter: 'blur(0px)' }} exit={{ opacity: 0, y: 16, scale: 0.97, filter: 'blur(4px)' }} transition={{ duration: 0.45, ease }}
                        className="absolute bottom-[calc(100%+0.75rem)] end-0 flex h-[min(38rem,calc(100svh-7rem))] w-[min(25rem,calc(100vw-2rem))] origin-bottom-right flex-col overflow-hidden border border-line-2 bg-ink-2 shadow-2xl shadow-black/60">
                        <span className="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-transparent via-volt to-transparent" aria-hidden="true" />
                        <header className="relative flex items-center justify-between gap-3 border-b border-line bg-[radial-gradient(120%_140%_at_0%_0%,color-mix(in_oklab,var(--color-accent)_12%,transparent),transparent_60%)] p-4">
                            <div className="flex items-center gap-3">
                                <Mark busy={busy} />
                                <div>
                                    <p className="flex items-center gap-2 text-sm font-semibold">{NAME} <span className="tag !px-1.5 !py-0 text-[9px]">Beta</span></p>
                                    <p className="flex items-center gap-1.5 text-xs text-mute" aria-live="polite">
                                        <span className={cn('h-1.5 w-1.5', busy ? 'animate-pulse bg-accent' : 'bg-mint')} />
                                        {thinking ? `${NAME} is typing…` : auth.user ? 'Online · private to your account' : 'Online · answers from live listings'}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-1">
                                <button type="button" onClick={restart} disabled={busy} className="btn btn-ghost btn-icon" aria-label="Start over" title="Start over"><Icon name="refresh" size={15} /></button>
                                <button type="button" onClick={() => setOpen(false)} className="btn btn-ghost btn-icon" aria-label="Close assistant"><Icon name="close" size={16} /></button>
                            </div>
                        </header>

                        <div ref={log} className="flex-1 space-y-4 overflow-y-auto overscroll-contain p-4" aria-live="polite" aria-relevant="additions">
                            {messages.map((message) => (
                                <motion.div key={message.id} initial={{ opacity: 0, y: 10, scale: 0.98 }} animate={{ opacity: 1, y: 0, scale: 1 }} transition={{ duration: 0.35, ease }}
                                    className={cn('flex gap-2.5', message.from === 'you' && 'flex-row-reverse')}>
                                    {message.from === 'bot' && <Mark busy={false} size={26} />}
                                    <div className={cn('max-w-[82%]', message.from === 'you' && 'text-right')}>
                                        <div className={cn('text-left text-sm leading-relaxed', message.from === 'you' ? 'bg-volt px-3.5 py-2.5 text-noir' : 'border border-line bg-ink-3 px-3.5 py-3 text-paper-2')}>
                                            {message.from === 'bot' && typingId === message.id
                                                ? <Typewriter text={message.text} onTick={scrollDown} onDone={() => setTypingId((current) => (current === message.id ? null : current))} />
                                                : message.from === 'bot' ? <Rich text={message.text} /> : message.text}
                                            {message.action && typingId !== message.id && (
                                                <motion.div initial={{ opacity: 0, y: 4 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}>
                                                    <Link href={message.action.href} onClick={() => setOpen(false)} className="group mt-3 flex items-center justify-between gap-2 border-t border-line pt-2.5 text-xs font-semibold uppercase tracking-[.08em] text-accent [font-stretch:115%]">
                                                        {message.action.label} <Icon name="arrow-right" size={14} className="transition-transform group-hover:translate-x-1" />
                                                    </Link>
                                                </motion.div>
                                            )}
                                        </div>
                                        <p className="num mt-1 px-1 text-[10px] text-dim">{message.from === 'bot' ? NAME : 'You'} · {message.at}</p>
                                    </div>
                                </motion.div>
                            ))}
                            <AnimatePresence>
                                {thinking && (
                                    <motion.div initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} className="flex items-center gap-2.5">
                                        <Mark busy size={26} />
                                        <div className="flex w-fit gap-1 border border-line bg-ink-3 px-3.5 py-3.5" role="status" aria-label={`${NAME} is typing`}>
                                            {[0, 1, 2].map((dot) => <motion.span key={dot} className="h-1.5 w-1.5 bg-accent" animate={{ opacity: [0.2, 1, 0.2], y: [0, -4, 0] }} transition={{ duration: 0.8, repeat: Infinity, delay: dot * 0.15 }} />)}
                                        </div>
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>

                        <div className="border-t border-line bg-ink-2">
                            <p className="label px-3 pt-3 text-[10px]" id="usher-questions">{auth.user ? `Just for you, ${auth.user.first_name}` : 'Tap a question'}</p>
                            <ul className="flex max-h-32 flex-wrap gap-1.5 overflow-y-auto overscroll-contain p-3" aria-labelledby="usher-questions">
                                {(remaining.length ? remaining : questions).map((question, index) => (
                                    <li key={question.id}>
                                        <button ref={index === 0 ? firstChip : undefined} type="button" onClick={() => ask(question)} disabled={thinking}
                                            className="min-h-9 border border-line-2 px-3 py-1.5 text-left text-xs text-paper-2 transition hover:border-paper hover:bg-paper hover:text-ink disabled:opacity-40">
                                            {question.label}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                            {!auth.user && (
                                <p className="border-t border-line px-3 py-2 text-[11px] text-mute">
                                    <Link href={route('user.login')} onClick={() => setOpen(false)} className="link text-paper-2">Sign in</Link> for your next show, points and film picks.
                                </p>
                            )}
                        </div>
                    </motion.section>
                )}
            </AnimatePresence>

            <AnimatePresence>
                {teaser && !open && (
                    <motion.button type="button" onClick={() => setOpen(true)} initial={{ opacity: 0, x: 12 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 12 }} transition={{ duration: 0.4, ease }}
                        className="absolute bottom-3 end-[calc(100%+0.75rem)] w-max max-w-[15rem] border border-line-2 bg-ink-2 px-3.5 py-2.5 text-left text-xs text-paper-2 shadow-xl shadow-black/40">
                        <span className="block font-semibold text-paper">Hi, I'm {NAME} 👋</span>
                        Need a seat for tonight? Ask me.
                    </motion.button>
                )}
            </AnimatePresence>

            <motion.button type="button" onClick={() => setOpen(!open)} aria-expanded={open} aria-label={open ? 'Close assistant' : teaser ? `Ask ${NAME}, the assistant: 1 new message` : `Ask ${NAME}, the assistant`}
                whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.92 }}
                className="relative grid h-14 w-14 place-items-center bg-volt text-noir shadow-2xl shadow-black/50">
                <AnimatePresence mode="wait" initial={false}>
                    <motion.span key={open ? 'close' : 'chat'} initial={{ rotate: -90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: 90, opacity: 0 }} transition={{ duration: 0.25 }}>
                        <Icon name={open ? 'close' : 'chat'} size={22} />
                    </motion.span>
                </AnimatePresence>
                {!open && <span className="absolute inset-0 animate-ping bg-volt/40 [animation-duration:2.6s]" aria-hidden="true" />}
                {teaser && !open && <span className="absolute -right-1 -top-1 grid h-4 w-4 place-items-center bg-signal text-[9px] font-bold text-white after:content-['1']" aria-hidden="true" />}
            </motion.button>
        </div>
    );
}
