import { Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'motion/react';
import Icon from '@/components/Icon';
import { useCompare } from '@/lib/compare';
import { route } from '@/lib/utils';

/** Floating pill that follows you around while films are queued to compare. */
export default function CompareTray() {
    const { list, clear } = useCompare();
    const { component } = usePage();

    return (
        <AnimatePresence>
            {list.length > 0 && component !== 'Public/Compare' && (
                <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 30 }} transition={{ duration: 0.5, ease: [0.16, 1, 0.3, 1] }}
                    className="fixed bottom-4 right-4 z-[60] sm:bottom-6 sm:right-6" data-print-hide>
                    <div className="glass flex items-center gap-3 p-1.5 shadow-2xl shadow-black/60">
                        <div className="flex -space-x-2 pl-1">
                            {list.map((movie) => (
                                <span key={movie.id} className="grid h-9 w-9 place-items-center border-2 border-ink text-sm font-extrabold uppercase text-noir [font-stretch:75%]"
                                    style={{ background: movie.palette?.[1] ?? '#e3ff3b' }}>
                                    {movie.title.charAt(0)}
                                </span>
                            ))}
                        </div>
                        <Link href={route('movies.compare')} className="btn btn-primary btn-sm">Compare {list.length}</Link>
                        <button type="button" onClick={clear} className="grid h-9 w-9 place-items-center text-mute hover:text-paper" aria-label="Clear compare list"><Icon name="close" size={14} /></button>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
