import { Head, Link } from '@inertiajs/react';
import { SplitHeading } from '@/components/motion';
import { route } from '@/lib/utils';

const copy: Record<number, [string, string]> = {
    403: ['This door is staff only', 'You do not have permission to open this page. If you think that is a mistake, sign in with the right account or contact support.'],
    404: ['This reel is missing', 'The page you asked for has moved, ended its run or never existed. The films that are playing are all one click away.'],
    429: ['Easy there, speed reader', 'We received a lot of requests from your connection in a short time, so we paused things to keep the box office fast for everyone. Please wait a minute and try again.'],
    500: ['The projector jammed', 'Something went wrong on our side. The team has been notified automatically. Please try again in a minute.'],
    503: ['Changing the reels', 'BookMyMovie is down for a short maintenance break. Your bookings are safe. Please check back in a few minutes.'],
};

export default function ErrorPage({ status }: { status: number }) {
    const [title, message] = copy[status] ?? copy[500];

    return (
        <>
            <Head title={`${status} · ${title}`} />
            <section className="relative flex min-h-[80svh] flex-col justify-center overflow-hidden pt-[var(--header)]">
                <p className="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 select-none text-center text-[42vw] font-black leading-none text-paper/[.04] [font-stretch:62%]" aria-hidden="true">{status}</p>
                <div className="shell relative">
                    <p className="label label-accent">Error {status}</p>
                    <SplitHeading as="h1" text={title} className="mt-5 max-w-5xl text-[clamp(4rem,11vw,10rem)]" />
                    <p className="lede mt-6 max-w-xl">{message}</p>
                    <div className="mt-10 flex flex-wrap gap-2">
                        <Link href={route('home')} className="btn btn-primary">Back to home</Link>
                        <Link href={route('movies.index')} className="btn btn-ghost">Browse films</Link>
                    </div>
                </div>
            </section>
        </>
    );
}
