<?php

/*
| Fictional cinema network. Addresses, phone numbers and emails are illustrative.
| screens: [name, format, sound system]
*/

return [
    'cities' => [
        ['name' => 'Karachi', 'province' => 'Sindh'],
        ['name' => 'Lahore', 'province' => 'Punjab'],
        ['name' => 'Islamabad', 'province' => 'Islamabad Capital Territory'],
        ['name' => 'Rawalpindi', 'province' => 'Punjab'],
    ],
    'amenities' => [
        ['IMAX with Laser', 'Largest screen in the city with 12-channel sound.'],
        ['Dolby Atmos', 'Object-based surround sound, including overhead speakers.'],
        ['Recliner seating', 'Fully reclining, heated leather seats with side tables.'],
        ['Wheelchair access', 'Step-free route and companion seating in every screen.'],
        ['Parking', 'On-site or partner parking with validation.'],
        ['Food court', 'Hot food, coffee and a full snack counter.'],
        ['Kids play area', 'Supervised play area for younger guests.'],
        ['Prayer room', 'Separate prayer rooms for men and women.'],
        ['Closed captions', 'Caption devices available for selected shows.'],
    ],
    'theaters' => [
        [
            'name' => 'Metropole Picture House', 'city' => 'Karachi', 'address' => 'Level 3, Seaview Galleria, Block 2, Clifton, Karachi 75600',
            'phone' => '+92 21 3587 4410', 'email' => 'boxoffice@metropole.example', 'lat' => 24.8138, 'lng' => 67.0300,
            'opens' => '10:00', 'closes' => '01:30',
            'description' => 'A restored 1960s picture palace reborn as a three-screen cinema by the sea, with an IMAX auditorium and a rooftop café overlooking Clifton beach.',
            'amenities' => ['IMAX with Laser', 'Dolby Atmos', 'Wheelchair access', 'Parking', 'Food court', 'Prayer room', 'Closed captions'],
            'screens' => [['Screen 1', 'imax', 'IMAX 12-channel'], ['Screen 2', 'dolby_cinema', 'Dolby Atmos'], ['Screen 3', 'standard', '7.1 Surround']],
        ],
        [
            'name' => 'Harbourline Cinemas', 'city' => 'Karachi', 'address' => 'Ocean Walk Mall, Khayaban-e-Shaheen, DHA Phase 8, Karachi 75500',
            'phone' => '+92 21 3524 9900', 'email' => 'hello@harbourline.example', 'lat' => 24.7922, 'lng' => 67.0652,
            'opens' => '11:00', 'closes' => '02:00',
            'description' => 'Karachi’s first 4DX auditorium alongside a recliner lounge, inside the waterfront Ocean Walk development.',
            'amenities' => ['Recliner seating', 'Dolby Atmos', 'Wheelchair access', 'Parking', 'Food court', 'Kids play area'],
            'screens' => [['Lounge A', 'recliner', 'Dolby Atmos'], ['4DX Hall', '4dx', '7.1 Surround']],
        ],
        [
            'name' => 'Lumière Grand', 'city' => 'Lahore', 'address' => 'Grand Arcade, 42 Main Boulevard, Gulberg III, Lahore 54660',
            'phone' => '+92 42 3578 2200', 'email' => 'tickets@lumieregrand.example', 'lat' => 31.5102, 'lng' => 74.3441,
            'opens' => '10:30', 'closes' => '01:00',
            'description' => 'A grand art-deco multiplex on Main Boulevard with Lahore’s largest single auditorium and a Dolby Cinema screen.',
            'amenities' => ['IMAX with Laser', 'Dolby Atmos', 'Wheelchair access', 'Parking', 'Food court', 'Prayer room'],
            'screens' => [['Grand Hall', 'imax', 'IMAX 12-channel'], ['Salon Deux', 'dolby_cinema', 'Dolby Atmos']],
        ],
        [
            'name' => 'The Arcadia', 'city' => 'Lahore', 'address' => 'Arcadia Square, Sector C, DHA Phase 6, Lahore 54792',
            'phone' => '+92 42 3713 5050', 'email' => 'hello@arcadia.example', 'lat' => 31.4697, 'lng' => 74.4535,
            'opens' => '11:00', 'closes' => '00:30',
            'description' => 'A boutique neighbourhood cinema with a ScreenX panoramic auditorium and an intimate recliner room for private screenings.',
            'amenities' => ['Recliner seating', 'Wheelchair access', 'Parking', 'Kids play area', 'Closed captions'],
            'screens' => [['Panorama', 'screenx', '7.1 Surround'], ['Studio', 'recliner', 'Dolby Atmos']],
        ],
        [
            'name' => 'Northlight Cinema', 'city' => 'Islamabad', 'address' => 'Centaurus Lane, Jinnah Avenue, F-8, Islamabad 44000',
            'phone' => '+92 51 2287 3300', 'email' => 'contact@northlight.example', 'lat' => 33.7077, 'lng' => 73.0498,
            'opens' => '10:00', 'closes' => '01:00',
            'description' => 'A glass-fronted cinema at the foot of the Margalla Hills, home of the capital’s independent film week and a Dolby Cinema screen.',
            'amenities' => ['Dolby Atmos', 'Recliner seating', 'Wheelchair access', 'Parking', 'Food court', 'Prayer room', 'Closed captions'],
            'screens' => [['Margalla', 'dolby_cinema', 'Dolby Atmos'], ['Daman', 'standard', '7.1 Surround']],
        ],
        [
            'name' => 'Marigold Screens', 'city' => 'Rawalpindi', 'address' => 'Marigold Mall, Murree Road, Satellite Town, Rawalpindi 46000',
            'phone' => '+92 51 4412 7788', 'email' => 'hello@marigold.example', 'lat' => 33.6405, 'lng' => 73.0718,
            'opens' => '11:00', 'closes' => '00:00',
            'description' => 'A friendly family cinema with affordable matinées, a kids play area and one of the region’s first 4DX halls.',
            'amenities' => ['Wheelchair access', 'Parking', 'Food court', 'Kids play area', 'Prayer room'],
            'screens' => [['Screen 1', '4dx', '7.1 Surround'], ['Screen 2', 'standard', '5.1 Surround']],
        ],
    ],
];
