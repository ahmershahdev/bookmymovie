import { motion } from 'motion/react';
import Avatar from '@/components/Avatar';
import Icon from '@/components/Icon';
import { ReviewCard, type ReviewItem } from '@/components/Reviews';
import { Breadcrumbs } from '@/components/ui';

interface Props {
    member: { name: string; username: string; avatar: string | null; city: string | null; bio: string | null; joined: string | null; reviews: number; verified: number; average: number | null; helpful: number };
    reviews: (ReviewItem & { film: string | null; film_slug: string | null; poster: string | null })[];
}

const ease = [0.16, 1, 0.3, 1] as const;

export default function Profile({ member, reviews }: Props) {
    const stats: [string, string | number][] = [
        ['Reviews', member.reviews],
        ['Verified', member.verified],
        ['Average', member.average !== null ? `${member.average.toFixed(1)}★` : '—'],
        ['Found helpful', member.helpful],
    ];

    return (
        <>
            <section className="relative overflow-hidden pb-16 pt-[calc(var(--header)+2.5rem)]">
                <div className="absolute inset-0 -z-10 bg-[radial-gradient(50%_60%_at_15%_20%,color-mix(in_oklab,var(--color-volt)_12%,transparent),transparent_70%)]" aria-hidden="true" />
                <div className="shell">
                    <Breadcrumbs className="mb-10" />
                    <div className="grid items-end gap-10 lg:grid-cols-12">
                        <motion.div className="flex items-center gap-6 lg:col-span-7" initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, ease }}>
                            <Avatar name={member.name} seed={member.username} src={member.avatar} size={112} className="shadow-2xl shadow-black/40" />
                            <div className="min-w-0">
                                <p className="num text-sm text-accent">@{member.username}</p>
                                <h1 className="display mt-2 text-[clamp(3rem,7vw,6rem)]">{member.name}</h1>
                                <p className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-mute">
                                    {member.city && <span className="flex items-center gap-1.5"><Icon name="pin" size={14} /> {member.city}</span>}
                                    {member.joined && <span className="flex items-center gap-1.5"><Icon name="calendar" size={14} /> Member since {member.joined}</span>}
                                </p>
                            </div>
                        </motion.div>
                        <motion.dl className="grid-lines grid-cols-2 sm:grid-cols-4 lg:col-span-5" initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.3 }}>
                            {stats.map(([label, value]) => (
                                <div key={label} className="p-5"><dt className="label">{label}</dt><dd className="display mt-2 text-4xl">{value}</dd></div>
                            ))}
                        </motion.dl>
                    </div>
                    {member.bio && <p className="lede mt-10 max-w-2xl">“{member.bio}”</p>}
                </div>
            </section>

            <section className="shell" aria-labelledby="member-reviews">
                <h2 id="member-reviews" className="label label-accent">Reviews by {member.name.split(' ')[0]}</h2>
                <div className="mt-4 max-w-4xl">
                    {reviews.map((review) => (
                        <ReviewCard key={review.id} review={review} film={review.film && review.film_slug ? { title: review.film, slug: review.film_slug } : undefined} />
                    ))}
                    {reviews.length === 0 && <p className="panel p-10 text-center text-mute">No reviews yet.</p>}
                </div>
            </section>
        </>
    );
}
