import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import { Field, TextArea } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { route } from '@/lib/utils';

type Values = Record<
    'site_name' | 'site_tagline' | 'support_email' | 'support_phone' | 'contact_address' | 'footer_description' | 'copyright_note' | 'default_meta_title' | 'default_meta_description'
    | 'social_website' | 'social_github' | 'social_linkedin' | 'social_instagram' | 'social_facebook' | 'social_x' | 'social_youtube', string>;

const SOCIALS: [keyof Values, string, string][] = [
    ['social_website', 'Website', 'https://ahmershah.dev/'], ['social_github', 'GitHub', 'https://github.com/…'], ['social_linkedin', 'LinkedIn', 'https://linkedin.com/in/…'],
    ['social_instagram', 'Instagram', 'https://instagram.com/…'], ['social_facebook', 'Facebook', 'https://facebook.com/…'], ['social_x', 'X', 'https://x.com/…'], ['social_youtube', 'YouTube', 'https://youtube.com/@…'],
];

export default function AdminSettings({ values, logoUrl, customLogo }: { values: Values; logoUrl: string; customLogo: boolean }) {
    const form = useForm<Values & { logo: File | null; reset_logo: boolean }>({ ...values, logo: null, reset_logo: false });
    const [preview, setPreview] = useState(logoUrl);
    const field = (key: keyof Values, label: string, extra: Record<string, unknown> = {}) => (
        <Field label={label} value={form.data[key]} onChange={(event) => form.setData(key, event.target.value)} error={form.errors[key]} {...extra} />
    );

    return (
        <AdminLayout label="Settings" title="Site & brand" lede="The name, logo, contact details and social links shown across the site, the footer, the menu, emails and search results. Changes go live as soon as you save."
            guide={<Guide id="settings" steps={[
                ['Name and tagline', 'Shown in the menu, browser tab, emails and Google results.'],
                ['Upload a logo', 'A transparent PNG or WebP works best. You can go back to the default logo at any time.'],
                ['Contact details', 'Email, phone and address appear on the Contact page and in the footer.'],
                ['Social links', 'Leave a box empty to hide that icon from the footer.'],
            ]} />}>
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.settings.update'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.setData('logo', null) }); }} className="grid gap-6 xl:grid-cols-12" noValidate>
                <div className="space-y-6 xl:col-span-7">
                    <fieldset className="panel grid gap-5 p-6 sm:grid-cols-2">
                        <legend className="label label-accent px-1">Identity</legend>
                        {field('site_name', 'Site name', { required: true, maxLength: 40, placeholder: 'e.g. BookMyMovie' })}
                        {field('site_tagline', 'Tagline', { maxLength: 80, placeholder: 'e.g. Cinema tickets for Pakistan' })}
                        <TextArea className="sm:col-span-2" label="Footer description" placeholder="Two short sentences about the site for the footer" rows={2} value={form.data.footer_description} onChange={(event) => form.setData('footer_description', event.target.value)} error={form.errors.footer_description} maxLength={200} />
                        {field('copyright_note', 'Copyright line', { maxLength: 80, placeholder: 'e.g. MIT licence', hint: 'Shown after “© year Site name ·”.' })}
                    </fieldset>

                    <fieldset className="panel grid gap-5 p-6 sm:grid-cols-2">
                        <legend className="label label-accent px-1">Contact</legend>
                        {field('support_email', 'Support email', { type: 'email', required: true, placeholder: 'support@bookmymovie.pk' })}
                        {field('support_phone', 'Phone', { type: 'tel', placeholder: '+92 300 1234567' })}
                        <div className="sm:col-span-2">{field('contact_address', 'Address or city', { placeholder: 'e.g. Clifton, Karachi' })}</div>
                    </fieldset>

                    <fieldset className="panel grid gap-5 p-6 sm:grid-cols-2">
                        <legend className="label label-accent px-1">Social links</legend>
                        <p className="text-sm text-mute sm:col-span-2">Leave a link empty to hide it. HTTPS links only.</p>
                        {SOCIALS.map(([key, label, placeholder]) => <div key={key}>{field(key, label, { type: 'url', placeholder })}</div>)}
                    </fieldset>

                    <fieldset className="panel grid gap-5 p-6">
                        <legend className="label label-accent px-1">Search engines</legend>
                        {field('default_meta_title', 'Default page title', { maxLength: 60, placeholder: 'e.g. BookMyMovie | Cinema tickets in Pakistan', hint: `${form.data.default_meta_title.length}/60 characters` })}
                        <TextArea label="Default description" placeholder="Under 160 characters, used when a page has no description" rows={2} value={form.data.default_meta_description} onChange={(event) => form.setData('default_meta_description', event.target.value)} error={form.errors.default_meta_description} maxLength={160}
                            hint={`${form.data.default_meta_description.length}/160 characters`} />
                    </fieldset>
                </div>

                <div className="space-y-6 xl:col-span-5">
                    <div className="panel sticky top-6 space-y-5 p-6">
                        <p className="label label-accent">Logo</p>
                        <div className="grid h-48 place-items-center border border-dashed border-line-2 bg-ink-3 p-6">
                            <img src={preview} alt="Current logo" className="max-h-full max-w-full object-contain" />
                        </div>
                        <label className="btn btn-ghost w-full cursor-pointer">
                            <Icon name="plus" size={16} /> Upload a new logo
                            <input type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" onChange={(event) => {
                                const file = event.target.files?.[0] ?? null;
                                form.setData('logo', file);
                                form.setData('reset_logo', false);
                                if (file) setPreview(URL.createObjectURL(file));
                            }} />
                        </label>
                        {form.errors.logo && <p className="field-error">{form.errors.logo}</p>}
                        <p className="text-xs text-mute">PNG, JPG or WebP up to 1 MB, at least 120×60. A transparent PNG or WebP looks best on both themes. SVG is not accepted because it can carry scripts.</p>
                        {customLogo && (
                            <button type="button" className="link text-xs text-signal" onClick={() => { form.setData('reset_logo', true); form.setData('logo', null); setPreview('/images/logo-sm.webp'); }}>Go back to the original logo</button>
                        )}
                        <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary btn-lg w-full">{form.processing ? 'Saving…' : 'Save changes'}</button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}

AdminSettings.layout = null;
