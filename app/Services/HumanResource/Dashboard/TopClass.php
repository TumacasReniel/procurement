<?php

namespace App\Services\HumanResource\Dashboard;

use App\Models\Dtr;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Http\Resources\DefaultResource;

class TopClass
{
    public function absences($request)
    {
        $year  = $request->year ?? date('Y');
        $monthName = $request->month ?? date('F');
        $month = date('m', strtotime($monthName));

        // 1️⃣ Working days (Mon–Fri)
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();

        $workingDays = collect(CarbonPeriod::create($startOfMonth, $endOfMonth))
            ->filter(fn ($date) => !$date->isWeekend())
            ->map(fn ($date) => $date->toDateString())
            ->values();
        $today = Carbon::today()->toDateString();
        $workingDays = $workingDays
        ->filter(fn ($date) => $date <= $today)
        ->values();

        // 2️⃣ Schedules within the month
        $schedules = Schedule::where(function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('start', [$startOfMonth, $endOfMonth])
            ->orWhereBetween('end', [$startOfMonth, $endOfMonth])
            ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where('start', '<=', $startOfMonth)
                    ->where('end', '>=', $endOfMonth);
            });
        })->get();

        // 3️⃣ Expand schedule ranges into actual dates
        $scheduledDays = collect();

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start);
            $end   = $schedule->end
                ? Carbon::parse($schedule->end)
                : $start;

            $scheduledDays = $scheduledDays->merge(
                collect(CarbonPeriod::create($start, $end))
                    ->map(fn ($d) => $d->toDateString())
            );
        }

        // 4️⃣ Remove scheduled days from working days
        $effectiveWorkingDays = $workingDays
            ->diff($scheduledDays)
            ->values();

        // 5️⃣ Users
        $users = User::with('profile')
            ->whereHas('organization', function ($q) {
                // org condition
            })
            ->get();

        // 6️⃣ Compute absences
        $usersWithAbsences = $users->map(function ($user) use ($effectiveWorkingDays) {

            $presentDays = $user->dtrs()
                ->whereIn('date', $effectiveWorkingDays)
                ->pluck('date')
                ->toArray();

            $absentCount = $effectiveWorkingDays
                ->diff($presentDays)
                ->count();

            if ($absentCount > 0) {
                $user->absences_count = $absentCount;
                return $user;
            }
        })
        ->filter()
        ->sortByDesc('absences_count')
        ->take(10)
        ->values();

        return $usersWithAbsences;
    }

    public function lates($request)
    {
        $year  = $request->year ?? date('Y');
        $monthName = $request->month ?? date('F');
        $month = date('m', strtotime($monthName));

        // 1️⃣ Working days (Mon–Fri)
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();

        $workingDays = collect(CarbonPeriod::create($startOfMonth, $endOfMonth))
            ->filter(fn ($date) => !$date->isWeekend())
            ->map(fn ($date) => $date->toDateString())
            ->values();
        $today = Carbon::today()->toDateString();
        $workingDays = $workingDays
        ->filter(fn ($date) => $date <= $today)
        ->values();

        // 2️⃣ Schedules within the month
        $schedules = Schedule::where(function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('start', [$startOfMonth, $endOfMonth])
            ->orWhereBetween('end', [$startOfMonth, $endOfMonth])
            ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where('start', '<=', $startOfMonth)
                    ->where('end', '>=', $endOfMonth);
            });
        })->get();

        // 3️⃣ Expand schedule ranges into actual dates
        $scheduledDays = collect();

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start);
            $end   = $schedule->end
                ? Carbon::parse($schedule->end)
                : $start;

            $scheduledDays = $scheduledDays->merge(
                collect(CarbonPeriod::create($start, $end))
                    ->map(fn ($d) => $d->toDateString())
            );
        }

        // 4️⃣ Remove scheduled days from working days
        $effectiveWorkingDays = $workingDays
            ->diff($scheduledDays)
            ->values();

        // 5️⃣ Users
        $users = User::with('profile')
            ->whereHas('organization', function ($q) {
                // org condition
            })
            ->get();

        // 6️⃣ Compute absences
        $usersWithLates = $users->map(function ($user) use ($effectiveWorkingDays) {
            $lateCount = $user->dtrs()
                ->whereIn('date', $effectiveWorkingDays)
                ->where(function ($q) {
                    $q->where('tardiness', '>', 0)
                    ->orWhere('undertime', '>', 0);
                })
                ->count(); // 1 per DTR

            if ($lateCount > 0) {
                $user->lates_count = $lateCount;
                return $user;
            }
        })
        ->filter()
        ->sortByDesc('lates_count')
        ->take(10)
        ->values();

        return $usersWithLates;
    }
}
