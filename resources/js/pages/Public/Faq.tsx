import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Accordion from '@/components/Accordion';
import Icon from '@/components/Icon';
import { PageHeader } from '@/components/ui';
import { cn, route, useShared } from '@/lib/utils';

type Faq = { id: number; category: string; question: string; answer: string };

export default function FaqPage({ faqs }: { faqs: Faq[] }) {
    const { site } = useShared();
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState('All');
    const [open, setOpen] = useState<number | null>(null);

    const groups = useMemo(() => {
        const map = new Map<string, Faq[]>();
        faqs.forEach((faq) => map.set(faq.category, [...(map.get(faq.category) ?? []), faq]));
        return map;
    }, [faqs]);

    const matches = (faq: Faq) => {
        const q = query.toLowerCase().trim();
        return (category === 'All' || faq.category === category) && (!q || faq.question.toLowerCase().includes(q) || faq.answer.toLowerCase().includes(q));
    };
    const count = faqs.filter(matches).length;

    return (
        <>
            <PageHeader label="Help centre" title="How can we help" accent={['help']}
                lede="Everything about booking, paying, cancelling and getting to your seat. If the answer is not here, a real person will reply to you.">
                <label className="relative mt-12 block max-w-3xl">
                    <span className="sr-only">Search help articles</span>
                    <Icon name="search" size={22} className="pointer-events-none absolute left-5 top-1/2 -translate-y-1/2 text-accent" />
                    <input type="search" value={query} onChange={(event) => { setQuery(event.target.value); setOpen(null); }} placeholder="Try “cancel”, “child ticket” or “IMAX”" className="input !h-16 !pl-14 !text-lg" />
                </label>
            </PageHeader>

            <section className="pt-16">
                <div className="shell grid grid-cols-1 gap-14 lg:grid-cols-12">
                    <aside className="min-w-0 lg:col-span-3">
                        <nav className="no-scrollbar sticky top-24 flex gap-1 overflow-x-auto lg:flex-col" aria-label="Help topics">
                            {[['All', faqs.length] as const, ...[...groups.entries()].map(([name, items]) => [name, items.length] as const)].map(([name, total]) => (
                                <button key={name} type="button" onClick={() => { setCategory(name); setOpen(null); }} aria-pressed={category === name}
                                    className={cn('flex shrink-0 items-center justify-between gap-6 px-4 py-3 text-left text-sm transition', category === name ? 'bg-volt text-noir' : 'text-mute hover:bg-ink-3 hover:text-paper')}>
                                    {name} <span className="num text-[11px] opacity-60">{total}</span>
                                </button>
                            ))}
                        </nav>
                    </aside>

                    <div className="min-w-0 lg:col-span-9">
                        <p className="mb-6 text-sm text-mute" aria-live="polite"><span className="num">{count}</span> answers{query && <> for “<span className="text-paper">{query}</span>”</>}</p>
                        {[...groups.entries()].map(([name, items]) => {
                            const visible = items.filter(matches);
                            if (!visible.length) return null;
                            return (
                                <div key={name} className="mb-14">
                                    <h2 className="label label-accent mb-4">{name}</h2>
                                    <div className="border-t border-line">
                                        {visible.map((faq) => <Accordion key={faq.id} id={faq.id} question={faq.question} answer={faq.answer} open={open === faq.id} onToggle={() => setOpen(open === faq.id ? null : faq.id)} />)}
                                    </div>
                                </div>
                            );
                        })}
                        {count === 0 && (
                            <div className="panel p-12 text-center">
                                <p className="headline text-4xl">No answer for that yet</p>
                                <p className="mt-2 text-mute">Send us your question and we will add it here.</p>
                                <Link href={route('contact')} className="btn btn-primary mt-6">Ask support</Link>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            <section className="pt-24">
                <div className="shell grid-lines md:grid-cols-3">
                    {[
                        ['E-tickets', 'What is on your ticket and how entry works.', route('eticket.info'), 'ticket'],
                        ['Cancellations', 'Cancel free until two hours before the show.', route('refund'), 'refresh'],
                        ['Contact support', `Real people, ${site.response_sla.toLowerCase()}.`, route('contact'), 'mail'],
                    ].map(([title, copy, href, icon]) => (
                        <Link key={title} href={href} className="group min-w-0 p-6 transition-colors sm:p-8 hover:!bg-volt hover:text-noir">
                            <Icon name={icon} size={24} className="text-accent group-hover:text-noir" />
                            <span className="headline mt-8 block text-4xl">{title}</span>
                            <span className="mt-2 block text-sm text-mute group-hover:text-noir/70">{copy}</span>
                            <span className="mt-6 inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[.08em] [font-stretch:115%]">Open <Icon name="arrow-right" size={14} className="transition group-hover:translate-x-1" /></span>
                        </Link>
                    ))}
                </div>
            </section>
        </>
    );
}
