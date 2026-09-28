import { AnimatePresence, motion } from 'motion/react';
import Icon from '@/components/Icon';
import { cn } from '@/lib/utils';

const ease = [0.16, 1, 0.3, 1] as const;

export default function Accordion({ id, question, answer, open, onToggle }: { id: number; question: string; answer: string; open: boolean; onToggle: () => void }) {
    return (
        <div className="border-b border-line">
            <h3>
                <button type="button" onClick={onToggle} aria-expanded={open} aria-controls={`faq-${id}`}
                    className="group flex w-full items-center justify-between gap-6 py-6 text-left text-xl font-semibold transition-colors hover:text-accent sm:text-2xl [font-stretch:90%]">
                    {question}
                    <span className={cn('grid h-10 w-10 shrink-0 place-items-center border transition duration-500', open ? 'rotate-45 border-accent bg-volt text-noir' : 'border-line-2')}>
                        <Icon name="plus" size={16} />
                    </span>
                </button>
            </h3>
            <AnimatePresence initial={false}>
                {open && (
                    <motion.div id={`faq-${id}`} initial={{ height: 0, opacity: 0 }} animate={{ height: 'auto', opacity: 1 }} exit={{ height: 0, opacity: 0 }} transition={{ duration: 0.45, ease }} className="overflow-hidden">
                        <p className="max-w-2xl pb-7 leading-relaxed text-paper-2">{answer}</p>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
