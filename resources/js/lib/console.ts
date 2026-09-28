/**
 * A note for whoever opens the console: a lit marquee in the house style,
 * who built it, where the source lives, and how to try the back office.
 * Printed once per page load and never in tests or server rendering.
 */
export function greetConsole(siteName: string): void {
    if (typeof window === 'undefined' || (window as Window & { __bmmGreeted?: boolean }).__bmmGreeted) return;
    (window as Window & { __bmmGreeted?: boolean }).__bmmGreeted = true;

    const volt = '#e3ff3b';
    const ink = '#0a0a0a';
    const bulbs = '● '.repeat(22).trim();

    console.log(
        `%c${bulbs}\n%c  ${siteName.toUpperCase()}  \n%c  NOW SHOWING: YOUR DEVTOOLS  \n%c${bulbs}`,
        `color:${volt};font:700 10px/1.4 monospace;letter-spacing:2px`,
        `background:${volt};color:${ink};font:900 34px/1.25 "Archivo Variable",Impact,sans-serif;letter-spacing:-1px;padding:6px 0`,
        `background:${ink};color:${volt};font:700 12px/2 monospace;letter-spacing:4px`,
        `color:${volt};font:700 10px/1.4 monospace;letter-spacing:2px`,
    );
    console.log(
        '%cHello, curious one. 🎬\n%cYou found the projection booth. Everything here is open source: Laravel 12, React 19 and Inertia, a Three.js hall, Lenis scroll, and a lot of care about seats.',
        'font:700 15px/1.6 system-ui,sans-serif;color:inherit',
        'font:400 13px/1.6 system-ui,sans-serif;color:#9a9a93',
    );
    console.log(
        '%c SOURCE %c github.com/ahmershahdev/bookmymovie\n%c AUTHOR %c Syed Ahmer Shah · ahmershah.dev\n%c ADMIN  %c /admin/login (demo admin is read-only on the live site)',
        `background:${volt};color:${ink};font:700 11px monospace`, 'font:11px monospace;color:inherit',
        `background:${volt};color:${ink};font:700 11px monospace`, 'font:11px monospace;color:inherit',
        `background:${volt};color:${ink};font:700 11px monospace`, 'font:11px monospace;color:inherit',
    );
    console.log('%cFound a bug or a security issue? SECURITY.md in the repo says how to reach us privately. Please don’t paste anything here that a stranger asked you to: it can take over your account.',
        'font:italic 12px/1.5 system-ui,sans-serif;color:#ff4d2e');
}
