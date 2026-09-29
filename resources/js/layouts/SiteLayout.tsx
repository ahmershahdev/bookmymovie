import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Assistant from '@/components/shell/Assistant';
import CompareTray from '@/components/shell/CompareTray';
import Footer from '@/components/shell/Footer';
import Navbar from '@/components/shell/Navbar';
import ProgressBar from '@/components/shell/ProgressBar';
import ScrollIndicator from '@/components/shell/ScrollIndicator';
import ScrollTop from '@/components/shell/ScrollTop';
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
 * survive every visit; only the page inside <main> changes, in place, so
 * moving between pages never looks like a reload.
 */
export default function SiteLayout({ children }: { children: ReactNode }) {
    return (
        <>
            <Meta />
            <ProgressBar />
            <Navbar />
            <main id="main" tabIndex={-1} className="min-h-[70vh] focus:outline-none">
                {/* No blanking re-mount between pages: the new page simply replaces the old one. */}
                {children}
            </main>
            <Footer />
            <CompareTray />
            <div className="fixed bottom-4 right-4 z-[65] flex flex-col items-end gap-3 sm:bottom-6 sm:right-6 rtl:left-4 rtl:right-auto sm:rtl:left-6">
                <ScrollTop />
                <Assistant />
            </div>
            <Toaster />
            <ScrollIndicator />
        </>
    );
}
