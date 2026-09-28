import { usePage } from '@inertiajs/react';
import ur from '@/lang/ur';

export type Locale = 'en' | 'ur';

type Replacements = Record<string, string | number>;

/**
 * English is the source language: every key is the English text itself, so
 * an untranslated string simply shows in English. Urdu lives in lang/ur.ts.
 * `:name` placeholders are filled from `replace`.
 */
export function translate(locale: Locale, key: string, replace?: Replacements): string {
    let text = locale === 'ur' ? (ur[key] ?? key) : key;
    if (replace) {
        for (const [name, value] of Object.entries(replace)) text = text.replaceAll(`:${name}`, String(value));
    }
    return text;
}

export function useLocale(): Locale {
    return (usePage().props as { locale?: Locale }).locale === 'ur' ? 'ur' : 'en';
}

export function useT(): (key: string, replace?: Replacements) => string {
    const locale = useLocale();
    return (key, replace) => translate(locale, key, replace);
}

/** Keeps <html lang dir> in step with the active language after Inertia visits. */
export function applyDocumentLocale(locale: string | undefined): void {
    const value = locale === 'ur' ? 'ur' : 'en';
    document.documentElement.lang = value;
    document.documentElement.dir = value === 'ur' ? 'rtl' : 'ltr';
}
