import { Link, useForm } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { Wordmark } from '@/components/shell/Navbar';
import ProgressBar from '@/components/shell/ProgressBar';
import Toaster from '@/components/shell/Toaster';
import { Alert } from '@/components/ui';
import { Meta } from '@/layouts/SiteLayout';
import { cn, money, route, useShared } from '@/lib/utils';

interface Props {
    number: string;
    signature: string;
    verdict: string;
    tone: 'mint' | 'volt' | 'signal';
    detail: string;
    canAdmit: boolean;
    booking: {
        film: string;
        customer: string | null;
        cinema: string;
        starts: string | null;
        seats: string[];
        adults: number;
        kids: number;
        total: number;
        paid: boolean;
        addons: string[];
    } | null;
}

/** What a box-office phone shows after scanning an e-ticket's QR code. */
export default function TicketCheck({ number, signature, verdict, tone, detail, canAdmit, booking }: Props) {
    const { errors } = useShared();
    const form = useForm({});
    const banner = { mint: 'bg-mint text-noir', volt: 'bg-volt text-noir', signal: 'bg-signal text-noir' }[tone];

    return (
        <>
            <Meta />
            <ProgressBar />
            <header className="border-b border-line">
                <div className="shell flex h-[var(--header)] items-center justify-between">
                    <Link href={route('home')} aria-label="BookMyMovie home"><Wordmark /></Link>
                    <Link href={route('admin.activity')} className="btn btn-ghost btn-sm">Activity <Icon name="arrow-right" size={14} /></Link>
                </div>
            </header>

            <main id="main" className="shell max-w-2xl space-y-6 py-8">
                <section className={cn('p-6 sm:p-8', banner)} aria-live="polite">
                    <p className="num text-xs opacity-70">{number}</p>
                    <h1 className="display mt-2 text-[clamp(3rem,12vw,5.5rem)]">{verdict}</h1>
                    <p className="mt-2 text-sm font-medium">{detail}</p>
                </section>

                {errors.admit && <Alert tone="error">{errors.admit}</Alert>}

                {booking && (
                    <dl className="grid-lines grid-fill-odd sm:grid-cols-2">
                        {[
                            ['Film', booking.film],
                            ['Guest', booking.customer ?? '—'],
                            ['Cinema', booking.cinema],
                            ['Starts', booking.starts ?? '—'],
                            ['Seats', booking.seats.join(', ')],
                            ['Tickets', `${booking.adults} adult${booking.adults === 1 ? '' : 's'}${booking.kids ? ` · ${booking.kids} child${booking.kids === 1 ? '' : 'ren'}` : ''}`],
                            ['Total', `${money(booking.total)} · ${booking.paid ? 'paid' : 'due now'}`],
                            ['Food and drink', booking.addons.length ? booking.addons.join(', ') : 'None'],
                        ].map(([label, value]) => (
                            <div key={label} className="p-5">
                                <dt className="label">{label}</dt>
                                <dd className="mt-2 text-lg font-semibold">{value}</dd>
                            </div>
                        ))}
                    </dl>
                )}

                {canAdmit && (
                    <button type="button" disabled={form.processing} aria-busy={form.processing} onClick={() => form.post(route('admin.tickets.admit', { number, signature }), { preserveScroll: true })}
                        className="btn btn-primary btn-lg w-full">
                        <Icon name="check" size={18} /> {form.processing ? 'Admitting…' : booking?.paid ? 'Admit guests' : `Take ${money(booking?.total ?? 0)} and admit`}
                    </button>
                )}
            </main>
            <Toaster />
        </>
    );
}

TicketCheck.layout = null;
