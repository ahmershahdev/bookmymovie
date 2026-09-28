import { useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import Tilt from '@/components/Tilt';
import { Alert, Field } from '@/components/ui';
import { money, route, useShared } from '@/lib/utils';
import type { MovieCard } from '@/types';

interface Props {
    share: { label: string; amount: number; status: 'pending' | 'paid' | 'cancelled'; paid_at: string | null; token: string };
    booking: { number: string; film: string; movie: MovieCard; when: string | null; cinema: string; seats: number; host: string; cancelled: boolean };
    progress: { paid: number; total: number };
    cardEnabled: boolean;
}

/** What a friend sees when the host sends them their share of a group booking. */
export default function Split({ share, booking, progress, cardEnabled }: Props) {
    const { errors } = useShared();
    const form = useForm({ name: '' });
    const paid = share.status === 'paid';

    return (
        <section className="pb-24 pt-[calc(var(--header)+3rem)]">
            <div className="shell grid max-w-5xl gap-10 lg:grid-cols-[18rem_1fr] lg:items-start">
                <Tilt max={10}><Poster movie={booking.movie} size="lg" eager /></Tilt>
                <div>
                    <p className="label label-accent">{booking.host} is taking you to the movies</p>
                    <h1 className="display mt-4 text-[clamp(3rem,8vw,6rem)] leading-[.9]">{booking.film}</h1>
                    <p className="lede mt-4">{booking.when} · {booking.cinema} · {booking.seats} seats</p>

                    <div className="mt-8 flex items-center gap-3">
                        <div className="h-1.5 flex-1 bg-line"><motion.div className="h-full bg-mint" initial={{ width: 0 }} animate={{ width: `${(progress.paid / Math.max(1, progress.total)) * 100}%` }} transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }} /></div>
                        <span className="num text-xs text-mute">{progress.paid} of {progress.total} shares paid</span>
                    </div>

                    <div className="ticket mt-8 p-7" style={{ ['--tear' as string]: '50%' }}>
                        <p className="label">Your share</p>
                        <p className="display mt-2 text-6xl tabular">{money(share.amount)}</p>
                        {errors.pay && <div className="mt-4"><Alert tone="error">{errors.pay}</Alert></div>}

                        {booking.cancelled || share.status === 'cancelled' ? (
                            <p className="mt-6 text-sm text-signal">This booking was cancelled, so nothing is owed. If you already paid, the cinema refunds it.</p>
                        ) : paid ? (
                            <p className="mt-6 flex items-center gap-2 text-mint"><Icon name="check" size={18} /> Paid {share.paid_at}. See you at the movies!</p>
                        ) : (
                            <>
                                {cardEnabled ? (
                                    <form onSubmit={(event) => { event.preventDefault(); form.post(route('split.pay', share.token)); }} className="mt-6 space-y-4" noValidate>
                                        <Field label="Your name (so the host knows who paid)" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} maxLength={100} placeholder="e.g. Sara" autoComplete="name" />
                                        <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">Pay {money(share.amount)} by card <Icon name="arrow-right" size={18} className="arrow" /></button>
                                        <p className="text-center text-xs text-mute">Secured by Stripe. Card details never touch BookMyMovie.</p>
                                    </form>
                                ) : null}
                                <div className="mt-6 border-t border-line pt-5 text-sm text-paper-2">
                                    <p className="font-semibold">{cardEnabled ? 'Or pay at the counter' : 'Pay at the counter'}</p>
                                    <p className="mt-1 text-mute">Give booking number <span className="num text-paper">{booking.number}</span> at the box office, at least 20 minutes before the show.</p>
                                </div>
                            </>
                        )}
                    </div>
                    <p className="mt-6 flex items-start gap-2 text-xs text-mute"><Icon name="lock" size={14} className="mt-0.5 shrink-0" /> This link is private to you. It shows only this booking and your share, never anyone's contact details.</p>
                </div>
            </div>
        </section>
    );
}
