import type { InertiaFormProps } from '@inertiajs/react';
import type { VisitOptions } from '@inertiajs/core';
import { recaptchaToken } from '@/components/Captcha';
import type { Captcha } from '@/types';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

/**
 * Submits an Inertia form, adding a reCAPTCHA v3 token first when the page
 * has one configured. The page keeps its scroll and local state so field
 * errors appear in place.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export async function submitForm<T extends Record<string, any>>(form: InertiaFormProps<T>, method: Method, url: string, options: Partial<VisitOptions> = {}, captcha?: Captcha): Promise<void> {
    const token = await recaptchaToken(captcha);

    form.transform((data) => (token ? { ...data, recaptcha_v3_token: token } : data));
    form.submit(method, url, { preserveScroll: true, preserveState: true, ...options } as never);
}
