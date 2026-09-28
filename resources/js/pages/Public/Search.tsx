import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import MovieCard from '@/components/MovieCard';
import { PageHeader } from '@/components/ui';
import { plural, route } from '@/lib/utils';
import type { MovieCard as Movie } from '@/types';

interface Props {
    query: string;
    results: Movie[];
    cinemas: { slug: string; name: string; city: string; screens: number }[];
    people: { name: string; initials: string; known_for: string | null; movies: { title: string; slug: string }[] }[];
    suggestions: { title: string; slug: string }[];
}

export default function Search({ query, results, cinemas, people, suggestions }: Props) {
    const [value, setValue] = useState(query);
    const total = results.length + cinemas.length + people.length;

    const Suggestions = () => (
        <div className="flex flex-wrap gap-2">
            {suggestions.map((movie) => <Link key={movie.slug} href={route('movies.show', movie.slug)} className="chip">{movie.title}</Link>)}
        </div>
    );

    return (
        <>
            <PageHeader label="Search" title={query ? `“${query}”` : 'Find your next film'} accent={['next']}
                lede={query ? `${plural(total, 'result')} across films, cinemas and people.` : 'Search by title, actor, director, genre or cinema.'}>
                <form role="search" className="relative mt-12 max-w-3xl" onSubmit={(event) => { event.preventDefault(); router.visit(value.trim() ? route('search', { query: value.trim() }) : route('search')); }}>
                    <label htmlFor="q" className="sr-only">Search</label>
                    <Icon name="search" size={22} className="pointer-events-none absolute left-5 top-1/2 -translate-y-1/2 text-accent" />
                    <input id="q" type="search" value={value} onChange={(event) => setValue(event.target.value)} maxLength={120} autoFocus
                        placeholder="Try “Kestrel”, “Nadia Qureshi” or “Lahore”" className="input !h-16 !pl-14 !pr-32 !text-lg" />
                    <button className="btn btn-primary btn-sm absolute right-2 top-1/2 -translate-y-1/2">Search</button>
                </form>
            </PageHeader>

            <div className="shell pt-16">
                {!query && (
                    <>
                        <p className="label">Popular right now</p>
                        <div className="mt-5"><Suggestions /></div>
                    </>
                )}
                {query && total === 0 && (
                    <div className="panel px-6 py-20 text-center">
                        <Icon name="search" size={36} className="mx-auto text-dim" />
                        <h2 className="display mt-6 text-6xl">No matches</h2>
                        <p className="lede mx-auto mt-3 max-w-md">Check the spelling or try a broader word. These films are popular right now:</p>
                        <div className="mt-8 flex justify-center"><Suggestions /></div>
                    </div>
                )}

                {results.length > 0 && (
                    <section aria-labelledby="film-results">
                        <h2 id="film-results" className="label label-accent">Films · {results.length}</h2>
                        <div className="mt-8 grid grid-cols-2 gap-x-4 gap-y-14 sm:grid-cols-3 lg:grid-cols-4">
                            {results.map((movie) => <MovieCard key={movie.id} movie={movie} />)}
                        </div>
                    </section>
                )}

                {cinemas.length > 0 && (
                    <section className="pt-20" aria-labelledby="cinema-results">
                        <h2 id="cinema-results" className="label label-accent">Cinemas · {cinemas.length}</h2>
                        <div className="grid-lines mt-6 md:grid-cols-3">
                            {cinemas.map((cinema) => (
                                <Link key={cinema.slug} href={route('cinemas.show', cinema.slug)} className="group p-6 transition-colors hover:!bg-volt hover:text-noir">
                                    <span className="headline block text-3xl">{cinema.name}</span>
                                    <span className="mt-1 block text-sm text-mute group-hover:text-noir/70">{cinema.city} · {cinema.screens} screens</span>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                {people.length > 0 && (
                    <section className="pt-20" aria-labelledby="people-results">
                        <h2 id="people-results" className="label label-accent">Cast & crew · {people.length}</h2>
                        <ul className="grid-lines mt-6 md:grid-cols-2">
                            {people.map((person) => (
                                <li key={person.name} className="flex gap-5 p-6">
                                    <span className="grid h-14 w-14 shrink-0 place-items-center bg-ink-4 text-2xl font-black uppercase [font-stretch:62%]">{person.initials}</span>
                                    <div>
                                        <p className="headline text-3xl">{person.name}</p>
                                        {person.known_for && <p className="text-sm text-mute">{person.known_for}</p>}
                                        <p className="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm">
                                            {person.movies.map((movie) => <Link key={movie.slug} href={route('movies.show', movie.slug)} className="link text-paper-2 hover:text-paper">{movie.title}</Link>)}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
