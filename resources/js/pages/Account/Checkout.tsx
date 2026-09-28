import { Link, useForm } from '@inertiajs/react';
import { CheckoutSteps } from '@/components/account';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Alert, Breadcrumbs, Checkbox, Field, TextArea } from '@/components/ui';
import { submitForm } from '@/lib/form';
import { useLocale, useT } from '@/lib/i18n';
import { cn, money, route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';
import type { CartItem, CartShow } from './Cart';

type Concession = { id: number; name: string; name_ur: string | null; description: string | null; category: 'snack' | 'drink' | 'combo' | string; price: number; icon: string };

interface Props {
    total: number;
    show: CartShow;
    items: CartItem[];
    idempotencyKey: string;
    captcha: CaptchaData;
    customer: { name: string; email: string; phone: string; address: string };
    paymentMethods: { key: string; label: string; detail: string }[];
    concessions: Concession[];
    points: number;
}

export default function Checkout({ total, show, items, idempotencyKey, captcha, customer, paymentMethods, concessions, points }: Props) {
    const t = useT();
    const locale = useLocale();
    const form = useForm({
        idempotency_key: idempotencyKey, ...customer, coupon_code: '', payment_method: 'cod', terms: false,
        addons: {} as Record<number, number>, gift_card_code: '', use_points: false, ...captchaDefaults,
    });
    const errors = form.errors as Record<string, string>;

    const addonsTotal = concessions.reduce((sum, item) => sum + item.price * (form.data.addons[item.id] ?? 0), 0);
    const pointsOff = form.data.use_points ? Math.min(points, Math.floor(total + addonsTotal)) : 0;
    const estimate = Math.max(0, total + addonsTotal - pointsOff);
    const online = form.data.payment_method !== 'cod' && estimate > 0;

    const setQuantity = (id: number, quantity: number) => {
        const next = { ...form.data.addons, [id]: Math.max(0, Math.min(10, quantity)) };
        if (!next[id]) delete next[id];
        form.setData('addons', next);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('user.checkout'), {}, captcha);
    };

    const groups: [string, string][] = [['combo', t('Combos')], ['snack', t('Snacks')], ['drink', t('Drinks')]];

    return (
        <>
            <section className="pb-10 pt-[calc(var(--header)+2.5rem)]">
                <div className="shell">
                    <Breadcrumbs className="mb-10" />
                    <CheckoutSteps step={3} />
                    <SplitHeading as="h1" text={t('Almost there')} accent={[t('there')]} className="text-[clamp(3.5rem,10vw,8.5rem)]" />
                </div>
            </section>

            <form onSubmit={submit} noValidate className="shell grid gap-10 lg:grid-cols-12">
                <div className="space-y-6 lg:col-span-7">
                    {errors.cart && <Alert tone="error">{errors.cart}</Alert>}

                    <fieldset className="panel grid gap-6 p-6 sm:grid-cols-2 sm:p-9">
                        <legend className="sr-only">{t('Contact details')}</legend>
                        <p className="headline text-4xl sm:col-span-2">{t('Who’s coming?')}</p>
                        <Field label={t('Name on the booking')} value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={errors.name} required minLength={3} maxLength={100} autoComplete="name" />
                        <Field label={t('Mobile number')} type="tel" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} error={errors.phone} required minLength={10} maxLength={20} autoComplete="tel" hint={t('The box office may call if a show changes.')} />
                        <Field className="sm:col-span-2" label={t('Email for your e-ticket')} type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="email" />
                        <TextArea className="sm:col-span-2" label={t('Address')} rows={2} value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} error={errors.address} maxLength={500} autoComplete="street-address" hint={t('Optional. Only used for your receipt.')} />
                    </fieldset>

                    {concessions.length > 0 && (
                        <fieldset className="panel p-6 sm:p-9">
                            <legend className="sr-only">{t('Food and drinks')}</legend>
                            <div className="flex flex-wrap items-end justify-between gap-3">
                                <p className="headline text-4xl">{t('Snacks for the show?')}</p>
                                <p className="text-xs text-mute">{t('Ready at the concession counter. Skip the queue.')}</p>
                            </div>
                            {groups.map(([category, label]) => {
                                const list = concessions.filter((item) => item.category === category);
                                if (!list.length) return null;
                                return (
                                    <div key={category} className="mt-6">
                                        <p className="label">{label}</p>
                                        <ul className="mt-3 grid gap-2 sm:grid-cols-2">
                                            {list.map((item) => {
                                                const quantity = form.data.addons[item.id] ?? 0;
                                                const name = locale === 'ur' && item.name_ur ? item.name_ur : item.name;
                                                return (
                                                    <li key={item.id} className={cn('flex items-center gap-4 border p-3 transition-colors', quantity ? 'border-accent bg-volt/10' : 'border-line-2')}>
                                                        <span className="grid h-11 w-11 shrink-0 place-items-center bg-ink-3 text-accent"><Icon name={item.icon} size={22} /></span>
                                                        <span className="min-w-0 flex-1">
                                                            <span className="block text-sm font-semibold">{name}</span>
                                                            <span className="block truncate text-xs text-mute">{item.description}</span>
                                                            <span className="num block text-xs text-paper-2">{money(item.price)}</span>
                                                        </span>
                                                        <span className="flex items-center border border-line-2">
                                                            <button type="button" onClick={() => setQuantity(item.id, quantity - 1)} disabled={!quantity} className="grid h-9 w-9 place-items-center transition hover:bg-ink-3 disabled:opacity-30" aria-label={t('Remove one :name', { name })}><Icon name="minus" size={14} /></button>
                                                            <span className="num w-7 text-center text-sm" aria-live="polite">{quantity}</span>
                                                            <button type="button" onClick={() => setQuantity(item.id, quantity + 1)} disabled={quantity >= 10} className="grid h-9 w-9 place-items-center transition hover:bg-ink-3 disabled:opacity-30" aria-label={t('Add one :name', { name })}><Icon name="plus" size={14} /></button>
                                                        </span>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>
                                );
                            })}
                        </fieldset>
                    )}

                    <fieldset className="panel grid gap-6 p-6 sm:grid-cols-2 sm:p-9">
                        <legend className="sr-only">{t('Discounts')}</legend>
                        <p className="headline text-4xl sm:col-span-2">{t('Have a code?')}</p>
                        <div>
                            <Field label={t('Coupon code')} value={form.data.coupon_code} onChange={(event) => form.setData('coupon_code', event.target.value.toUpperCase())} error={errors.coupon_code}
                                maxLength={30} autoComplete="off" placeholder="WELCOME15" inputClassName="num uppercase tracking-[.2em]" hint={t('Validated when you confirm.')} />
                            <Link href={route('offers')} className="link mt-3 inline-block text-sm text-accent">{t('View offers')}</Link>
                        </div>
                        <div>
                            <Field label={t('Gift card')} value={form.data.gift_card_code} onChange={(event) => form.setData('gift_card_code', event.target.value.toUpperCase())} error={errors.gift_card_code}
                                maxLength={24} autoComplete="off" placeholder="BMM-XXXX-XXXX" inputClassName="num uppercase tracking-[.14em]" hint={t('Its balance comes off after any coupon.')} />
                            <Link href={route('gift-cards')} className="link mt-3 inline-block text-sm text-accent">{t('Check a balance')}</Link>
                        </div>
                        <div className="sm:col-span-2">
                            {points > 0 ? (
                                <Checkbox checked={form.data.use_points} onChange={(event) => form.setData('use_points', event.target.checked)} error={errors.use_points}>
                                    {t('Use my :points loyalty points (1 point = PKR 1 off).', { points: points.toLocaleString('en-PK') })}
                                </Checkbox>
                            ) : (
                                <p className="flex items-center gap-2 text-sm text-mute"><Icon name="star" size={16} className="text-accent" /> {t('This booking earns loyalty points you can spend next time.')}</p>
                            )}
                        </div>
                    </fieldset>

                    {estimate > 0 && (
                        <fieldset className="panel p-6 sm:p-9">
                            <legend className="sr-only">{t('Payment method')}</legend>
                            <p className="headline text-4xl">{t('How will you pay?')}</p>
                            <div className="mt-5 grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label={t('Payment method')}>
                                {paymentMethods.map((method) => {
                                    const checked = form.data.payment_method === method.key;
                                    return (
                                        <label key={method.key} className={cn('relative flex cursor-pointer flex-col gap-2 border p-4 transition-colors', checked ? 'border-accent bg-volt/10' : 'border-line-2 hover:border-paper')}>
                                            <input type="radio" name="payment_method" value={method.key} checked={checked} onChange={() => form.setData('payment_method', method.key)} className="sr-only" />
                                            <span className="flex items-center justify-between gap-3">
                                                <span className="flex items-center gap-3 font-semibold"><PaymentMark method={method.key} /> {method.label}</span>
                                                <span className={cn('grid h-5 w-5 place-items-center border', checked ? 'border-volt bg-volt text-noir' : 'border-line-2')}>{checked && <Icon name="check" size={12} stroke={2.5} />}</span>
                                            </span>
                                            <span className="text-xs text-mute">{method.detail}</span>
                                        </label>
                                    );
                                })}
                            </div>
                            {errors.payment_method && <p className="field-error mt-3">{errors.payment_method}</p>}
                            {paymentMethods.length === 1 && <p className="field-hint mt-3">{t('Online payment appears here once JazzCash, Easypaisa or card payments are switched on.')}</p>}
                        </fieldset>
                    )}

                    <div className="panel space-y-6 p-6 sm:p-9">
                        <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                            onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                        <Checkbox checked={form.data.terms} onChange={(event) => form.setData('terms', event.target.checked)} error={errors.terms}>
                            {online ? t('I agree to pay online now, and understand unpaid bookings can still be paid at the counter.') : t('I understand payment is taken at the cinema counter.')} {t('I can cancel an unpaid booking free until 2 hours before the show under the')} <Link href={route('refund')} className="link text-paper">{t('cancellation policy')}</Link>.
                        </Checkbox>
                    </div>
                </div>

                <aside className="lg:col-span-5">
                    <div className="ticket sticky top-24 p-7 sm:p-9" style={{ ['--tear' as string]: '46%' }}>
                        <p className="label">{t('Your booking')}</p>
                        <div className="mt-5 grid grid-cols-[6rem_1fr] gap-5">
                            <Poster movie={show.movie} size="sm" meta={false} />
                            <div>
                                <p className="headline text-3xl">{show.title}</p>
                                <p className="num mt-2 text-sm text-paper-2">{show.short}</p>
                                <p className="text-sm text-mute">{show.theater} · {show.screen}</p>
                            </div>
                        </div>
                        <div className="perforation -mx-9 my-7" />
                        <ul className="space-y-2 text-sm">
                            {items.map((item) => (
                                <li key={item.id} className="flex justify-between"><span className="text-paper-2"><span className="num">{item.seat}</span> · {item.type}</span><span className="num">{money(item.price)}</span></li>
                            ))}
                            {concessions.filter((item) => form.data.addons[item.id]).map((item) => (
                                <li key={`addon-${item.id}`} className="flex justify-between"><span className="text-paper-2"><span className="num">{form.data.addons[item.id]}×</span> {locale === 'ur' && item.name_ur ? item.name_ur : item.name}</span><span className="num">{money(item.price * form.data.addons[item.id])}</span></li>
                            ))}
                            {pointsOff > 0 && (
                                <li className="flex justify-between text-mint"><span>{t('Loyalty points')}</span><span className="num">− {money(pointsOff)}</span></li>
                            )}
                        </ul>
                        <div className="mt-6 flex items-baseline justify-between border-t border-line pt-5">
                            <span className="text-paper-2">{estimate <= 0 ? t('Nothing to pay') : online ? t('Pay now') : t('Due at counter')}</span>
                            <span className="display text-6xl tabular">{money(estimate)}</span>
                        </div>
                        <p className="mt-2 text-right text-xs text-mute">{t('Coupon and gift card amounts are subtracted when you confirm.')}</p>
                        <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg mt-7 w-full">{form.processing ? t('Confirming…') : online ? t('Confirm & pay :amount', { amount: money(estimate) }) : t('Confirm booking')} <Icon name="arrow-right" size={18} className="arrow" /></button>
                        <p className="mt-4 flex items-start gap-2 text-xs text-mute"><Icon name="lock" size={14} className="mt-0.5" /> {t('Pressing twice is safe: each checkout can only ever create one booking.')}</p>
                    </div>
                </aside>
            </form>
        </>
    );
}

/** Small wordmarks so each method is recognisable at a glance. */
function PaymentMark({ method }: { method: string }) {
    const marks: Record<string, [string, string]> = {
        cod: ['PKR', 'bg-paper text-ink'],
        jazzcash: ['JC', 'bg-[#d6001c] text-white'],
        easypaisa: ['EP', 'bg-[#1ea54b] text-white'],
        card: ['CARD', 'bg-[#635bff] text-white'],
    };
    const [text, tone] = marks[method] ?? ['PAY', 'bg-paper text-ink'];
    return <span className={cn('num grid h-7 min-w-9 place-items-center px-1.5 text-[10px] font-bold', tone)} aria-hidden="true">{text}</span>;
}
