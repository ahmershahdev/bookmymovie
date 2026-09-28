<?php

namespace App\Jobs;

use App\Support\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seats came back on a show (a cancellation or an expired hold): tell the
 * waitlist, first come first served, only as many people as there are
 * seats for. Each person is told once; joining again re-arms it.
 */
class ProcessShowWaitlist implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $showId) {}

    public function handle(): void
    {
        $show = DB::table('v_show_details')->where('show_id', $this->showId)->first();
        if (! $show || $show->show_status !== 'scheduled' || Carbon::parse($show->show_date.' '.$show->show_time)->isPast()) {
            return;
        }

        $free = DB::table('v_seat_availability')->where('show_id', $this->showId)->where('seat_status', 'available')->count();
        if ($free === 0) {
            return;
        }

        $chosen = [];
        $budget = $free;
        DB::transaction(function () use (&$chosen, &$budget) {
            $waiting = DB::table('show_waitlists')->where('show_id', $this->showId)->whereNull('notified_at')
                ->orderBy('created_at')->orderBy('id')->lockForUpdate()->get(['id', 'user_id', 'seats_wanted']);
            foreach ($waiting as $entry) {
                if ($entry->seats_wanted > $budget) {
                    continue;
                }
                $chosen[] = (int) $entry->user_id;
                $budget -= $entry->seats_wanted;
                DB::table('show_waitlists')->where('id', $entry->id)->update(['notified_at' => now(), 'updated_at' => now()]);
                if ($budget <= 0) {
                    break;
                }
            }
        });

        Notify::users(
            $chosen,
            'waitlist',
            'Seats just opened: '.$show->movie_title,
            $free.' '.($free === 1 ? 'seat is' : 'seats are').' free for '.Carbon::parse($show->show_date.' '.$show->show_time)->format('D j M, g:i A').' at '.$show->theater_name.'. First to hold them gets them.',
            route('movies.seats', ['slug' => $show->movie_slug, 'show' => $this->showId]),
            'show',
            $this->showId,
        );
    }
}
