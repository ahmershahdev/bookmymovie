import { Link, useForm } from '@inertiajs/react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import Icon from '@/components/Icon';
import { Field, PageHeader, Select, TextArea } from '@/components/ui';
import { submitForm } from '@/lib/form';
import { cn, route, useShared } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

export default function Contact({ topics, captcha }: { topics: Record<string, string>; captcha: CaptchaData }) {
    const { site, auth } = useShared();
    const form = useForm({
        name: auth.user?.name ?? '',
        email: auth.user?.email ?? '',
        topic: '',
        booking_number: '',
        message: '',
        ...captchaDefaults,
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('contact'), {
            onSuccess: () => form.reset('topic', 'booking_number', 'message', 'custom_captcha_answer'),
        }, captcha);
    };

    return (
        <>
            <PageHeader label="Contact" title={site.contact_heading ?? 'We read every message'} accent={['every']} lede={site.contact_intro ?? undefined} />

            <section className="pt-16">
                <div className="shell grid gap-16 lg:grid-cols-12">
                    <aside className="space-y-10 lg:col-span-4">
                        <div>
                            <p className="label">Email</p>
                            <a href={`mailto:${site.support_email}`} className="link headline mt-3 block break-all text-3xl">{site.support_email}</a>
                        </div>
                        {site.support_phone && (
                            <div>
                                <p className="label">Phone</p>
                                <a href={`tel:${site.support_phone.replace(/\s+/g, '')}`} className="link num mt-3 block text-3xl">{site.support_phone}</a>
                                <p className="mt-2 text-sm text-mute">Monday to Saturday, 10 AM to 10 PM PKT</p>
                            </div>
                        )}
                        <dl className="grid grid-cols-2 gap-6 border-t border-line pt-8 text-sm">
                            <div><dt className="label">Response time</dt><dd className="mt-2">{site.response_sla}</dd></div>
                            <div><dt className="label">Service area</dt><dd className="mt-2">{site.service_area}</dd></div>
                        </dl>
                        <div className="panel p-6">
                            <p className="font-semibold">Faster answers</p>
                            <ul className="mt-4 space-y-3 text-sm">
                                {[['Cancel a booking', route('user.bookings')], ['How e-tickets work', route('eticket.info')], ['Refund policy', route('refund')], ['Accessibility at our cinemas', route('accessibility')]].map(([label, href]) => (
                                    <li key={label}><Link href={href} className="group flex items-center justify-between text-paper-2 hover:text-paper">{label} <Icon name="arrow-right" size={14} className="text-dim transition group-hover:translate-x-1 group-hover:text-accent" /></Link></li>
                                ))}
                            </ul>
                        </div>
                        <p className="flex items-start gap-3 text-sm text-mute"><Icon name="shield" size={18} className="mt-0.5 text-accent" /> Found a security issue? Please follow our responsible disclosure policy in SECURITY.md instead of posting it publicly.</p>
                    </aside>

                    <div className="lg:col-span-8">
                        <form onSubmit={submit} noValidate className="panel grid gap-6 p-6 sm:grid-cols-2 sm:p-10">
                            <Field label="Your name" name="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={form.errors.name} required minLength={2} maxLength={100} autoComplete="name" />
                            <Field label="Email" type="email" name="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} required maxLength={150} autoComplete="email" hint="We reply to this address." />
                            <Select label="What is it about?" name="topic" value={form.data.topic} onChange={(event) => form.setData('topic', event.target.value)} error={form.errors.topic} required options={[['', 'Choose a topic'], ...Object.entries(topics)]} />
                            <Field label="Booking number" name="booking_number" value={form.data.booking_number} onChange={(event) => form.setData('booking_number', event.target.value)} error={form.errors.booking_number} hint="Optional, e.g. BM-2026-8KQ2Z7TX" maxLength={24} autoComplete="off" inputClassName="num uppercase" />
                            <TextArea className="sm:col-span-2" label="Message" name="message" rows={7} value={form.data.message} onChange={(event) => form.setData('message', event.target.value)} error={form.errors.message} required minLength={20} maxLength={2000}
                                placeholder="Tell us what happened, including the cinema, date and time if it is about a show."
                                aside={<span className={cn('num text-[11px]', form.data.message.length > 1900 ? 'text-signal' : 'text-dim')}>{form.data.message.length}/2000</span>} />
                            <div className="sm:col-span-2">
                                <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={(form.errors as Record<string, string>).captcha}
                                    onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)}
                                    onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                            </div>
                            <div className="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-xs text-mute">By sending this message you agree to our <Link href={route('privacy')} className="link text-paper-2">privacy policy</Link>.</p>
                                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg">{form.processing ? 'Sending…' : 'Send message'} <Icon name="arrow-right" size={18} className="arrow" /></button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </>
    );
}
