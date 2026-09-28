import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import { Wordmark } from '@/components/shell/Navbar';
import ProgressBar from '@/components/shell/ProgressBar';
import ScrollIndicator from '@/components/shell/ScrollIndicator';
import Toaster from '@/components/shell/Toaster';
import { Alert, Checkbox, Field, Select, TextArea } from '@/components/ui';
import { DemoBanner } from '@/layouts/AdminLayout';
import { Meta } from '@/layouts/SiteLayout';
import { scrollToTarget } from '@/lib/scroll';
import { cn, route, useShared } from '@/lib/utils';

type Row = { label: string; value: string };
type MovieData = {
    id: number; title: string; slug: string; language: string; duration_minutes: number; certificate_rating: string; status: string; release_date: string;
    genre_ids: number[]; base_price: string; sale_price: string; tagline: string; rating_mode: string; fake_average_rating: string; fake_total_reviews: string;
    description: string; hero_carousel_enabled: boolean; hero_sort_order: string; hero_eyebrow: string; hero_tagline: string; meta_title: string; meta_description: string;
    kids_discount_eligible: boolean; poster_url: string | null; hero_url: string | null;
};

interface Props {
    admin: { name: string; email: string };
    sessionMinutes: number;
    stats: { revenue: number; bookings: number; users: number; topMovie: string; activeShows: number; pendingReviews: number };
    settings: Record<'site_name' | 'canonical_base_url' | 'default_meta_title' | 'default_meta_description' | 'support_email' | 'home_ticker_messages', string>;
    contentPages: { id: number; slug: string; title: string; meta_title: string; meta_description: string; canonical_path: string; excerpt: string }[];
    selectedMovie: MovieData | null;
    movieOptions: { id: number; label: string }[];
    genres: { id: number; name: string }[];
    recentMovies: Row[];
    trashedMovies: { id: number; title: string }[];
    schedule: Row[];
    selectedMovieShows: { id: number; venue: string; when: string; available: number; booked: number }[];
    rowPriceBenefits: { row: string; tier: string; price: string; benefits: string }[];
    bookings: Row[];
    coupons: Row[];
    users: Row[];
    customers: Row[];
    reviews: Row[];
    certificates: string[];
    today: string;
}

const nav = [
    ['overview', 'Overview', 'grid'], ['movies', 'Movies', 'film'], ['settings', 'Website', 'settings'], ['seo', 'SEO', 'search'],
    ['shows', 'Shows', 'calendar'], ['bookings', 'Bookings', 'ticket'], ['coupons', 'Coupons', 'tag'], ['users', 'Users', 'user'],
    ['reviews', 'Reviews', 'star'], ['messages', 'Messages', 'megaphone'], ['profile', 'Profile', 'lock'],
] as const;

const post = (data: Record<string, unknown>, options: Record<string, unknown> = {}) =>
    router.post(route('admin.dashboard'), data as never, { preserveScroll: true, ...options });

export default function AdminDashboard(props: Props) {
    const { errors } = useShared();
    const [active, setActive] = useState('overview');
    const firstError = Object.values(errors)[0];

    useEffect(() => {
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (visible) setActive(visible.target.id);
        }, { rootMargin: '-20% 0px -65% 0px', threshold: [0.1, 0.3] });
        nav.forEach(([id]) => {
            const node = document.getElementById(id);
            if (node) observer.observe(node);
        });
        return () => observer.disconnect();
    }, []);

    return (
        <>
            <Meta />
            <ProgressBar />
            <div className="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
                <aside className="z-20 flex flex-col border-b border-line bg-ink-2 p-4 lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r lg:p-5">
                    <div className="flex items-center justify-between gap-3">
                        <Link href={route('home')}><Wordmark /></Link>
                        <Link href={route('admin.logout')} method="post" as="button" className="btn btn-ghost btn-sm lg:hidden">Logout</Link>
                    </div>
                    <div className="mt-5 border border-line p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="label label-accent">Box office</p>
                                <p className="mt-2 text-sm font-semibold">{props.admin.name}</p>
                            </div>
                            <span className="tag tag-mint">Live</span>
                        </div>
                        <p className="num mt-3 text-[11px] text-mute">Session · {props.sessionMinutes} min</p>
                    </div>
                    <nav className="no-scrollbar mt-5 flex gap-1 overflow-x-auto lg:flex-1 lg:flex-col lg:overflow-y-auto" data-lenis-prevent aria-label="Admin sections">
                        {nav.map(([id, label, icon]) => (
                            <button key={id} type="button" onClick={() => scrollToTarget(`#${id}`, -24)} aria-current={active === id ? 'true' : undefined}
                                className={cn('flex shrink-0 items-center gap-3 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-[.08em] transition [font-stretch:115%]',
                                    active === id ? 'bg-volt text-noir' : 'text-mute hover:bg-ink-3 hover:text-paper')}>
                                <Icon name={icon} size={16} /> {label}
                            </button>
                        ))}
                    </nav>
                    <div className="mt-3 grid gap-1 border-t border-line pt-3">
                        {([['Members', 'admin.users', 'user'], ['Reviews', 'admin.reviews', 'star'], ['Bookings & refunds', 'admin.activity', 'ticket'], ['Site & brand', 'admin.settings', 'settings']] as const).map(([label, name, icon]) => (
                            <Link key={name} href={route(name)} className="flex items-center justify-between gap-3 px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[.08em] text-paper transition hover:bg-ink-3 hover:text-accent [font-stretch:115%]">
                                <span className="flex items-center gap-3"><Icon name={icon} size={16} /> {label}</span> <Icon name="arrow-right" size={14} />
                            </Link>
                        ))}
                    </div>
                    <div className="mt-4 hidden gap-2 border-t border-line pt-4 lg:grid">
                        <Link href={route('home')} className="btn btn-ghost btn-sm">Public site <Icon name="arrow-up-right" size={14} /></Link>
                        <Link href={route('admin.logout')} method="post" as="button" className="btn btn-primary btn-sm"><Icon name="logout" size={14} /> Logout</Link>
                    </div>
                </aside>

                <main id="main" className="min-w-0 space-y-6 p-4 sm:p-6 lg:p-10">
                    <header id="overview" className="scroll-mt-6 grid gap-6 border-b border-line pb-8 xl:grid-cols-[1fr_auto] xl:items-end">
                        <div>
                            <p className="label label-accent">Admin panel</p>
                            <h1 className="display mt-3 text-[clamp(3.5rem,7vw,6.5rem)]">Operations</h1>
                            <p className="mt-3 max-w-2xl text-sm text-mute">Manage the catalogue, hero carousel, seat pricing, bookings, users, SEO and global site settings.</p>
                        </div>
                        <Link href={route('home')} className="btn btn-ghost">View live site <Icon name="arrow-up-right" size={14} /></Link>
                    </header>

                    <DemoBanner />
                    {firstError && <Alert tone="error">{firstError}</Alert>}

                    <div className="grid-lines sm:grid-cols-2 xl:grid-cols-3">
                        {([
                            ['Revenue', `PKR ${Math.round(props.stats.revenue).toLocaleString('en-PK')}`],
                            ['Bookings', props.stats.bookings.toLocaleString('en-PK')],
                            ['Users', props.stats.users.toLocaleString('en-PK')],
                            ['Active shows', props.stats.activeShows.toLocaleString('en-PK')],
                            ['Pending reviews', props.stats.pendingReviews.toLocaleString('en-PK')],
                            ['Top movie', props.stats.topMovie],
                        ]).map(([label, value]) => (
                            <div key={label} className="p-6">
                                <p className="label">{label}</p>
                                <p className="display mt-3 truncate text-5xl" title={value}>{value}</p>
                            </div>
                        ))}
                    </div>
                    <div className="grid gap-6 lg:grid-cols-2">
                        <List title="Recent movies" rows={props.recentMovies} />
                        <List title="Top customers" rows={props.customers} />
                    </div>

                    <Panel id="movies" label="Movies" title="Catalogue workspace" description="Create, edit, remove, restore, upload media and control home hero placement.">
                        <div className="grid gap-3 border border-line p-5 sm:grid-cols-[1fr_auto] sm:items-end">
                            <Select label="Edit a movie" value={String(props.selectedMovie?.id ?? '')} onChange={(event) => router.visit(route('admin.dashboard.movie', event.target.value), { preserveScroll: true, preserveState: false })}
                                options={props.movieOptions.map((option) => [String(option.id), option.label] as [string, string])} />
                        </div>

                        {props.selectedMovie && <MovieEditor key={props.selectedMovie.id} movie={props.selectedMovie} {...props} />}

                        <div className="border-t border-line pt-10">
                            <h3 className="headline text-4xl">Add a new movie</h3>
                            <MovieEditor movie={null} {...props} />
                        </div>

                        {props.trashedMovies.length > 0 && (
                            <div className="border border-line p-6">
                                <h3 className="headline text-3xl">Removed movies</h3>
                                <div className="mt-5 grid gap-2 md:grid-cols-2">
                                    {props.trashedMovies.map((movie) => (
                                        <div key={movie.id} className="flex items-center justify-between gap-4 border border-line p-4">
                                            <span className="text-sm">{movie.title}</span>
                                            <button type="button" className="btn btn-ghost btn-sm" onClick={() => post({ _action: 'restore_movie', movie_id: movie.id })}>Restore</button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </Panel>

                    <SettingsPanel settings={props.settings} />
                    <SeoPanel pages={props.contentPages} />

                    <Panel id="shows" label="Shows" title="Schedule & pricing" description="Upcoming runs for the selected movie and the row-by-row price ladder.">
                        <div className="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
                            <div className="border border-line p-6">
                                <h3 className="headline text-3xl">{props.selectedMovie?.title ?? 'Selected movie'} showtimes</h3>
                                <div className="mt-5 grid gap-2 sm:grid-cols-2">
                                    {props.selectedMovieShows.length ? props.selectedMovieShows.map((show) => (
                                        <article key={show.id} className="border border-line p-4">
                                            <p className="text-sm font-semibold">{show.venue}</p>
                                            <p className="num mt-1.5 text-xs text-mute">{show.when}</p>
                                            <div className="mt-4 flex justify-between"><span className="tag tag-mint">{show.available} free</span><span className="tag">{show.booked} booked</span></div>
                                        </article>
                                    )) : <p className="col-span-full p-6 text-center text-sm text-mute">No upcoming scheduled showtimes for the selected movie.</p>}
                                </div>
                            </div>
                            <div className="border border-line p-6">
                                <h3 className="headline text-3xl">Seat tier benefits</h3>
                                <div className="mt-5 space-y-2">
                                    {props.rowPriceBenefits.length ? props.rowPriceBenefits.map((tier) => (
                                        <div key={tier.row} className="border border-line p-4">
                                            <div className="flex items-center justify-between gap-4"><p className="text-sm font-semibold">Row {tier.row} · <span className="font-normal text-mute">{tier.tier}</span></p><p className="num text-sm text-accent">{tier.price}</p></div>
                                            <p className="mt-2 text-xs text-mute">{tier.benefits}</p>
                                        </div>
                                    )) : <p className="p-6 text-center text-sm text-mute">Run migrations and seed row prices to populate the benefits ladder.</p>}
                                </div>
                            </div>
                        </div>
                        <List title="Latest schedule" rows={props.schedule} />
                    </Panel>

                    <Panel id="bookings" label="Bookings" title="Recent transactions"><List rows={props.bookings} /></Panel>
                    <CouponPanel coupons={props.coupons} />
                    <Panel id="users" label="Users" title="Customer accounts"><List rows={props.users} /></Panel>
                    <Panel id="reviews" label="Reviews" title="Moderation"><List rows={props.reviews} /></Panel>
                    <MessagePanel />
                    <ProfilePanel admin={props.admin} />
                </main>
            </div>
            <Toaster />
            <ScrollIndicator />
        </>
    );
}

AdminDashboard.layout = null;

function Panel({ id, label, title, description, children }: { id: string; label: string; title: string; description?: string; children: ReactNode }) {
    return (
        <section id={id} className="scroll-mt-6 space-y-6 border border-line bg-ink-2 p-5 sm:p-8">
            <div>
                <p className="label label-accent">{label}</p>
                <h2 className="display mt-2 text-6xl">{title}</h2>
                {description && <p className="mt-2 text-sm text-mute">{description}</p>}
            </div>
            {children}
        </section>
    );
}

function List({ title, rows }: { title?: string; rows: Row[] }) {
    return (
        <div>
            {title && <p className="label mb-3">{title}</p>}
            <ul className="divide-y divide-line border border-line">
                {rows.length ? rows.map((row, index) => (
                    <li key={`${row.label}-${index}`} className="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <span className="text-sm">{row.label}</span>
                        <span className="num text-xs text-mute">{row.value}</span>
                    </li>
                )) : <li className="p-10 text-center text-sm text-mute">No records found.</li>}
            </ul>
        </div>
    );
}

function MovieEditor({ movie, genres, certificates, today }: { movie: MovieData | null } & Pick<Props, 'genres' | 'certificates' | 'today'>) {
    const editing = Boolean(movie);
    const form = useForm({
        _action: editing ? 'update_movie' : 'store_movie',
        movie_id: movie?.id ?? '',
        title: movie?.title ?? '',
        slug: movie?.slug ?? '',
        language: movie?.language ?? 'English',
        duration_minutes: String(movie?.duration_minutes ?? 120),
        certificate_rating: movie?.certificate_rating || 'UA',
        status: movie?.status ?? 'coming_soon',
        release_date: movie?.release_date ?? today,
        genre_ids: (movie?.genre_ids ?? []) as number[],
        base_price: movie?.base_price ?? '2500',
        sale_price: movie?.sale_price ?? '',
        tagline: movie?.tagline ?? '',
        rating_mode: movie?.rating_mode ?? 'real',
        fake_average_rating: movie?.fake_average_rating ?? '',
        fake_total_reviews: movie?.fake_total_reviews ?? '',
        description: movie?.description ?? '',
        hero_carousel_enabled: movie?.hero_carousel_enabled ?? false,
        hero_sort_order: movie?.hero_sort_order ?? '0',
        hero_eyebrow: movie?.hero_eyebrow ?? '',
        hero_tagline: movie?.hero_tagline ?? '',
        meta_title: movie?.meta_title ?? '',
        meta_description: movie?.meta_description ?? '',
        kids_discount_eligible: movie?.kids_discount_eligible ?? false,
        poster_upload: null as File | null,
        hero_upload: null as File | null,
    });
    const [confirmDelete, setConfirmDelete] = useState(false);
    const errors = form.errors as Record<string, string>;
    const text = (name: keyof typeof form.data) => ({
        value: String(form.data[name] ?? ''),
        onChange: (event: { target: { value: string } }) => form.setData(name, event.target.value as never),
        error: errors[name as string],
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(route('admin.dashboard'), { forceFormData: true, preserveScroll: true, onSuccess: () => !editing && form.reset() });
    };

    return (
        <>
            <form onSubmit={submit} className="mt-6 grid gap-5 border border-line p-5 sm:p-6 lg:grid-cols-2">
                <Field label="Title" {...text('title')} required />
                <Field label="Slug" {...text('slug')} hint="Leave empty to generate from the title." />
                <Field label="Language" {...text('language')} />
                <Field label="Duration (minutes)" type="number" min={1} {...text('duration_minutes')} />
                <Select label="Certificate" {...text('certificate_rating')} options={certificates.map((value) => [value, value] as [string, string])} />
                <Select label="Status" {...text('status')} options={[['coming_soon', 'Coming soon'], ['now_showing', 'Now showing'], ['ended', 'Ended']]} />
                <Field label="Release date" type="date" {...text('release_date')} />
                <div className="field">
                    <span className="field-label">Genres</span>
                    <div className="flex flex-wrap gap-1.5">
                        {genres.map((genre) => {
                            const on = form.data.genre_ids.includes(genre.id);
                            return (
                                <button key={genre.id} type="button" aria-pressed={on} className="chip"
                                    onClick={() => form.setData('genre_ids', on ? form.data.genre_ids.filter((id) => id !== genre.id) : [...form.data.genre_ids, genre.id])}>
                                    {genre.name}
                                </button>
                            );
                        })}
                    </div>
                    {errors.genre_ids && <p className="field-error">{errors.genre_ids}</p>}
                </div>
                <Field label="Base price" type="number" step="0.01" {...text('base_price')} />
                <Field label="Sale price" type="number" step="0.01" {...text('sale_price')} />
                <Field label="Tagline" maxLength={180} {...text('tagline')} />
                <Select label="Rating mode" {...text('rating_mode')} options={[['real', 'Use real reviews'], ['fake', 'Use manual rating']]} />
                <Field label="Manual rating" type="number" min={1} max={5} step="0.1" {...text('fake_average_rating')} />
                <Field label="Manual reviews count" type="number" min={0} {...text('fake_total_reviews')} />
                <TextArea className="lg:col-span-2" label="Description" rows={4} {...text('description')} />

                {([['poster_upload', 'Poster image', '9:16 vertical', 'Movie cards, detail page, search and watchlist.', movie?.poster_url, 'aspect-[9/16] w-28'], ['hero_upload', 'Carousel image', '16:9 widescreen', 'Home carousel and detail backdrop.', movie?.hero_url, 'aspect-video w-48']] as const).map(([field, title, ratio, usage, current, previewClass]) => (
                    <div key={field} className="border border-line p-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="field-label">{title}</p>
                                <p className="mt-1 text-sm font-semibold">{ratio}</p>
                                <p className="mt-1 text-xs text-mute">{usage}</p>
                                <p className="mt-2 text-xs text-dim">PNG, JPG or WebP, up to 3 MB. Saved as optimised WebP.</p>
                            </div>
                            {current && <img src={current} alt={`${title} preview`} className={cn('shrink-0 border border-line object-cover', previewClass)} />}
                        </div>
                        <input type="file" accept="image/png,image/jpeg,image/webp" className="input mt-4 cursor-pointer" aria-label={title}
                            onChange={(event) => form.setData(field, event.target.files?.[0] ?? null)} />
                        {errors[field] && <p className="field-error mt-2">{errors[field]}</p>}
                    </div>
                ))}

                <div className="grid gap-5 border border-line p-5 lg:col-span-2 lg:grid-cols-2">
                    <div className="lg:col-span-2"><Checkbox checked={form.data.hero_carousel_enabled} onChange={(event) => form.setData('hero_carousel_enabled', event.target.checked)}>Promote in the home hero carousel</Checkbox></div>
                    <Field label="Hero order" type="number" min={0} {...text('hero_sort_order')} />
                    <Field label="Hero eyebrow" {...text('hero_eyebrow')} />
                    <Field className="lg:col-span-2" label="Hero tagline" maxLength={220} {...text('hero_tagline')} />
                </div>

                <Field label="Meta title" maxLength={60} {...text('meta_title')} />
                <Field label="Meta description" maxLength={150} {...text('meta_description')} />
                <div className="lg:col-span-2"><Checkbox checked={form.data.kids_discount_eligible} onChange={(event) => form.setData('kids_discount_eligible', event.target.checked)}>Eligible for kids discount</Checkbox></div>

                <div className="lg:col-span-2">
                    <button type="submit" disabled={form.processing} className="btn btn-primary">{form.processing ? 'Saving…' : editing ? 'Update movie' : 'Create movie'}</button>
                </div>
            </form>

            {movie && (
                <div className="border border-signal/40 p-6">
                    <p className="text-sm text-signal">Danger zone: this removes the movie from the active catalogue. It can be restored later.</p>
                    {!confirmDelete ? (
                        <button type="button" className="btn btn-danger btn-sm mt-4" onClick={() => setConfirmDelete(true)}>Remove {movie.title}</button>
                    ) : (
                        <div className="mt-4 flex gap-2">
                            <button type="button" className="btn btn-danger btn-sm" onClick={() => post({ _action: 'delete_movie', movie_id: movie.id })}>Yes, remove it</button>
                            <button type="button" className="btn btn-ghost btn-sm" onClick={() => setConfirmDelete(false)}>Cancel</button>
                        </div>
                    )}
                </div>
            )}
        </>
    );
}

function SettingsPanel({ settings }: { settings: Props['settings'] }) {
    const form = useForm({ _action: 'update_settings', ...settings });
    const bind = (name: keyof Props['settings']) => ({ value: form.data[name], onChange: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => form.setData(name, event.target.value), error: form.errors[name] });

    return (
        <Panel id="settings" label="Website" title="Global settings" description="Core branding, defaults and communications.">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true }); }} className="grid gap-5 lg:grid-cols-2">
                <Field label="Website name" maxLength={60} {...bind('site_name')} />
                <Field label="Canonical base URL" {...bind('canonical_base_url')} />
                <Field className="lg:col-span-2" label="Default meta title" maxLength={60} {...bind('default_meta_title')} />
                <TextArea className="lg:col-span-2" label="Default meta description" rows={3} maxLength={150} {...bind('default_meta_description')} />
                <Field label="Support email" type="email" {...bind('support_email')} />
                <Field label="Home ticker messages" placeholder="Message 1 | Message 2" {...bind('home_ticker_messages')} />
                <div className="lg:col-span-2"><button type="submit" disabled={form.processing} className="btn btn-primary">Save settings</button></div>
            </form>
        </Panel>
    );
}

function SeoPanel({ pages }: { pages: Props['contentPages'] }) {
    const form = useForm({ _action: 'update_pages', pages });
    const update = (index: number, key: string, value: string) => form.setData('pages', form.data.pages.map((page, position) => (position === index ? { ...page, [key]: value } : page)));
    const errors = form.errors as Record<string, string>;

    return (
        <Panel id="seo" label="SEO" title="Page metadata" description="Titles under 60 characters and descriptions under 150 read best in search results.">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true }); }} className="space-y-4">
                {form.data.pages.map((page, index) => (
                    <div key={page.id} className="border border-line p-5">
                        <p className="headline mb-4 text-2xl">{page.slug} page</p>
                        <div className="grid gap-5 lg:grid-cols-2">
                            <Field label="Title" value={page.title} onChange={(event) => update(index, 'title', event.target.value)} error={errors[`pages.${index}.title`]} />
                            <Field label="Canonical path" placeholder="/example" value={page.canonical_path} onChange={(event) => update(index, 'canonical_path', event.target.value)} error={errors[`pages.${index}.canonical_path`]} />
                            <Field label="Meta title" maxLength={60} value={page.meta_title} onChange={(event) => update(index, 'meta_title', event.target.value)} error={errors[`pages.${index}.meta_title`]} aside={<span className="num text-[11px] text-dim">{page.meta_title.length}/60</span>} />
                            <Field label="Meta description" maxLength={150} value={page.meta_description} onChange={(event) => update(index, 'meta_description', event.target.value)} error={errors[`pages.${index}.meta_description`]} aside={<span className="num text-[11px] text-dim">{page.meta_description.length}/150</span>} />
                            <TextArea className="lg:col-span-2" label="Excerpt" rows={2} value={page.excerpt} onChange={(event) => update(index, 'excerpt', event.target.value)} error={errors[`pages.${index}.excerpt`]} />
                        </div>
                    </div>
                ))}
                <button type="submit" disabled={form.processing} className="btn btn-primary">Update SEO data</button>
            </form>
        </Panel>
    );
}

function CouponPanel({ coupons }: { coupons: Row[] }) {
    const form = useForm({ _action: 'store_coupon', code: '', discount: '' });

    return (
        <Panel id="coupons" label="Coupons" title="Discounts" description="Create single-use percentage codes, valid for one month.">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true, onSuccess: () => form.reset('code', 'discount') }); }} className="grid items-end gap-4 border border-line p-5 md:grid-cols-3">
                <Field label="Code" value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase())} error={form.errors.code} inputClassName="num uppercase" />
                <Field label="Discount %" type="number" min={1} max={100} value={form.data.discount} onChange={(event) => form.setData('discount', event.target.value)} error={form.errors.discount} />
                <button type="submit" disabled={form.processing} className="btn btn-primary w-full">Create coupon</button>
            </form>
            <List rows={coupons} />
        </Panel>
    );
}

function MessagePanel() {
    const form = useForm({ _action: 'send_notification', message: '' });

    return (
        <Panel id="messages" label="Messages" title="Broadcast" description="Send an in-app notice to every registered user.">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true, onSuccess: () => form.reset('message') }); }} className="border border-line p-5">
                <TextArea label="Message" rows={4} maxLength={1000} value={form.data.message} onChange={(event) => form.setData('message', event.target.value)} error={form.errors.message} />
                <button type="submit" disabled={form.processing} className="btn btn-primary mt-4">Send notification</button>
            </form>
        </Panel>
    );
}

function ProfilePanel({ admin }: { admin: Props['admin'] }) {
    const form = useForm({ _action: 'update_profile', name: admin.name, email: admin.email, current_password: '', password: '' });

    return (
        <Panel id="profile" label="Profile" title="Administrator" description="Your name, sign-in email and password.">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true, onSuccess: () => form.reset('password', 'current_password') }); }} className="grid gap-5 border border-line p-5 md:grid-cols-2">
                <Field label="Full name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={form.errors.name} />
                <Field label="Email address" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} />
                <Field label="Current password" type="password" placeholder="Needed to change email or password" value={form.data.current_password} onChange={(event) => form.setData('current_password', event.target.value)} error={(form.errors as Record<string, string>).current_password} autoComplete="current-password" />
                <Field label="New password (optional)" type="password" placeholder="10+ chars, upper, lower, number, symbol" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} autoComplete="new-password" />
                <div className="md:col-span-2"><button type="submit" disabled={form.processing} className="btn btn-primary">Save changes</button></div>
            </form>
        </Panel>
    );
}
