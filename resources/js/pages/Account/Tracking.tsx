import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import { Alert, Breadcrumbs } from '@/components/ui';
import { cn, pad, route } from '@/lib/utils';
import type { BookingSummary } from '@/types';

interface Props {
    booking: BookingSummary & { when: string; cancelled_at: string | null; cancelled_by: string | null; cancellation_reason: string | null };
    milestones: { title: string; copy: string; done: boolean; at: string | null }[];
    events: { id: number; at: string | null; actor: string; event: string; cancelled: boolean; note: string | null }[];
}

export default function Tracking({ booking, milestones, events }: Props) {
    return (
        <section className="pb-24 pt-[calc(var(--header)+2.5rem)]">
            <div className="shell">
                <Breadcrumbs className="mb-10" />
                <p className="label label-accent num">{booking.number}</p>
                <SplitHeading as="h1" text={booking.title} className="mt-4 text-[clamp(3.5rem,10vw,8.5rem)]" />
                <p className="lede mt-4">{booking.when} · {booking.theater}</p>

                {booking.cancelled ? (
                    <div className="mt-10 max-w-2xl"><Alert tone="error">Cancelled {booking.cancelled_at} by {booking.cancelled_by}. {booking.cancellation_reason}</Alert></div>
                ) : (
                    <ol className="mt-14 grid gap-8 md:grid-cols-4" aria-label="Progress">
                        {milestones.map((milestone, index) => (
                            <li key={milestone.title}>
                                <div className="flex items-center gap-3">
                                    <span className={cn('grid h-11 w-11 shrink-0 place-items-center border', milestone.done ? 'border-accent bg-volt text-noir' : 'border-line-2 text-dim')}>
                                        {milestone.done ? <Icon name="check" size={18} stroke={2} /> : <span className="num text-xs">{pad(index + 1)}</span>}
                                    </span>
                                    {index < milestones.length - 1 && <span className={cn('hidden h-px flex-1 md:block', milestone.done ? 'bg-volt' : 'bg-line')} />}
                                </div>
                                <p className={cn('headline mt-4 text-3xl', !milestone.done && 'text-mute')}>{milestone.title}</p>
                                <p className="mt-1 text-sm text-mute">{milestone.copy}</p>
                                {milestone.at && <p className="num mt-2 text-[11px] text-dim">{milestone.at}</p>}
                            </li>
                        ))}
                    </ol>
                )}

                <section className="mt-20 max-w-3xl" aria-labelledby="history">
                    <h2 id="history" className="headline text-4xl">History</h2>
                    <ol className="mt-8 border-l border-line-2">
                        {events.length ? events.map((event) => (
                            <li key={event.id} className="relative pb-8 pl-8 last:pb-0">
                                <span className={cn('absolute -left-[5px] top-1.5 h-2.5 w-2.5', event.cancelled ? 'bg-signal' : 'bg-volt')} />
                                <p className="label">{event.at} · {event.actor}</p>
                                <p className="mt-1">{event.event}</p>
                                {event.note && <p className="mt-1 text-sm text-mute">{event.note}</p>}
                            </li>
                        )) : <li className="pl-8 text-sm text-mute">No updates yet.</li>}
                    </ol>
                </section>

                <div className="mt-16 flex flex-wrap gap-2">
                    <Link href={route('user.booking.show', booking.number)} className="btn btn-light">View e-ticket</Link>
                    <Link href={route('user.bookings')} className="btn btn-ghost">All bookings</Link>
                </div>
            </div>
        </section>
    );
}
