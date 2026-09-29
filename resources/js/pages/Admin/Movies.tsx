import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import { Checkbox, Field, Select, TextArea } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, route } from '@/lib/utils';

type Row = { label: string; value: string };
type MovieData = {
    id: number; title: string; slug: string; language: string; duration_minutes: number; certificate_rating: string; status: string; release_date: string;
    genre_ids: number[]; base_price: string; sale_price: string; tagline: string; rating_mode: string; fake_average_rating: string; fake_total_reviews: string;
    description: string; hero_carousel_enabled: boolean; hero_sort_order: string; hero_eyebrow: string; hero_tagline: string; meta_title: string; meta_description: string;
    kids_discount_eligible: boolean; poster_url: string | null; hero_url: string | null;
};

interface Props {
    selectedMovie: MovieData | null;
    movieOptions: { id: number; label: string }[];
    genres: { id: number; name: string }[];
    recentMovies: Row[];
    trashedMovies: { id: number; title: string }[];
    schedule: Row[];
    selectedMovieShows: { id: number; venue: string; when: string; available: number; booked: number }[];
    rowPriceBenefits: { row: string; tier: string; price: string; benefits: string }[];
    certificates: string[];
    today: string;
}

type Tab = 'edit' | 'add' | 'shows' | 'removed';

export default function AdminMovies(props: Props) {
    const [tab, setTab] = useState<Tab>(props.selectedMovie ? 'edit' : 'add');
    const [query, setQuery] = useState('');
    const films = useMemo(() => props.movieOptions.filter((option) => option.label.toLowerCase().includes(query.toLowerCase())), [props.movieOptions, query]);
    const post = (data: Record<string, unknown>) => router.post(route('admin.dashboard'), data as never, { preserveScroll: true });

    const tabs: [Tab, string, string, number?][] = [
        ['edit', 'Edit film', 'film'],
        ['add', 'Add a film', 'plus'],
        ['shows', 'Showtimes & prices', 'calendar', props.selectedMovieShows.length],
        ['removed', 'Removed', 'trash', props.trashedMovies.length],
    ];

    return (
        <AdminLayout label="Catalogue" title="Movies & shows" lede="Add films, change their details and artwork, choose which ones headline the home page, and check upcoming showtimes."
            guide={
                <Guide id="movies" steps={[
                    ['Pick a film', 'Search the list on the left and click a title. Its details load into the editor.'],
                    ['Edit section by section', 'Basics, price, artwork, home carousel and search. Each section says what it changes on the site.'],
                    ['Save once', 'Press “Update film” at the bottom. Nothing goes live until you save.'],
                    ['Removing is safe', 'Removed films go to the Removed tab and can be restored with one click, bookings intact.'],
                ]} tip="Artwork is converted to fast WebP automatically. Use a 4:3 image for cards and a 16:9 image for the carousel." />
            }>
            <div className="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
                <aside className="border border-line bg-ink-2 xl:sticky xl:top-6 xl:self-start">
                    <div className="border-b border-line p-4">
                        <label className="relative block">
                            <span className="sr-only">Search films</span>
                            <Icon name="search" size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-mute" />
                            <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={`Search ${props.movieOptions.length} films`} className="input !pl-9" />
                        </label>
                    </div>
                    <ul className="max-h-[26rem] overflow-y-auto xl:max-h-[calc(100vh-12rem)]" data-lenis-prevent>
                        {films.map((film) => {
                            const [title, placement] = film.label.split(' — ');
                            const active = props.selectedMovie?.id === film.id;
                            return (
                                <li key={film.id}>
                                    <button type="button" onClick={() => { setTab('edit'); router.visit(route('admin.movies', film.id), { preserveScroll: true }); }} aria-current={active || undefined}
                                        className={cn('flex w-full items-center justify-between gap-3 border-b border-line px-4 py-3 text-left transition', active ? 'bg-volt text-noir' : 'hover:bg-ink-3')}>
                                        <span className="truncate text-sm font-semibold">{title}</span>
                                        {placement === 'Hero' && <span className={cn('label shrink-0', active ? 'text-noir' : 'text-accent')}>Hero</span>}
                                    </button>
                                </li>
                            );
                        })}
                        {films.length === 0 && <li className="p-6 text-center text-sm text-mute">No film matches “{query}”.</li>}
                    </ul>
                    <div className="p-3"><button type="button" onClick={() => setTab('add')} className="btn btn-primary btn-sm w-full"><Icon name="plus" size={14} /> Add a film</button></div>
                </aside>

                <div className="min-w-0 space-y-6">
                    <div className="no-scrollbar flex gap-1 overflow-x-auto border-b border-line" role="tablist">
                        {tabs.map(([key, label, icon, count]) => (
                            <button key={key} type="button" role="tab" aria-selected={tab === key} onClick={() => setTab(key)}
                                className={cn('-mb-px flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-[11px] font-semibold uppercase tracking-[.08em] transition [font-stretch:115%]',
                                    tab === key ? 'border-accent text-paper' : 'border-transparent text-mute hover:text-paper')}>
                                <Icon name={icon} size={14} /> {label}{count ? <span className="num bg-ink-4 px-1.5 text-[10px]">{count}</span> : null}
                            </button>
                        ))}
                    </div>

                    {tab === 'edit' && (props.selectedMovie
                        ? <MovieEditor key={props.selectedMovie.id} movie={props.selectedMovie} {...props} onRemove={(id) => post({ _action: 'delete_movie', movie_id: id })} />
                        : <Empty icon="film" title="No films yet">Use “Add a film” to create the first one.</Empty>)}

                    {tab === 'add' && <MovieEditor movie={null} {...props} />}

                    {tab === 'shows' && <Shows {...props} />}

                    {tab === 'removed' && (props.trashedMovies.length ? (
                        <div className="grid gap-2 md:grid-cols-2">
                            {props.trashedMovies.map((movie) => (
                                <div key={movie.id} className="flex items-center justify-between gap-4 border border-line bg-ink-2 p-4">
                                    <span className="text-sm">{movie.title}</span>
                                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => post({ _action: 'restore_movie', movie_id: movie.id })}><Icon name="refresh" size={13} /> Restore</button>
                                </div>
                            ))}
                        </div>
                    ) : <Empty icon="trash" title="Nothing removed">Films you remove appear here, ready to restore.</Empty>)}
                </div>
            </div>
        </AdminLayout>
    );
}

AdminMovies.layout = null;

function Empty({ icon, title, children }: { icon: string; title: string; children: ReactNode }) {
    return (
        <div className="grid place-items-center gap-2 border border-dashed border-line-2 px-6 py-14 text-center">
            <Icon name={icon} size={24} className="text-mute" />
            <p className="font-semibold">{title}</p>
            <p className="text-sm text-mute">{children}</p>
        </div>
    );
}

function Section({ step, title, help, children }: { step: number; title: string; help: string; children: ReactNode }) {
    return (
        <fieldset className="border border-line bg-ink-2 p-5 sm:p-6">
            <legend className="sr-only">{title}</legend>
            <div className="mb-5 flex gap-4">
                <span className="num grid h-8 w-8 shrink-0 place-items-center bg-volt text-sm font-semibold text-noir">{step}</span>
                <div><h3 className="headline text-2xl">{title}</h3><p className="mt-1 text-xs text-mute">{help}</p></div>
            </div>
            <div className="grid gap-5 lg:grid-cols-2">{children}</div>
        </fieldset>
    );
}

function MovieEditor({ movie, genres, certificates, today, onRemove }: { movie: MovieData | null; onRemove?: (id: number) => void } & Pick<Props, 'genres' | 'certificates' | 'today'>) {
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
        <form onSubmit={submit} className="space-y-4">
            {movie && (
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="display text-5xl">{movie.title}</h2>
                    <a href={route('movies.show', movie.slug)} target="_blank" rel="noreferrer" className="btn btn-ghost btn-sm">See it live <Icon name="arrow-up-right" size={13} /></a>
                </div>
            )}

            <Section step={1} title="Basics" help="What customers see on the film page and in search. Status controls whether it can be booked.">
                <Field label="Title" placeholder="e.g. Brass Monkeys" {...text('title')} required />
                <Field label="Web address (slug)" placeholder="e.g. brass-monkeys (optional)" {...text('slug')} hint="Leave empty and it is made from the title, e.g. dune-part-two." />
                <Field label="Language" placeholder="e.g. English" {...text('language')} />
                <Field label="Running time (minutes)" placeholder="e.g. 118" type="number" min={1} {...text('duration_minutes')} />
                <Select label="Certificate" {...text('certificate_rating')} options={certificates.map((value) => [value, value] as [string, string])} />
                <Select label="Status" {...text('status')} hint="“Now showing” films can be booked; “Coming soon” films show a waitlist." options={[['coming_soon', 'Coming soon'], ['now_showing', 'Now showing'], ['ended', 'Ended']]} />
                <Field label="Release date" type="date" {...text('release_date')} />
                <div className="field">
                    <span className="field-label">Genres <span className="font-normal normal-case tracking-normal text-dim">(click to toggle)</span></span>
                    <div className="flex flex-wrap gap-1.5">
                        {genres.map((genre) => {
                            const on = form.data.genre_ids.includes(genre.id);
                            return (
                                <button key={genre.id} type="button" aria-pressed={on} className="chip"
                                    onClick={() => form.setData('genre_ids', on ? form.data.genre_ids.filter((id) => id !== genre.id) : [...form.data.genre_ids, genre.id])}>
                                    {on && <Icon name="check" size={11} />} {genre.name}
                                </button>
                            );
                        })}
                    </div>
                    {errors.genre_ids && <p className="field-error">{errors.genre_ids}</p>}
                </div>
                <Field label="Tagline" placeholder="One line that sells the film" maxLength={180} {...text('tagline')} aside={<span className="num text-[11px] text-dim">{form.data.tagline.length}/180</span>} />
                <TextArea className="lg:col-span-2" label="Story" placeholder="Two or three sentences about the plot, no spoilers" rows={4} {...text('description')} hint="Two or three sentences, no spoilers." />
            </Section>

            <Section step={2} title="Price & rating" help="The base price is the starting ticket price. A sale price must be lower and shows as a discount.">
                <Field label="Base price (PKR)" placeholder="e.g. 1200" type="number" step="0.01" {...text('base_price')} />
                <Field label="Sale price (PKR)" placeholder="Leave empty for no sale" type="number" step="0.01" {...text('sale_price')} hint="Optional. Leave empty for no sale." />
                <Select label="Star rating shown" {...text('rating_mode')} options={[['real', 'Average of real reviews'], ['fake', 'Set it by hand']]} hint="“By hand” is for launch week, before real reviews arrive." />
                <div className={cn('grid grid-cols-2 gap-4', form.data.rating_mode !== 'fake' && 'pointer-events-none opacity-40')}>
                    <Field label="Stars (1-5)" placeholder="e.g. 4.2" type="number" min={1} max={5} step="0.1" {...text('fake_average_rating')} />
                    <Field label="Review count" placeholder="e.g. 36" type="number" min={0} {...text('fake_total_reviews')} />
                </div>
                <div className="lg:col-span-2"><Checkbox checked={form.data.kids_discount_eligible} onChange={(event) => form.setData('kids_discount_eligible', event.target.checked)}>Child tickets get the kids discount for this film</Checkbox></div>
            </Section>

            <Section step={3} title="Artwork" help="PNG, JPG or WebP up to 3 MB. Saved as optimised WebP. You will see a preview before saving.">
                <Upload label="Card image" ratio="4:3 landscape" usage="Film cards, search, watchlist and the film page." current={movie?.poster_url ?? null} file={form.data.poster_upload}
                    aspect="aspect-[4/3]" error={errors.poster_upload} onPick={(file) => form.setData('poster_upload', file)} />
                <Upload label="Carousel image" ratio="16:9 widescreen" usage="Home page carousel and the film page backdrop." current={movie?.hero_url ?? null} file={form.data.hero_upload}
                    aspect="aspect-video" error={errors.hero_upload} onPick={(file) => form.setData('hero_upload', file)} />
            </Section>

            <Section step={4} title="Home page carousel" help="Promoted films rotate in the big banner at the top of the home page, lowest order number first.">
                <div className="lg:col-span-2"><Checkbox checked={form.data.hero_carousel_enabled} onChange={(event) => form.setData('hero_carousel_enabled', event.target.checked)}>Show this film in the home page carousel</Checkbox></div>
                <div className={cn('contents', !form.data.hero_carousel_enabled && '[&>*]:opacity-40')}>
                    <Field label="Order" placeholder="0 = first" type="number" min={0} {...text('hero_sort_order')} hint="0 comes first." />
                    <Field label="Small line above the title" placeholder="e.g. In IMAX this Friday" {...text('hero_eyebrow')} />
                    <Field className="lg:col-span-2" label="Carousel tagline" placeholder="e.g. The heist of the year, now in IMAX" maxLength={220} {...text('hero_tagline')} />
                </div>
            </Section>

            <Section step={5} title="Search engines" help="What Google shows for this film. Leave empty to use the title and story automatically.">
                <Field label="Search title" placeholder="Leave empty to use the film title" maxLength={60} {...text('meta_title')} aside={<span className="num text-[11px] text-dim">{form.data.meta_title.length}/60</span>} />
                <Field label="Search description" placeholder="Leave empty to use the story" maxLength={150} {...text('meta_description')} aside={<span className="num text-[11px] text-dim">{form.data.meta_description.length}/150</span>} />
                <div className="lg:col-span-2 border border-line bg-ink p-4" aria-label="Search result preview">
                    <p className="label mb-2">Preview</p>
                    <p className="truncate text-base text-[#8ab4f8]">{form.data.meta_title || form.data.title || 'Film title'} | BookMyMovie</p>
                    <p className="num truncate text-xs text-mint">bookmymovie.ahmershah.dev/movies/{form.data.slug || 'your-film'}</p>
                    <p className="mt-1 line-clamp-2 text-sm text-paper-2">{form.data.meta_description || form.data.description || 'The story summary appears here.'}</p>
                </div>
            </Section>

            <div className="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 border border-line-2 bg-ink-2/95 p-4 shadow-2xl backdrop-blur">
                <p className="text-xs text-mute">{form.isDirty ? <span className="text-accent">You have unsaved changes.</span> : 'All changes saved.'}</p>
                <div className="flex gap-2">
                    {form.isDirty && <button type="button" onClick={() => form.reset()} className="btn btn-ghost btn-sm">Undo changes</button>}
                    <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary">{form.processing ? 'Saving…' : editing ? 'Update film' : 'Create film'}</button>
                </div>
            </div>

            {movie && onRemove && (
                <div className="border border-signal/40 p-5">
                    <p className="text-sm font-semibold text-signal">Remove this film</p>
                    <p className="mt-1 text-xs text-mute">It disappears from the site straight away. Bookings are kept, and you can restore it from the Removed tab.</p>
                    {!confirmDelete ? (
                        <button type="button" className="btn btn-danger btn-sm mt-4" onClick={() => setConfirmDelete(true)}>Remove {movie.title}</button>
                    ) : (
                        <div className="mt-4 flex gap-2">
                            <button type="button" className="btn btn-danger btn-sm" onClick={() => onRemove(movie.id)}>Yes, remove it</button>
                            <button type="button" className="btn btn-ghost btn-sm" onClick={() => setConfirmDelete(false)}>Keep it</button>
                        </div>
                    )}
                </div>
            )}
        </form>
    );
}

function Upload({ label, ratio, usage, current, file, aspect, error, onPick }: {
    label: string; ratio: string; usage: string; current: string | null; file: File | null; aspect: string; error?: string; onPick: (file: File | null) => void;
}) {
    const [preview, setPreview] = useState<string | null>(null);
    useEffect(() => {
        if (!file) { setPreview(null); return; }
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);
    const shown = preview ?? current;

    return (
        <div>
            <p className="field-label">{label} · <span className="text-accent">{ratio}</span></p>
            <p className="mt-1 text-xs text-mute">{usage}</p>
            <label className={cn('group relative mt-3 grid cursor-pointer place-items-center overflow-hidden border border-dashed border-line-2 bg-ink transition hover:border-accent', aspect)}>
                {shown ? <img src={shown} alt={`${label} preview`} className="absolute inset-0 h-full w-full object-cover" /> : null}
                <span className={cn('relative z-10 flex flex-col items-center gap-1 p-3 text-center text-xs', shown && 'bg-ink/80 opacity-0 transition group-hover:opacity-100')}>
                    <Icon name="plus" size={18} className="text-accent" />
                    {shown ? 'Replace image' : 'Choose an image'}
                </span>
                <input type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" aria-label={label} onChange={(event) => onPick(event.target.files?.[0] ?? null)} />
            </label>
            {file && <p className="mt-2 flex items-center justify-between gap-2 text-xs"><span className="truncate text-mint">New: {file.name}</span><button type="button" className="link" onClick={() => onPick(null)}>Cancel</button></p>}
            {error && <p className="field-error mt-2">{error}</p>}
        </div>
    );
}

function Shows({ selectedMovie, selectedMovieShows, rowPriceBenefits, schedule }: Props) {
    return (
        <div className="space-y-6">
            <div className="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
                <section className="border border-line bg-ink-2 p-5 sm:p-6">
                    <h3 className="headline text-2xl">{selectedMovie?.title ?? 'Selected film'}: upcoming shows</h3>
                    <p className="mt-1 text-xs text-mute">The bar shows how full each show is.</p>
                    <div className="mt-5 grid gap-2 sm:grid-cols-2">
                        {selectedMovieShows.length ? selectedMovieShows.map((show) => {
                            const total = show.available + show.booked;
                            const rate = total ? Math.round((show.booked / total) * 100) : 0;
                            return (
                                <article key={show.id} className="border border-line p-4">
                                    <p className="text-sm font-semibold">{show.venue}</p>
                                    <p className="num mt-1 text-xs text-mute">{show.when}</p>
                                    <div className="mt-3 h-1.5 bg-ink-4"><div className="h-full bg-volt" style={{ width: `${rate}%` }} /></div>
                                    <p className="num mt-2 flex justify-between text-[11px]"><span className="text-mint">{show.available} free</span><span className="text-mute">{show.booked} booked · {rate}%</span></p>
                                </article>
                            );
                        }) : <p className="col-span-full p-6 text-center text-sm text-mute">No upcoming showtimes for this film.</p>}
                    </div>
                </section>
                <section className="border border-line bg-ink-2 p-5 sm:p-6">
                    <h3 className="headline text-2xl">Seat price ladder</h3>
                    <p className="mt-1 text-xs text-mute">Each row of the hall has its own price and perks.</p>
                    <div className="mt-5 space-y-2">
                        {rowPriceBenefits.length ? rowPriceBenefits.map((tier) => (
                            <div key={tier.row} className="border border-line p-3">
                                <div className="flex items-center justify-between gap-4"><p className="text-sm font-semibold">Row {tier.row} · <span className="font-normal text-mute">{tier.tier}</span></p><p className="num text-sm text-accent">{tier.price}</p></div>
                                {tier.benefits && <p className="mt-1.5 text-xs text-mute">{tier.benefits}</p>}
                            </div>
                        )) : <p className="p-6 text-center text-sm text-mute">No row prices yet for this film&rsquo;s shows.</p>}
                    </div>
                </section>
            </div>
            <section className="border border-line bg-ink-2 p-5 sm:p-6">
                <h3 className="headline text-2xl">Next shows across all films</h3>
                <ul className="mt-4 divide-y divide-line border border-line">
                    {schedule.length ? schedule.map((row, index) => (
                        <li key={`${row.label}-${index}`} className="flex flex-col gap-1 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <span className="text-sm">{row.label}</span><span className="num text-xs text-mute">{row.value}</span>
                        </li>
                    )) : <li className="p-8 text-center text-sm text-mute">Nothing scheduled.</li>}
                </ul>
            </section>
        </div>
    );
}
