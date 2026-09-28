import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';
import SiteLayout from '@/layouts/SiteLayout';
import { startSmoothScroll } from '@/lib/scroll';

type PageModule = { default: { layout?: ((page: ReactNode) => ReactNode) | null } };

const appName = 'BookMyMovie';

createInertiaApp({
    title: (title) => title || appName,
    resolve: async (name) => {
        const pages = import.meta.glob<PageModule>('./pages/**/*.tsx');
        const loader = pages[`./pages/${name}.tsx`];
        if (!loader) throw new Error(`Unknown page: ${name}`);
        const page = await loader();
        // Pages opt out of the site chrome by setting `layout = null` or their own layout.
        if (page.default.layout === undefined) {
            page.default.layout = (child: ReactNode) => <SiteLayout>{child}</SiteLayout>;
        }
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
        startSmoothScroll();
    },
    // The progress bar is our own component: Inertia's injects an inline
    // <style> tag, which the Content-Security-Policy blocks.
    progress: false,
});
