import { Link, useForm, usePage } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { Reveal } from '@/components/motion';
import { Field, PageHeader } from '@/components/ui';
import { useT } from '@/lib/i18n';
import { cn, money, route, useShared } from '@/lib/utils';

type Balance = { code: string; balance: number; expires: string | null; usable: boolean };

export default function GiftCards({ pointValue, pointsPer100 }: { pointValue: number; pointsPer100: number }) {
    const t = useT();
    const { auth } = useShared();
    const giftCard = (usePage().props as { giftCard?: Balance | null }).giftCard ?? null;
    const form = useForm({ code: '' });

    return (
        <>
            <PageHeader label={t('Gift cards & points')} title={t('Movies on the house')} accent={[t('house')]}
                lede={t('Gift cards and loyalty points both come off your total at checkout. Whatever you do not use stays on your account.')} />

            <section className="pt-16">
                <div className="shell grid gap-10 lg:grid-cols-12">
                    <Reveal className="ticket p-7 sm:p-10 lg:col-span-7">
                        <p className="label">{t('Check a balance')}</p>
                        <p className="headline mt-3 text-4xl">{t('Got a gift card?')}</p>
                        <form onSubmit={(event) => { event.preventDefault(); form.post(route('gift-cards.balance'), { preserveScroll: true }); }} className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <Field className="flex-1" label={t('Gift card code')} value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase())}
                                error={form.errors.code} maxLength={24} autoComplete="off" placeholder="BMM-XXXX-XXXX" inputClassName="num uppercase tracking-[.18em]" />
                            <button type="submit" disabled={form.processing || form.data.code.length < 6} className="btn btn-primary">{t('Check balance')}</button>
                        </form>

                        {giftCard && (
                            <div className="mt-8 border-t border-line pt-6" aria-live="polite">
                                <div className="flex flex-wrap items-end justify-between gap-4">
                                    <div>
                                        <p className="num text-sm tracking-[.18em] text-mute">{giftCard.code}</p>
                                        <p className="display mt-2 text-7xl tabular">{money(giftCard.balance)}</p>
                                    </div>
                                    <span className={cn('tag', giftCard.usable ? 'tag-mint' : 'tag-signal')}>{giftCard.usable ? t('Ready to use') : t('Not usable')}</span>
                                </div>
                                {giftCard.expires && <p className="mt-3 text-sm text-mute">{t('Valid until :date', { date: giftCard.expires })}</p>}
                            </div>
                        )}
                    </Reveal>

                    <div className="space-y-4 lg:col-span-5">
                        <div className="panel p-7">
                            <Icon name="star" size={22} className="text-accent" />
                            <p className="headline mt-4 text-3xl">{t('Loyalty points')}</p>
                            <p className="mt-3 text-sm leading-relaxed text-mute">
                                {t('Earn :points points for every PKR 100 you pay. Each point is worth PKR :value off a future booking. Points from a cancelled booking are taken back, and points you spent are returned.', { points: pointsPer100, value: pointValue })}
                            </p>
                            <Link href={auth.user ? route('user.dashboard') : route('user.register')} className="btn btn-ghost btn-sm mt-5">
                                {auth.user ? t('See my points') : t('Create an account')} <Icon name="arrow-right" size={14} className="arrow" />
                            </Link>
                        </div>
                        <div className="panel p-7">
                            <Icon name="ticket" size={22} className="text-accent" />
                            <p className="headline mt-4 text-3xl">{t('Using a card')}</p>
                            <p className="mt-3 text-sm leading-relaxed text-mute">{t('Enter the code in the “Gift card” box at checkout. If the card covers the whole booking, nothing is left to pay.')}</p>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
