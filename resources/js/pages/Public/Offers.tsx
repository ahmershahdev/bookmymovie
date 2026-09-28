import { useState } from 'react';
import { Reveal } from '@/components/motion';
import { EmptyState, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';

type Offer = { code: string; description: string; value: string; live: boolean; starts: string | null; ends: string | null; min_spend: string; max_off: string | null; per_user: number; remaining: number | null };

export default function Offers({ offers }: { offers: Offer[] }) {
    const [copied, setCopied] = useState<string | null>(null);

    const copy = (code: string) => {
        navigator.clipboard?.writeText(code).catch(() => undefined);
        setCopied(code);
        window.setTimeout(() => setCopied(null), 1800);
    };

    return (
        <>
            <PageHeader label="Offers" title="Better seats smaller bill" accent={['smaller']}
                lede="Enter a code at checkout. It is checked when you place the booking, and the discount is shown on your e-ticket." />

            <section className="pt-16">
                <div className="shell">
                    {offers.length === 0 ? (
                        <EmptyState icon="tag" title="No offers right now">Weekday matinées are always discounted. Look for prices in red on the showtimes.</EmptyState>
                    ) : (
                        <div className="grid gap-4 lg:grid-cols-2">
                            {offers.map((offer, index) => (
                                <Reveal key={offer.code} delay={index * 90} className={cn('ticket flex flex-col p-7 sm:p-10', !offer.live && 'opacity-60')}>
                                    <div className="flex items-start justify-between gap-6">
                                        <p className="display text-[clamp(5rem,10vw,8rem)]">{offer.value}<span className="ml-2 text-4xl text-accent">off</span></p>
                                        <span className={cn('tag', offer.live ? 'tag-mint' : 'tag-volt')}>{offer.live ? 'Live' : `Starts ${offer.starts}`}</span>
                                    </div>
                                    <p className="mt-4 max-w-md text-paper-2">{offer.description}</p>
                                    <div className="perforation -mx-10 my-8" />
                                    <div className="mt-auto flex flex-wrap items-center justify-between gap-4">
                                        <button type="button" onClick={() => copy(offer.code)} aria-label={`Copy code ${offer.code}`}
                                            className="num group flex items-center gap-3 border border-dashed border-accent px-5 py-3 text-lg font-semibold tracking-[.25em] text-accent transition hover:bg-volt hover:text-noir">
                                            {offer.code}
                                            <span className="font-sans text-[10px] font-bold uppercase tracking-[.08em] text-mute group-hover:text-noir" aria-live="polite">{copied === offer.code ? 'Copied' : 'Copy'}</span>
                                        </button>
                                        <dl className="grid grid-cols-3 gap-6 text-right text-xs">
                                            <div><dt className="text-mute">Min. spend</dt><dd className="num mt-1">{offer.min_spend}</dd></div>
                                            <div><dt className="text-mute">Max. off</dt><dd className="num mt-1">{offer.max_off ?? '—'}</dd></div>
                                            <div><dt className="text-mute">Ends</dt><dd className="num mt-1">{offer.ends}</dd></div>
                                        </dl>
                                    </div>
                                    <p className="mt-6 text-xs text-mute">{offer.per_user} {offer.per_user === 1 ? 'use' : 'uses'} per account{offer.remaining !== null ? ` · ${offer.remaining} redemptions left` : ''}</p>
                                </Reveal>
                            ))}
                        </div>
                    )}

                    <div className="grid-lines mt-20 md:grid-cols-3">
                        {[
                            ['Weekday matinées', 'Every show before 5 PM, Monday to Friday, is automatically 15% cheaper. No code needed.'],
                            ['Child tickets', 'Guests aged 3–12 pay around 25% less on Classic and Prime rows.'],
                            ['How codes work', 'One code per booking. Minimum spend and expiry apply, and a cancelled booking returns the code to your account.'],
                        ].map(([title, copy]) => (
                            <div key={title} className="p-8"><h2 className="headline text-4xl">{title}</h2><p className="mt-3 text-sm leading-relaxed text-mute">{copy}</p></div>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
