import type { SVGProps } from 'react';

// 24px stroke icons, drawn to one consistent weight.
const paths: Record<string, string> = {
    'arrow-right': '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'arrow-left': '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'arrow-up-right': '<path d="M7 17 17 7M8 7h9v9"/>',
    'arrow-down': '<path d="M12 5v14M6 13l6 6 6-6"/>',
    'chevron-down': '<path d="m6 9 6 6 6-6"/>',
    'chevron-right': '<path d="m9 6 6 6-6 6"/>',
    'chevron-left': '<path d="m15 6-6 6 6 6"/>',
    search: '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
    heart: '<path d="M12 20s-7.5-4.4-7.5-10.1A4.4 4.4 0 0 1 12 7.1a4.4 4.4 0 0 1 7.5 2.8C19.5 15.6 12 20 12 20Z"/>',
    ticket: '<path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5V10a2 2 0 0 0 0 4v2.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 16.5V14a2 2 0 0 0 0-4Z"/><path d="M14 6v12" stroke-dasharray="2 2.5"/>',
    bag: '<path d="M5.5 8h13l-1 12h-11Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
    user: '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"/>',
    menu: '<path d="M4 8h16M4 16h16"/>',
    close: '<path d="M6 6l12 12M18 6 6 18"/>',
    clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    calendar: '<rect x="4" y="5.5" width="16" height="14.5" rx="2"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/>',
    pin: '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>',
    star: '<path d="m12 3.8 2.5 5.1 5.6.8-4 4 1 5.6-5.1-2.7-5 2.7.9-5.6-4-4 5.6-.8Z"/>',
    globe: '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.3 2.4 3.5 5.3 3.5 8.5s-1.2 6.1-3.5 8.5c-2.3-2.4-3.5-5.3-3.5-8.5s1.2-6.1 3.5-8.5Z"/>',
    film: '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M7.5 4.5v15M16.5 4.5v15M3.5 9h4M3.5 15h4M16.5 9h4M16.5 15h4"/>',
    screen: '<path d="M3 7c6-2.7 12-2.7 18 0"/><path d="M5 11h14M7 15h10M9 19h6"/>',
    seat: '<path d="M6 11V7a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v4"/><path d="M4 11h16v5H4ZM6 16v3M18 16v3"/>',
    shield: '<path d="M12 3.5 19 6v5.5c0 4.3-3 7.7-7 9-4-1.3-7-4.7-7-9V6Z"/><path d="m9 12 2 2 4-4"/>',
    lock: '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
    check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    info: '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
    alert: '<path d="M12 4 21 19.5H3Z"/><path d="M12 10v4M12 17h.01"/>',
    mail: '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
    phone: '<path d="M6.5 4h3l1.5 4-2 1.5a10 10 0 0 0 5.5 5.5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A15.5 15.5 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    trash: '<path d="M5 7h14M10 7V5h4v2M7 7l1 13h8l1-13"/>',
    compare: '<path d="M8 4v16M16 4v16M4 8h4M16 16h4"/>',
    sparkle: '<path d="M12 3.5c.6 4.2 2.3 6 6.5 6.5-4.2.6-5.9 2.3-6.5 6.5-.6-4.2-2.3-5.9-6.5-6.5 4.2-.5 5.9-2.3 6.5-6.5Z"/>',
    wheelchair: '<circle cx="11" cy="4.5" r="1.5"/><path d="M11 7v6h5l2 5M11 10h5"/><path d="M8 11.5a5 5 0 1 0 6.9 6.2"/>',
    car: '<path d="M5 16V11l2-4.5h10L19 11v5"/><path d="M4 16h16v2.5H4ZM7 18.5V20M17 18.5V20M5 11h14"/>',
    coffee: '<path d="M5 9h11v5a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5Z"/><path d="M16 10h1.5a2.5 2.5 0 0 1 0 5H16M8 3.5c0 1 1 1.5 1 2.5M12 3.5c0 1 1 1.5 1 2.5"/>',
    sound: '<path d="M5 9.5h3l4-3.5v12l-4-3.5H5Z"/><path d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/>',
    captions: '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="M10 10.5a2 2 0 1 0 0 3M16 10.5a2 2 0 1 0 0 3"/>',
    child: '<circle cx="12" cy="6" r="2.5"/><path d="M7 11.5 12 10l5 1.5M12 10v5l-2.5 5M12 15l2.5 5"/>',
    prayer: '<path d="M12 3.5v3M7 21v-7a5 5 0 0 1 10 0v7M4 21h16"/><path d="M12 6.5c-2 1.2-3 2.8-3 4.5"/>',
    logout: '<path d="M14 5h4a1.5 1.5 0 0 1 1.5 1.5v11A1.5 1.5 0 0 1 18 19h-4M10 16l4-4-4-4M14 12H4"/>',
    grid: '<rect x="4" y="4" width="6.5" height="6.5"/><rect x="13.5" y="4" width="6.5" height="6.5"/><rect x="4" y="13.5" width="6.5" height="6.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5"/>',
    github: '<path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12.3 12.3 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/>',
    linkedin: '<rect x="3.5" y="3.5" width="17" height="17" rx="2"/><path d="M8 10.5v6M8 7.5v.01M11.5 16.5v-3.5a2.5 2.5 0 0 1 5 0v3.5M11.5 10.5v6"/>',
    tag: '<path d="M3.5 12.2V4.5a1 1 0 0 1 1-1h7.7l8.3 8.3a1.4 1.4 0 0 1 0 2l-6.7 6.7a1.4 1.4 0 0 1-2 0Z"/><circle cx="8" cy="8" r="1.4"/>',
    eye: '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
    'eye-off': '<path d="M4 4l16 16M10.5 6c.5-.1 1-.1 1.5-.1 6 0 9.5 6.1 9.5 6.1a16 16 0 0 1-2.8 3.4M6.6 7.4A16 16 0 0 0 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 10a3 3 0 0 0 4.1 4.1"/>',
    refresh: '<path d="M19.5 11A7.5 7.5 0 0 0 6 7.2L4.5 9M4.5 13A7.5 7.5 0 0 0 18 16.8l1.5-1.8M4.5 4.5V9H9M19.5 19.5V15H15"/>',
    print: '<path d="M7 9V4h10v5M7 17H5a1.5 1.5 0 0 1-1.5-1.5v-5A1.5 1.5 0 0 1 5 9h14a1.5 1.5 0 0 1 1.5 1.5v5A1.5 1.5 0 0 1 19 17h-2"/><path d="M7 14h10v6H7Z"/>',
    quote: '<path d="M9.5 7C6.5 8 5 10.2 5 13.5V17h4.5v-4.5H7c0-2 1-3.3 2.5-4ZM18.5 7c-3 1-4.5 3.2-4.5 6.5V17h4.5v-4.5H16c0-2 1-3.3 2.5-4Z"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M12 3.5v2M12 18.5v2M20.5 12h-2M5.5 12h-2M18 6l-1.4 1.4M7.4 16.6 6 18M18 18l-1.4-1.4M7.4 7.4 6 6"/>',
    chart: '<path d="M4 19V5M4 19h16M8 16v-5M12 16V8M16 16v-3"/>',
    megaphone: '<path d="M4 10v4h3l7 4V6L7 10Z"/><path d="M17.5 9a3.5 3.5 0 0 1 0 6"/>',
};

interface IconProps extends Omit<SVGProps<SVGSVGElement>, 'name' | 'stroke'> {
    name: string;
    size?: number;
    stroke?: number;
}

export default function Icon({ name, size = 20, stroke = 1.6, className = '', ...rest }: IconProps) {
    return (
        <svg
            {...rest}
            className={`shrink-0 ${className}`}
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={stroke}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
            dangerouslySetInnerHTML={{ __html: paths[name] ?? paths.info }}
        />
    );
}
