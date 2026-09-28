import { Link, useForm } from '@inertiajs/react';
import { CheckoutSteps } from '@/components/account';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Alert, Breadcrumbs, Checkbox, Field, TextArea } from '@/components/ui';
import { submitForm } from '@/lib/form';
import { cn, money, route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';
import type { CartItem, CartShow } from './Cart';

interface Props {
    total: number;
    show: CartShow;
    items: CartItem[];
    idempotencyKey: string;
    captcha: CaptchaData;
    customer: { name: string; email: string; phone: string; address: string };
    paymentMethods: { key: string; label: string; detail: string }[];
}

export default function Checkout({ total, show, items, idempotencyKey, captcha, customer, paymentMethods }: Props) {
    const form = useForm({ idempotency_key: idempotencyKey, ...customer, coupon_code: '', payment_method: 'cod', terms: false, ...captchaDefaults });
    const online = form.data.payment_method !== 'cod';
    const errors = form.errors as Record<string, string>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('user.checkout'), {}, captcha);
    };

    return (
        <>
            <section className="pb-10 pt-[calc(var(--header)+2.5rem)]">
                <div className="shell">
                    <Breadcrumbs className="mb-10" />
                    <CheckoutSteps step={3} />
                    <SplitHeading as="h1" text="Almost there" accent={['there']} className="text-[clamp(3.5rem,10vw,8.5rem)]" />
                </div>
            </section>

            <form onSubmit={submit} noValidate className="shell grid gap-10 lg:grid-cols-12">
                <div className="space-y-6 lg:col-span-7">
                    {errors.cart && <Alert tone="error">{errors.cart}</Alert>}

                    <fieldset className="panel grid gap-6 p-6 sm:grid-cols-2 sm:p-9">
                        <legend className="sr-only">Contact details</legend>
                        <p className="headline text-4xl sm:col-span-2">Who’s coming?</p>
                        <Field label="Name on the booking" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={errors.name} required minLength={3} maxLength={100} autoComplete="name" />
                        <Field label="Mobile number" type="tel" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} error={errors.phone} required minLength={10} maxLength={20} autoComplete="tel" hint="The box office may call if a show changes." />
                        <Field className="sm:col-span-2" label="Email for your e-ticket" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="email" />
                        <TextArea className="sm:col-span-2" label="Address" rows={2} value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} error={errors.address} maxLength={500} autoComplete="street-address" hint="Optional. Only used for your receipt." />
                    </fieldset>

                    <fieldset className="panel p-6 sm:p-9">
                        <legend className="sr-only">Coupon</legend>
                        <p className="headline text-4xl">Have a code?</p>
                        <Field className="mt-5" label="Coupon code" value={form.data.coupon_code} onChange={(event) => form.setData('coupon_code', event.target.value.toUpperCase())} error={errors.coupon_code}
                            maxLength={30} autoComplete="off" placeholder="WELCOME15" inputClassName="num uppercase tracking-[.2em]" hint="Validated when you confirm. See current codes on the offers page." />
                        <Link href={route('offers')} className="link mt-3 inline-block text-sm text-accent">View offers</Link>
                    </fieldset>

                    <fieldset className="panel p-6 sm:p-9">
                        <legend className="sr-only">Payment method</legend>
                        <p className="headline text-4xl">How will you pay?</p>
                        <div className="mt-5 grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Payment method">
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
                        {paymentMethods.length === 1 && <p className="field-hint mt-3">Online payment appears here once JazzCash, Easypaisa or card payments are switched on.</p>}
                    </fieldset>

                    <div className="panel space-y-6 p-6 sm:p-9">
                        <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                            onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                        <Checkbox checked={form.data.terms} onChange={(event) => form.setData('terms', event.target.checked)} error={errors.terms}>
                            {online ? 'I agree to pay online now, and understand unpaid bookings can still be paid at the counter.' : 'I understand payment is taken at the cinema counter.'} I can cancel an unpaid booking free until 2 hours before the show under the <Link href={route('refund')} className="link text-paper">cancellation policy</Link>.
                        </Checkbox>
                    </div>
                </div>

                <aside className="lg:col-span-5">
                    <div className="ticket sticky top-24 p-7 sm:p-9" style={{ ['--tear' as string]: '46%' }}>
                        <p className="label">Your booking</p>
                        <div className="mt-5 grid grid-cols-[5rem_1fr] gap-5">
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
                        </ul>
                        <div className="mt-6 flex items-baseline justify-between border-t border-line pt-5">
                            <span className="text-paper-2">{online ? 'Pay now' : 'Due at counter'}</span>
                            <span className="display text-6xl tabular">{money(total)}</span>
                        </div>
                        <p className="mt-2 text-right text-xs text-mute">Coupon discounts are subtracted when you confirm.</p>
                        <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg mt-7 w-full">{form.processing ? 'Confirming…' : online ? `Confirm & pay ${money(total)}` : 'Confirm booking'} <Icon name="arrow-right" size={18} className="arrow" /></button>
                        <p className="mt-4 flex items-start gap-2 text-xs text-mute"><Icon name="lock" size={14} className="mt-0.5" /> Pressing twice is safe: each checkout can only ever create one booking.</p>
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
