import { Link, useForm } from '@inertiajs/react';
import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useState } from 'react';
import { AccountHeader } from '@/components/account';
import Icon from '@/components/Icon';
import { Field, Select, TextArea } from '@/components/ui';
import { cn, route } from '@/lib/utils';

interface Props {
    profile: {
        name: string; email: string; phone: string; address: string; date_of_birth: string; gender: string;
        avatar: string | null; verified: boolean; member_since: string | null; last_film: string | null; max_birth_date: string;
    };
}

export default function Profile({ profile }: Props) {
    const form = useForm<{
        name: string; email: string; phone: string; address: string; date_of_birth: string; gender: string;
        current_password: string; password: string; password_confirmation: string; profile_picture: File | null;
    }>({
        name: profile.name, email: profile.email, phone: profile.phone, address: profile.address, date_of_birth: profile.date_of_birth, gender: profile.gender,
        current_password: '', password: '', password_confirmation: '', profile_picture: null,
    });
    const [preview, setPreview] = useState<string | null>(profile.avatar);
    const [changePassword, setChangePassword] = useState(false);

    useEffect(() => {
        if (form.errors.password || form.errors.current_password) setChangePassword(true);
    }, [form.errors.password, form.errors.current_password]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(route('user.profile'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('current_password', 'password', 'password_confirmation', 'profile_picture');
                setChangePassword(false);
            },
        });
    };

    return (
        <>
            <AccountHeader title="Profile & security" accent={['security']}>
                Member since {profile.member_since}{profile.last_film ? ` · last booked ${profile.last_film}` : ''}
            </AccountHeader>

            <form onSubmit={submit} noValidate className="shell grid gap-10 lg:grid-cols-12">
                <aside className="lg:col-span-4">
                    <div className="panel sticky top-24 p-8 text-center">
                        <div className="relative mx-auto h-36 w-36">
                            {preview ? (
                                <img src={preview} alt="Profile photo" className="h-36 w-36 object-cover" />
                            ) : (
                                <span className="grid h-36 w-36 place-items-center bg-volt text-7xl font-black uppercase text-noir [font-stretch:62%]">{profile.name.charAt(0)}</span>
                            )}
                            <label htmlFor="profile_picture" className="absolute -bottom-2 -right-2 grid h-10 w-10 cursor-pointer place-items-center border border-line-2 bg-ink transition hover:border-accent hover:text-accent" title="Change photo">
                                <Icon name="plus" size={16} /><span className="sr-only">Change profile photo</span>
                            </label>
                            <input id="profile_picture" type="file" accept="image/jpeg,image/png,image/webp" className="sr-only"
                                onChange={(event) => {
                                    const file = event.target.files?.[0] ?? null;
                                    form.setData('profile_picture', file);
                                    if (file) setPreview(URL.createObjectURL(file));
                                }} />
                        </div>
                        <p className="headline mt-6 text-3xl">{profile.name}</p>
                        <p className="text-sm text-mute">{profile.email}</p>
                        <p className="mt-4 text-xs text-mute">JPG, PNG or WebP, up to 2 MB, at least 96 × 96 px.</p>
                        {form.errors.profile_picture && <p className="field-error mt-3 justify-center">{form.errors.profile_picture}</p>}
                        <span className={cn('tag mt-6', profile.verified ? 'tag-mint' : 'tag-volt')}>{profile.verified ? 'Email verified' : 'Unverified'}</span>
                    </div>
                </aside>

                <div className="space-y-6 lg:col-span-8">
                    <fieldset className="panel grid gap-6 p-6 sm:grid-cols-2 sm:p-9">
                        <legend className="sr-only">Personal details</legend>
                        <p className="headline text-4xl sm:col-span-2">Personal details</p>
                        <Field label="Full name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={form.errors.name} required minLength={3} maxLength={100} autoComplete="name" />
                        <Field label="Date of birth" type="date" value={form.data.date_of_birth} onChange={(event) => form.setData('date_of_birth', event.target.value)} error={form.errors.date_of_birth} max={profile.max_birth_date} hint="Used to check age-rated films. Never shown publicly." />
                        <Select label="Gender" value={form.data.gender} onChange={(event) => form.setData('gender', event.target.value)} error={form.errors.gender}
                            options={[['', 'Prefer not to say'], ['female', 'Female'], ['male', 'Male'], ['other', 'Other'], ['prefer_not_to_say', 'Prefer not to say']]} />
                        <Field label="Mobile number" type="tel" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} error={form.errors.phone} minLength={10} maxLength={20} autoComplete="tel" />
                        <TextArea className="sm:col-span-2" label="Address" rows={2} value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} error={form.errors.address} maxLength={500} autoComplete="street-address" />
                    </fieldset>

                    <fieldset className="panel grid gap-6 p-6 sm:p-9">
                        <legend className="sr-only">Sign-in and security</legend>
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <p className="headline text-4xl">Sign-in & security</p>
                            <button type="button" className="btn btn-ghost btn-sm" onClick={() => setChangePassword(!changePassword)}>{changePassword ? 'Keep current password' : 'Change password'}</button>
                        </div>
                        <Field label="Email address" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} required maxLength={150} autoComplete="email" hint="Changing your email requires your current password." />
                        <AnimatePresence initial={false}>
                            {changePassword && (
                                <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: 'auto', opacity: 1 }} exit={{ height: 0, opacity: 0 }} className="overflow-hidden">
                                    <div className="grid gap-6 sm:grid-cols-2">
                                        <Field label="New password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} autoComplete="new-password" maxLength={72} hint="At least 8 characters with upper and lower case letters and a number." />
                                        <Field label="Confirm new password" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" maxLength={72} />
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                        <Field label="Current password" type="password" value={form.data.current_password} onChange={(event) => form.setData('current_password', event.target.value)} error={form.errors.current_password} autoComplete="current-password" maxLength={72}
                            hint="Only needed to change your email or password. Other devices are signed out after a password change." />
                    </fieldset>

                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <p className="text-sm text-mute">We never share your details with advertisers. <Link href={route('privacy')} className="link text-paper-2">Privacy policy</Link></p>
                        <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg">{form.processing ? 'Saving…' : 'Save changes'}</button>
                    </div>
                </div>
            </form>
        </>
    );
}
