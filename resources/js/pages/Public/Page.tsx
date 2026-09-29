import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { PageHeader } from '@/components/ui';
import { scrollToTarget } from '@/lib/scroll';
import { cn, pad, route, useShared } from '@/lib/utils';

export interface ContentPage {
    slug: string;
    title: string;
    label: string | null;
    excerpt: string | null;
    paragraphs: string[];
    sections: { title: string; body: string | null; items: string[] }[];
    updated: string | null;
    minutes: number;
}

export default function Page({ page, related }: { page: ContentPage; related: { title: string; label: string | null; url: string }[] }) {
    const { site } = useShared();
    const hasToc = page.sections.length > 1;

    return (
        <>
            <PageHeader label={page.label} title={page.title} lede={page.excerpt}>
                <p className="label mt-10">Last updated <span className="num">{page.updated}</span> · {page.minutes} min read</p>
            </PageHeader>

            <div className="shell grid gap-16 pt-16 lg:grid-cols-12">
                {hasToc && (
                    <aside className="hidden lg:col-span-3 lg:block">
                        <nav className="sticky top-24" aria-label="On this page">
                            <p className="label">On this page</p>
                            <ol className="mt-5 space-y-3 border-l border-line text-sm">
                                {page.sections.map((section, index) => (
                                    <li key={section.title}>
                                        <button type="button" onClick={() => scrollToTarget(`#section-${index + 1}`)} className="-ml-px block border-l-2 border-transparent pl-4 text-left text-mute transition hover:border-accent hover:text-paper">
                                            {section.title}
                                        </button>
                                    </li>
                                ))}
                            </ol>
                        </nav>
                    </aside>
                )}

                <article className={cn(hasToc ? 'lg:col-span-8 lg:col-start-5' : 'lg:col-span-8 lg:col-start-3')}>
                    {page.paragraphs.length > 0 && <div className="prose-body">{page.paragraphs.map((paragraph, index) => <p key={index}>{paragraph}</p>)}</div>}

                    {page.sections.map((section, index) => (
                        <section key={section.title} id={`section-${index + 1}`} className={cn('scroll-mt-24 border-t border-line pt-10', (index > 0 || page.paragraphs.length > 0) && 'mt-14')}>
                            <div className="flex items-baseline gap-5">
                                <span className="num text-sm text-accent">{pad(index + 1)}</span>
                                <h2 className="headline text-4xl sm:text-5xl">{section.title}</h2>
                            </div>
                            {section.body && <p className="prose-body mt-5 sm:pl-10">{section.body}</p>}
                            {section.items.length > 0 && (
                                <ul className="mt-6 space-y-4 sm:pl-10">
                                    {section.items.map((item) => (
                                        <li key={item} className="flex gap-4 text-paper-2"><span className="mt-2.5 h-1.5 w-1.5 shrink-0 bg-volt" aria-hidden="true" /><span className="leading-relaxed">{item}</span></li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    ))}

                    <div className="panel mt-20 flex flex-col items-start justify-between gap-6 p-8 sm:flex-row sm:items-center">
                        <div>
                            <p className="headline text-3xl">Still have a question?</p>
                            <p className="mt-1 text-sm text-mute">Our support team replies {site.response_sla.toLowerCase()}.</p>
                        </div>
                        <Link href={route('contact')} className="btn btn-light shrink-0">Contact support <Icon name="arrow-right" size={16} className="arrow" /></Link>
                    </div>

                    {related.length > 0 && (
                        <nav className="mt-16" aria-label="Related pages">
                            <p className="label">Related</p>
                            <ul className="grid-lines grid-fill-odd mt-4 sm:grid-cols-2">
                                {related.map((item) => (
                                    <li key={item.url}>
                                        <Link href={item.url} className="group flex items-center justify-between px-5 py-4 transition-colors hover:bg-volt hover:text-noir">
                                            <span><span className="label block text-[9px] group-hover:text-noir/60">{item.label}</span><span>{item.title}</span></span>
                                            <Icon name="arrow-right" size={16} className="transition group-hover:translate-x-1" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}
                </article>
            </div>
        </>
    );
}
