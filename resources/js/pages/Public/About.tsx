import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { CountUp, Reveal } from '@/components/motion';
import { PageHeader, SectionHeading } from '@/components/ui';
import { pad, route } from '@/lib/utils';
import type { ContentPage } from './Page';

const engineering = [
    ['Seats that cannot be sold twice', 'Every hold and booking runs inside a database transaction with row locks, and a unique index on live seats is the final referee. Two people tapping the same seat at the same millisecond get one booking and one clear message.'],
    ['Prices you can audit', 'Prices live per show, per row and per ticket type. The seat map, the cart and the e-ticket all read the same numbers from the same place.'],
    ['Security by default', 'Prepared statements everywhere, rate limits on every sensitive route, bot checks on every public form, strict security headers and hashed, rotated credentials.'],
    ['Open to inspection', 'The code is MIT-licensed on GitHub. Cinemas can self-host it; developers can read exactly how a booking is made.'],
];

export default function About({ page, stats }: { page: ContentPage; stats: { films: number; cinemas: number; screens: number; seats: number } }) {
    const [lead, ...rest] = page.paragraphs;

    return (
        <>
            <PageHeader label={page.label ?? 'About us'} title={page.title} lede={page.excerpt} />

            <section className="pt-14">
                <div className="shell">
                    <dl className="grid-lines grid-cols-2 lg:grid-cols-4">
                        {([[stats.films, 'films on the slate'], [stats.cinemas, 'partner cinemas'], [stats.screens, 'screens'], [stats.seats, 'seats on the map']] as [number, string][]).map(([value, label]) => (
                            <div key={label} className="px-5 py-12">
                                <dd><CountUp value={value} className="display block text-8xl tabular" /></dd>
                                <dt className="label mt-3">{label}</dt>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <section className="pt-28">
                <div className="shell grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4"><p className="label label-accent">Our story</p></div>
                    <div className="lg:col-span-8">
                        {lead && <Reveal><p className="text-3xl font-medium leading-[1.3] [font-stretch:90%] sm:text-4xl">{lead}</p></Reveal>}
                        <div className="prose-body mt-8">{rest.map((paragraph, index) => <Reveal key={index}><p>{paragraph}</p></Reveal>)}</div>
                    </div>
                </div>
            </section>

            {page.sections.map((section) => (
                <section key={section.title} className="pt-28">
                    <div className="shell grid gap-12 lg:grid-cols-12">
                        <Reveal className="lg:col-span-4"><h2 className="display text-7xl">{section.title}</h2></Reveal>
                        <div className="lg:col-span-8">
                            {section.body && <Reveal><p className="prose-body">{section.body}</p></Reveal>}
                            {section.items.length > 0 && (
                                <ol className="grid-lines mt-6 sm:grid-cols-2">
                                    {section.items.map((item, index) => (
                                        <Reveal as="li" key={item} delay={index * 80} className="p-7">
                                            <span className="num text-xs text-accent">{pad(index + 1)}</span>
                                            <p className="mt-4 leading-relaxed text-paper-2">{item}</p>
                                        </Reveal>
                                    ))}
                                </ol>
                            )}
                        </div>
                    </div>
                </section>
            ))}

            <section className="pt-32">
                <div className="shell">
                    <SectionHeading label="Under the hood" title="Engineered like a box office not a brochure" accent={['box', 'office']} />
                    <div className="grid-lines mt-14 md:grid-cols-2">
                        {engineering.map(([title, copy], index) => (
                            <Reveal key={title} delay={index * 80} className="p-8 sm:p-10">
                                <span className="display display-outline block text-8xl text-accent">{index + 1}</span>
                                <h3 className="headline mt-8 text-4xl">{title}</h3>
                                <p className="mt-4 leading-relaxed text-mute">{copy}</p>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            <section className="pt-32">
                <div className="shell">
                    <Reveal className="bg-volt p-8 text-noir sm:p-16">
                        <div className="grid gap-10 lg:grid-cols-12 lg:items-end">
                            <div className="lg:col-span-8">
                                <p className="label !text-noir/70">Built in the open</p>
                                <h2 className="display mt-4 text-[clamp(3.5rem,8vw,7rem)] !text-noir">Read the code run your own cinema</h2>
                                <p className="mt-6 max-w-2xl text-noir/75">BookMyMovie is designed and built by Syed Ahmer Shah and published under the MIT licence. Issues, ideas and pull requests are welcome.</p>
                            </div>
                            <div className="flex flex-wrap gap-2 lg:col-span-4 lg:justify-end">
                                <a href="https://github.com/ahmershahdev/bookmymovie" target="_blank" rel="noopener" className="btn bg-ink text-paper hover:text-noir"><Icon name="github" size={18} /> GitHub</a>
                                <Link href={route('contact')} className="btn border-noir/40 text-noir [--btn-wipe:#0a0a0a] hover:text-paper">Get in touch</Link>
                            </div>
                        </div>
                    </Reveal>
                </div>
            </section>
        </>
    );
}
