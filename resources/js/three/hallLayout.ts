/**
 * Auditorium geometry shared by the 3D hall and the seat page. No three.js
 * here, so the seat page can score a seat without loading the 3D bundle.
 * One unit is about one metre.
 */
export const SEAT_GAP = 0.62;
export const AISLE_GAP = 0.55;
export const ROW_DEPTH = 1.0;
export const ROW_RISE = 0.3;
export const FIRST_ROW_Z = 4.2;
export const EYE_HEIGHT = 0.78;
export const SCREEN_Z = -2.6;
export const SCREEN_WIDTH = 10;
export const SCREEN_HEIGHT = SCREEN_WIDTH / 2.2;
export const SCREEN_Y = 3.1;

export type HallSeat = { id: number; number: number; label: string; tier: string; status: string; available: boolean };
export type HallRow = { label: string; seats: HallSeat[] };
export type Layout = { seat: HallSeat; row: string; x: number; y: number; z: number }[];

/** Positions every seat like the real room: aisles, a raked floor, row A at the front. */
export function computeLayout(rows: HallRow[], aisles: number[]): Layout {
    const layout: Layout = [];
    rows.forEach((row, rowIndex) => {
        let x = 0;
        const xs = row.seats.map((seat) => {
            const position = x;
            x += SEAT_GAP + (aisles.includes(seat.number) ? AISLE_GAP : 0);
            return position;
        });
        const width = xs.length ? xs[xs.length - 1] : 0;
        row.seats.forEach((seat, index) => {
            layout.push({ seat, row: row.label, x: xs[index] - width / 2, y: rowIndex * ROW_RISE, z: FIRST_ROW_Z + rowIndex * ROW_DEPTH });
        });
    });
    return layout;
}

export type ViewStats = { distance: number; fill: number; offAxis: number; tilt: number; score: number; verdict: string; notes: string[] };

/**
 * How good the view is, the way cinema designers measure it: the screen
 * should fill about 40° of your vision (THX), you should face it square
 * on, and you should not have to crane your neck upward.
 */
export function viewStats(layout: Layout, seatId: number): ViewStats | null {
    const item = layout.find((entry) => entry.seat.id === seatId);
    if (!item) return null;

    const eyeY = item.y + 0.3 + EYE_HEIGHT;
    const depth = item.z - SCREEN_Z;
    const distance = Math.hypot(item.x, SCREEN_Y - eyeY, depth);
    const fill = (2 * Math.atan(SCREEN_WIDTH / 2 / depth) * 180) / Math.PI;
    const offAxis = (Math.atan(Math.abs(item.x) / depth) * 180) / Math.PI;
    const tilt = (Math.atan((SCREEN_Y - eyeY) / depth) * 180) / Math.PI;

    const score = Math.round(Math.max(0, Math.min(100, 100 - Math.abs(fill - 40) * 1.1 - offAxis * 1.6 - Math.max(0, tilt - 12) * 2.2)));
    const verdict = score >= 88 ? 'Ideal' : score >= 75 ? 'Great' : score >= 60 ? 'Good' : score >= 45 ? 'Fair' : 'Limited';
    const notes = [
        fill > 55 ? 'Very close: the screen fills your whole view.' : fill < 30 ? 'Far back: a smaller picture, easy on the eyes.' : 'The screen fills your view about as much as the film was mixed for.',
        offAxis > 18 ? 'Off to the side: you watch the screen at an angle.' : offAxis > 8 ? 'Slightly off-centre.' : 'Dead centre for the picture and the sound.',
        tilt > 18 ? 'You look up quite a lot from here.' : 'Comfortable eye line, no craning.',
    ];

    return { distance, fill, offAxis, tilt, score, verdict, notes };
}
