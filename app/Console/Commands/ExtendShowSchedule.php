<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seeded shows cover a single week, so a local install runs dry once that
 * week passes. This repeats each screen's latest week (with its prices) in
 * whole-week steps until the schedule reaches $days ahead, and marks shows
 * that have already started as completed.
 */
class ExtendShowSchedule extends Command
{
    protected $signature = 'shows:extend {--days=7 : Keep the schedule this many days ahead}';

    protected $description = 'Roll the weekly show schedule forward so there are always upcoming shows';

    public function handle(): int
    {
        $target = now()->startOfDay()->addDays((int) $this->option('days'));
        $created = 0;

        DB::table('shows')->where('status', 'scheduled')
            ->whereRaw("TIMESTAMP(show_date, show_time) < ?", [now()])
            ->update(['status' => 'completed', 'updated_at' => now()]);

        foreach (DB::table('screens')->pluck('id') as $screenId) {
            $last = DB::table('shows')->where('screen_id', $screenId)->where('status', '!=', 'cancelled')->max('show_date');
            if (! $last) {
                continue;
            }

            $template = DB::table('shows')
                ->join('movies', 'movies.id', '=', 'shows.movie_id')
                ->where('shows.screen_id', $screenId)
                ->where('shows.status', '!=', 'cancelled')
                ->where('movies.status', 'now_showing')
                ->whereBetween('shows.show_date', [Carbon::parse($last)->subDays(6)->toDateString(), $last])
                ->select('shows.*')
                ->get();
            if ($template->isEmpty()) {
                continue;
            }

            // Whole-week offsets keep weekday pricing (matinées) aligned; past dates are skipped.
            for ($weeks = 1; Carbon::parse($last)->addWeeks($weeks)->subDays(6)->lte($target); $weeks++) {
                $created += $this->copyWeek($template, $weeks * 7);
            }
        }

        if ($created > 0) {
            Cache::flush();
        }
        $this->info("Added {$created} shows.");

        return self::SUCCESS;
    }

    private function copyWeek($template, int $offsetDays): int
    {
        $count = 0;

        DB::transaction(function () use ($template, $offsetDays, &$count) {
            foreach ($template as $show) {
                $date = Carbon::parse($show->show_date)->addDays($offsetDays);
                if ($date->lt(now()->startOfDay())) {
                    continue;
                }
                $exists = DB::table('shows')->where('screen_id', $show->screen_id)
                    ->where('show_date', $date->toDateString())->where('show_time', $show->show_time)->exists();
                if ($exists) {
                    continue;
                }

                $id = DB::table('shows')->insertGetId([
                    'movie_id' => $show->movie_id,
                    'screen_id' => $show->screen_id,
                    'show_date' => $date->toDateString(),
                    'show_time' => $show->show_time,
                    'status' => Carbon::parse($date->toDateString().' '.$show->show_time)->isPast() ? 'completed' : 'scheduled',
                    'total_seats' => $show->total_seats,
                    'booked_seats' => 0,
                    'created_by' => $show->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach (['show_seat_row_prices', 'show_seat_prices'] as $table) {
                    $rows = DB::table($table)->where('show_id', $show->id)->get()->map(function ($row) use ($id) {
                        $row = (array) $row;
                        unset($row['id']);

                        return ['show_id' => $id, 'created_at' => now(), 'updated_at' => now()] + $row;
                    })->all();
                    if ($rows) {
                        DB::table($table)->insert($rows);
                    }
                }
                $count++;
            }
        });

        return $count;
    }
}
