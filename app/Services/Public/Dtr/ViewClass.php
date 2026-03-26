<?php

namespace App\Services\Public\Dtr;

use DateTime;
use Hashids\Hashids;
use App\Models\Dtr;
use App\Models\User;

class ViewClass
{
    public function list($request)
    {    
        $hashids = new Hashids('krad', 10);
        $station_id = $hashids->decode($request->code)[0] ?? null;
        
        $dtrs = Dtr::with('user.profile','user.organization.division')
            ->whereDate('date', now())
            ->where('station_id',$station_id)
            ->orderBy('created_at', 'desc')
            ->get();


        // Map each DTR into multiple rows (AM/PM IN/OUT)
        $rows = $dtrs->flatMap(function($dtr) {

            // Helper function to create a row for each time entry
            $makeRow = function($json, $typeLabel) use ($dtr) {
                if (!$json) return null;

                // Decode JSON and extract raw time
                $decoded = json_decode(trim($json, '"'), true);
                $raw = $decoded['time'] ?? null;

                // fallback if stored as plain HH:MM
                if (!$raw && is_string($json)) $raw = $json;

                if (!$raw) return null;
                $subtype = str_contains(strtolower($typeLabel), 'out') ? 'out' : 'in';
                return [
                    'avatar' => $dtr->user->profile->avatar,
                    'name' => $dtr->user->profile->fullname ?? 'No Name',
                    'division' => $dtr->user->organization->division->name ?? 'No Division',
                    'dtr_id' => $dtr->id,
                    'date' => $dtr->date,
                    'type' => $typeLabel,
                    'subtype' => $subtype,
                    'time' => $this->formatTimeFromJson($json),   // formatted 12-hour
                    'sort_time' => strtotime($raw),               // used for sorting
                    'remarks' => $dtr->remarks,
                ];
            };

            // Generate rows for AM/PM IN/OUT
            return collect([
                $makeRow($dtr->am_in_at,  'Time In (am)'),
                $makeRow($dtr->am_out_at, 'Time Out (am)'),
                $makeRow($dtr->pm_in_at,  'Time In (pm)'),
                $makeRow($dtr->pm_out_at, 'Time Out (pm)'),
            ])->filter(); // remove nulls
        });
        return $rows->sortByDesc('sort_time')->values();
    }

    private function formatTimeFromJson($json) 
    {
        if (!$json) return null;
        $decoded = json_decode(trim($json, '"'), true);
        $raw = $decoded['time'] ?? null;
        if (!$raw && is_string($json)) {
            $raw = $json;
        }
        if (!$raw) return null;
        $time = DateTime::createFromFormat('H:i:s', $raw);
        return $time ? $time->format('g:i A') : null;
    }

}
