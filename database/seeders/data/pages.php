<?php

/*
|--------------------------------------------------------------------------
| Content pages
|--------------------------------------------------------------------------
|
| Rendered by resources/views/public/page.blade.php. Each section may have a
| `body` paragraph, a list of `items`, or both. meta_title stays under 60
| characters and meta_description under 150, as the layout enforces.
|
*/

return [
    [
        'slug' => 'about',
        'title' => 'Cinema, booked properly.',
        'meta_title' => 'About BookMyMovie | Our Story',
        'meta_description' => 'BookMyMovie is an open-source cinema booking platform: live seat maps, honest prices and tickets that just work at the door.',
        'hero_label' => 'About us',
        'excerpt' => 'BookMyMovie started with a simple frustration: booking a cinema seat should take less time than the adverts before the film. We built a platform that shows every seat, every price and every showtime honestly, then gets out of your way.',
        'body' => "BookMyMovie is an independent, open-source ticketing platform for cinemas across Pakistan. We partner with multiplexes and heritage single screens alike, and we publish every line of our code so that anyone can see exactly how a booking is made, priced and protected.\n\nWe do not sell your data, we do not add surprise convenience fees at the last step, and we do not hold seats hostage with countdown tricks. What you see on the seat map is what the cinema has: the same live inventory the box office uses.",
        'sections' => [
            ['title' => 'What we believe', 'items' => [
                'The price on the seat is the price you pay. Discounts are applied before you confirm, never after.',
                'A seat is either yours or someone else’s. Our booking engine makes double-selling a seat impossible at the database level.',
                'Cinemas are community spaces. We give single-screen theatres the same tools as national chains.',
                'Privacy is a feature. We collect what a booking needs and nothing more.',
            ]],
            ['title' => 'How a booking works', 'body' => 'Pick a film, choose a showtime, select up to four seats on the live map and they are held for you for ten minutes while you check out. Pay at the counter when you arrive, show your booking number or e-ticket, and walk in.'],
            ['title' => 'Built in the open', 'body' => 'The entire platform, from the seat-locking logic to this page, is published under the MIT licence. Developers are welcome to study it, deploy it for their own cinema, or send improvements back.'],
            ['title' => 'Our cinema partners', 'items' => [
                'Six venues across Karachi, Lahore, Islamabad and Rawalpindi.',
                'Formats from classic 2D to IMAX with Laser, Dolby Cinema and 4DX.',
                'Wheelchair-accessible seating at every partner venue.',
            ]],
        ],
    ],
    [
        'slug' => 'terms',
        'title' => 'Terms of Service',
        'meta_title' => 'Terms of Service | BookMyMovie',
        'meta_description' => 'The rules for using BookMyMovie: accounts, seat holds, bookings, pay-at-counter payments, conduct and liability.',
        'hero_label' => 'Legal',
        'excerpt' => 'These terms govern your use of BookMyMovie. They are written to be read, not skimmed. By creating an account or making a booking you agree to them.',
        'body' => "Last updated: 27 September 2026.\n\nBookMyMovie (“we”, “us”) operates this website as an intermediary between you and the cinema that screens the film. The cinema is responsible for the screening itself; we are responsible for the accuracy of the booking we give you.",
        'sections' => [
            ['title' => '1. Your account', 'items' => [
                'You must be at least 13 years old to create an account. Films rated R or A require the ticket holder to meet the cinema’s age policy on the day.',
                'Provide an accurate name, email address and phone number. We use them only to deliver and verify your booking.',
                'Keep your password private. You are responsible for bookings made from your account until you tell us it has been compromised.',
                'We may suspend accounts that abuse the service, including automated booking, reselling tickets or repeated no-shows.',
            ]],
            ['title' => '2. Seat holds', 'body' => 'Selecting seats places a temporary hold for 10 minutes. A hold is not a booking. If you do not complete checkout before the timer ends, the seats are released automatically so other guests can book them. You may hold up to four seats for one show at a time.'],
            ['title' => '3. Bookings and prices', 'items' => [
                'A booking is confirmed only when you receive a booking number on screen and by email.',
                'Prices are set by the cinema per show, seat row and ticket type, and include all applicable taxes.',
                'Child tickets are for guests aged 3–12. The cinema may ask for proof of age at the door.',
                'Coupon codes are single-use per account unless the offer says otherwise and cannot be exchanged for cash.',
            ]],
            ['title' => '4. Payment', 'body' => 'Bookings are currently paid at the cinema box office (pay at counter). Please arrive at least 20 minutes before the show to pay and collect your seats. Unpaid bookings may be released by the cinema 15 minutes before the show starts.'],
            ['title' => '5. At the cinema', 'items' => [
                'Bring your booking number or e-ticket and a valid photo ID for age-restricted films.',
                'Recording any part of a film is illegal and will result in removal without refund.',
                'Cinema staff may refuse entry to guests who are disruptive or who do not meet the film’s age rating.',
            ]],
            ['title' => '6. Liability', 'body' => 'If a show is cancelled or a seat cannot be honoured because of an error on our side, we will cancel the booking at no cost to you and help you rebook. We are not liable for indirect losses such as travel or parking costs.'],
            ['title' => '7. Changes to these terms', 'body' => 'We may update these terms as the service evolves. Material changes will be announced on the site at least 14 days before they take effect.'],
        ],
    ],
    [
        'slug' => 'privacy',
        'title' => 'Privacy Policy',
        'meta_title' => 'Privacy Policy | BookMyMovie',
        'meta_description' => 'What BookMyMovie collects, why, how long we keep it, who we share it with and how to access or delete your data.',
        'hero_label' => 'Privacy',
        'excerpt' => 'We collect the minimum information needed to issue a ticket and keep the service secure. We never sell personal data, and we never will.',
        'body' => "Last updated: 27 September 2026.\n\nThis policy explains what personal data BookMyMovie processes when you browse, create an account or make a booking, and the choices you have.",
        'sections' => [
            ['title' => 'What we collect', 'items' => [
                'Account details: your name, email address, phone number and, optionally, date of birth, address and profile photo.',
                'Booking details: the show, seats, ticket types, price paid, coupon used and payment status.',
                'Security data: IP address, browser user agent and timestamps of sign-ins and sensitive actions.',
                'Messages you send us through the contact form.',
            ]],
            ['title' => 'Why we collect it', 'items' => [
                'To issue, deliver and verify your tickets at the cinema.',
                'To send transactional emails: verification codes, booking confirmations and password resets.',
                'To protect accounts and the platform from fraud, brute-force attacks and automated abuse.',
            ]],
            ['title' => 'Who we share it with', 'body' => 'The cinema you book with receives the name, booking number and seats for your booking so it can admit you. We use Google reCAPTCHA to tell humans from bots, which is subject to Google’s privacy policy. We do not share data with advertisers.'],
            ['title' => 'How long we keep it', 'items' => [
                'Booking records: 3 years, for accounting and dispute resolution.',
                'Security logs: 90 days.',
                'Contact messages: 12 months.',
                'Account data: until you delete your account.',
            ]],
            ['title' => 'How we protect it', 'body' => 'Passwords are hashed with bcrypt and never stored in readable form. All traffic is served over HTTPS with strict transport security, and the site sends a strict Content Security Policy. Access to the admin panel is limited, time-boxed and logged.'],
            ['title' => 'Your rights', 'items' => [
                'Access and download the personal data we hold about you.',
                'Correct anything that is inaccurate from your profile page.',
                'Ask us to delete your account and associated data, subject to legal retention of booking records.',
                'Contact privacy@bookmymovie.ahmershah.dev with any request. We reply within 30 days.',
            ]],
        ],
    ],
    [
        'slug' => 'refund',
        'title' => 'Cancellations & Refunds',
        'meta_title' => 'Cancellation and Refund Policy | BookMyMovie',
        'meta_description' => 'Cancel an unpaid booking up to two hours before the show. See when refunds apply for cancelled shows and cinema errors.',
        'hero_label' => 'Support',
        'excerpt' => 'Plans change. Unpaid bookings can be cancelled from your account up to two hours before the show, and the seats go straight back on sale.',
        'body' => "Last updated: 27 September 2026.\n\nBecause BookMyMovie bookings are paid at the cinema counter, most cancellations involve no money changing hands at all. This page explains what you can cancel yourself and when the cinema will refund a payment.",
        'sections' => [
            ['title' => 'Cancelling yourself', 'items' => [
                'Open Account → Bookings, choose the booking and select “Cancel booking”.',
                'Available for confirmed, unpaid bookings until 2 hours before the show starts.',
                'Your seats are released immediately and any coupon you used is returned to your account.',
            ]],
            ['title' => 'Full refund or free rebooking', 'items' => [
                'The cinema cancels or reschedules the show.',
                'A technical fault stops the screening for more than 20 minutes.',
                'Your seat is unavailable or double-allocated when you arrive.',
                'A duplicate booking was created in error (contact us within 24 hours).',
            ]],
            ['title' => 'Not refundable', 'items' => [
                'No-shows, or arriving after the doors close.',
                'Refused entry because the ticket holder does not meet the film’s age rating.',
                'Paid bookings cancelled less than 2 hours before the show.',
            ]],
            ['title' => 'How refunds are paid', 'body' => 'Counter payments are refunded at the same cinema box office in the original payment method within 7 working days. Bring your booking number and the payment receipt.'],
        ],
    ],
    [
        'slug' => 'eticket-info',
        'title' => 'Your e-ticket, explained',
        'meta_title' => 'E-Ticket Guide | BookMyMovie',
        'meta_description' => 'How BookMyMovie e-tickets work: booking numbers, per-seat ticket codes, paying at the counter and getting into your show.',
        'hero_label' => 'Tickets',
        'excerpt' => 'Every booking gets a booking number and every seat gets its own ticket code. Here is what to expect from the moment you check out to the moment the lights go down.',
        'body' => "Your e-ticket lives in your account and in your confirmation email. There is nothing to print, and it works even if your phone is offline once the page has loaded.",
        'sections' => [
            ['title' => 'What is on your ticket', 'items' => [
                'Booking number, for example BM-2026-8KQ2Z7TX.',
                'Film, cinema, screen and format.',
                'Date and start time, with the doors-open time.',
                'Each seat with its own ticket code, row tier and ticket type.',
                'Amount due at the counter and payment status.',
            ]],
            ['title' => 'On the day', 'items' => [
                'Arrive 20 minutes early if your booking is unpaid.',
                'Give your booking number at the box office and pay the amount shown.',
                'Show the e-ticket to the usher at the screen door.',
            ]],
            ['title' => 'Tracking your booking', 'body' => 'Account → Bookings → Track shows every step of a booking, from confirmation to payment and entry, with a timestamp for each change.'],
            ['title' => 'Lost your booking number?', 'body' => 'Sign in and open Bookings. If you checked out as a different account, contact support with the email address and phone number you used.'],
        ],
    ],
    [
        'slug' => 'cookies',
        'title' => 'Cookie Policy',
        'meta_title' => 'Cookie Policy | BookMyMovie',
        'meta_description' => 'BookMyMovie uses only essential cookies for sign-in, security and your seat hold. No advertising or tracking cookies.',
        'hero_label' => 'Legal',
        'excerpt' => 'We use a small number of strictly necessary cookies. There are no advertising cookies and no third-party tracking pixels on this site.',
        'body' => 'Because every cookie we set is essential to the service, there is no cookie banner asking for consent. If you block these cookies you will still be able to browse films, but you will not be able to sign in or book.',
        'sections' => [
            ['title' => 'Cookies we set', 'items' => [
                'bookmymovie-session: keeps you signed in and holds your seat selection (expires when you sign out or after 2 hours idle).',
                'XSRF-TOKEN: protects forms from cross-site request forgery (session length).',
                'remember_web_*: only if you tick “Keep me signed in” (up to 400 days).',
            ]],
            ['title' => 'Third parties', 'body' => 'On sign-in, registration, contact and checkout forms, Google reCAPTCHA may set its own cookies to distinguish people from automated traffic.'],
            ['title' => 'Local storage', 'body' => 'The compare feature saves up to four films in your browser’s local storage. It never leaves your device.'],
        ],
    ],
    [
        'slug' => 'accessibility',
        'title' => 'Accessibility',
        'meta_title' => 'Accessibility Statement | BookMyMovie',
        'meta_description' => 'Our commitment to an accessible booking experience: keyboard support, screen readers, contrast and accessible seating at every venue.',
        'hero_label' => 'Inclusion',
        'excerpt' => 'Everyone should be able to book a film night on their own terms. We design and test BookMyMovie against WCAG 2.2 level AA.',
        'body' => 'Accessibility is part of how we build, not a separate project. If anything on the site gets in your way, tell us and we will treat it as a bug.',
        'sections' => [
            ['title' => 'On the website', 'items' => [
                'Every page and the seat map can be used with a keyboard alone.',
                'Seats announce their row, number, tier, price and availability to screen readers.',
                'Text meets AA contrast ratios in both the default and high-contrast system modes.',
                'Animations respect your “reduce motion” preference.',
            ]],
            ['title' => 'At our partner cinemas', 'items' => [
                'Wheelchair spaces with a companion seat at every venue.',
                'Step-free access from the car park to the screen.',
                'Audio-description headsets and closed captions on selected shows (ask at the box office).',
                'Carers accompanying a guest with a disability are admitted free.',
            ]],
            ['title' => 'Feedback', 'body' => 'Email accessibility@bookmymovie.ahmershah.dev or use the contact form. We aim to respond within two working days.'],
        ],
    ],
];
