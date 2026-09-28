import { Head, usePage } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import type { ReactNode } from 'react';
import CompareTray from '@/components/shell/CompareTray';
import Footer from '@/components/shell/Footer';
import Navbar from '@/components/shell/Navbar';
import ProgressBar from '@/components/shell/ProgressBar';
import ScrollIndicator from '@/components/shell/ScrollIndicator';
import Toaster from '@/components/shell/Toaster';
import { useShared } from '@/lib/utils';

/** Page title and description, updated on every client-side visit. */
export function Meta() {
    const { meta } = useShared();
    if (!meta) return null;

    return (
        <Head title={meta.title}>
            <meta head-key="description" name="description" content={meta.description} />
        </Head>
    );
}

/**
 * Persistent shell: the navbar, footer, scrollbar and overlays mount once and
 * survive every visit; only the page inside <main> changes, sliding up
 * into place so a new page feels like the next reel rather than a reload.
 */
export default function SiteLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();
    const reduce = useReducedMotion();
    const path = url.split('?')[0].split('#')[0];

    return (
        <>
            <Meta />
            <ProgressBar />
            <Navbar />
            <main id="main" tabIndex={-1} className="min-h-[70vh] focus:outline-none">
                <motion.div key={path} initial={reduce ? false : { opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7, ease: [0.16, 1, 0.3, 1] }}>
                    {children}
                </motion.div>
            </main>
            <Footer />
            <CompareTray />
            <Toaster />
            <ScrollIndicator />
        </>
    );
}
