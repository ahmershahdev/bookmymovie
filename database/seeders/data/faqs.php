<?php

/*
| [category, question, answer]
*/

return [
    ['Booking', 'How many seats can I book at once?', 'Up to four seats per booking, all for the same show. If you need more for a group, make a second booking or contact the cinema’s group sales desk.'],
    ['Booking', 'How long are my selected seats held?', 'Seats you add to your cart are held for 10 minutes. The timer restarts whenever you add another seat. When it runs out, the seats return to sale automatically.'],
    ['Booking', 'Can two people book the same seat?', 'No. Seats are locked inside a database transaction the moment you add them, and the booking table enforces one live booking per seat per show. If someone beats you to a seat by a fraction of a second, you will see a clear message and can pick another.'],
    ['Booking', 'Why do seats in the same show have different prices?', 'Cinemas price by row. Front stalls are the most affordable, the centre rows have the best sightlines, and the back rows are recliners. The seat map shows every row’s price before you select it.'],
    ['Booking', 'Do you offer child tickets?', 'Yes, for guests aged 3 to 12 on most rows. Recliner rows are adult-priced only. Choose “Child” before selecting seats.'],
    ['Payments', 'How do I pay?', 'Pay at the cinema box office when you arrive, by cash or card. Please arrive 20 minutes early so you are through the queue before the trailers.'],
    ['Payments', 'Are there any booking fees?', 'No. The price on the seat map is the full price, including taxes. There is no convenience fee at checkout.'],
    ['Payments', 'How do coupon codes work?', 'Enter the code at checkout. It is validated when you place the booking: the minimum order value, expiry date and per-account limit all apply. Discounts are shown on your booking and your e-ticket.'],
    ['Cancellations', 'Can I cancel my booking?', 'Yes. Unpaid bookings can be cancelled from Account → Bookings until two hours before the show. The seats are released instantly and any coupon is returned to your account.'],
    ['Cancellations', 'What if the cinema cancels my show?', 'You will receive an email and the booking is cancelled at no cost. If you had already paid, the box office refunds you within 7 working days.'],
    ['Tickets', 'Do I need to print my ticket?', 'No. Show the booking number or the e-ticket page on your phone. Each seat also has its own ticket code if your party arrives separately.'],
    ['Tickets', 'Where do I find my booking later?', 'Sign in and open Account → Bookings. Every booking has a detail page, an e-ticket and a tracking timeline.'],
    ['Account', 'Why must I verify my email?', 'Verification makes sure your tickets and password resets reach you, and it stops people creating accounts in someone else’s name. The code is valid for 15 minutes.'],
    ['Account', 'I forgot my password. What now?', 'Use “Forgot password” on the sign-in page. If an account exists for that address, we will email a reset link that is valid for 15 minutes.'],
    ['Account', 'Can I sign in with Google or Facebook?', 'Yes, when the cinema operator has enabled it. Social sign-in uses the email address your provider shares with us.'],
    ['Cinemas', 'What do IMAX, Dolby Cinema and 4DX mean?', 'IMAX with Laser uses a much larger, brighter screen and a 12-channel sound system. Dolby Cinema combines Dolby Vision HDR projection with Dolby Atmos sound. 4DX adds motion seats, wind, water and scent effects synchronised to the film.'],
    ['Cinemas', 'Are your cinemas wheelchair accessible?', 'Yes. Every partner venue has step-free access and wheelchair spaces with a companion seat. Mention it at the box office and staff will help with seating.'],
    ['Cinemas', 'Can I bring my own food?', 'Outside food and drink are not permitted at partner cinemas, except for baby food and medical needs.'],
    ['Security', 'Why do I see a security code on some forms?', 'Sign-in, registration, contact and checkout forms are protected by a short security code and Google reCAPTCHA. They stop bots from trying stolen passwords or snapping up seats.'],
    ['Security', 'How is my password stored?', 'Passwords are hashed with bcrypt and can never be read back, not even by our team. Repeated failed sign-ins are slowed down and temporarily blocked.'],
    ['Security', 'How do I report a security issue?', 'Please email security@bookmymovie.ahmershah.dev or follow the responsible disclosure process in our public SECURITY.md. Please do not test against other people’s accounts.'],
];
