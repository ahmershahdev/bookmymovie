export type Palette = [string, string, string];

export interface Trailer {
    src: string;
    label: string;
    poster: string | null;
}

/** Movie::toCardArray() */
export interface MovieCard {
    id: number;
    title: string;
    slug: string;
    tagline: string;
    poster_url: string | null;
    banner_url: string | null;
    hero_image_url: string | null;
    hero_eyebrow: string;
    trailers: Trailer[];
    genre: string;
    genres: string[];
    language: string;
    duration: string;
    duration_minutes: number;
    rating: number;
    reviews: number;
    certificate: string;
    status: string;
    status_key: 'now_showing' | 'coming_soon' | 'ended' | string;
    release_date: string | null;
    release_year: string | null;
    price: number;
    original_price: number;
    first_show_id: number | null;
    palette: Palette;
}

export type NavMovie = Pick<MovieCard, 'id' | 'title' | 'slug' | 'genre' | 'duration' | 'palette' | 'poster_url' | 'certificate' | 'release_year' | 'rating' | 'reviews'>;

export interface Crumb {
    label: string;
    url: string | null;
}

export interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Captcha {
    action: string;
    image: string | null;
    length: number;
    v2_site_key: string | null;
    v3_site_key: string | null;
}

export interface SharedProps {
    auth: {
        user: null | {
            id: number;
            name: string;
            first_name: string;
            email: string;
            avatar: string | null;
            verified: boolean;
        };
        admin: boolean;
    };
    site: {
        name: string;
        support_email: string;
        support_phone: string;
        footer_description: string;
        copyright_note: string;
        response_sla: string;
        service_area: string;
        contact_heading: string | null;
        contact_intro: string | null;
        catalog_heading: string | null;
        catalog_intro: string | null;
        max_seats: number;
        hold_minutes: number;
    };
    locale: 'en' | 'ur';
    counts: { cart: number; wishlist: number };
    navMovies: NavMovie[];
    footer: {
        genres: { name: string; slug: string }[];
        cinemas: { name: string; slug: string; city: string }[];
    };
    flash: null | { status: string; id: string };
    errors: Record<string, string>;
    meta: { title: string; description: string; breadcrumbs: Crumb[] };
    [key: string]: unknown;
}

export interface BookingSummary {
    number: string;
    state: string;
    tone: '' | 'mint' | 'volt' | 'signal';
    cancelled: boolean;
    paid: boolean;
    method: string;
    method_label: string;
    can_pay_online: boolean;
    movie: MovieCard;
    title: string;
    starts: string | null;
    relative: string | null;
    theater: string;
    seats: string[];
    seat_count: number;
    total: number;
}
