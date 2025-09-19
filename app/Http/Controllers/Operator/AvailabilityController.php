<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\WeeklyAvailability;
use Illuminate\Http\Request;
use App\Models\AvailabilityChangeRequest;
use Carbon\Carbon;
use App\Models\Room;

class AvailabilityController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $days = [
            ['key' => 0, 'label' => 'Lun'],
            ['key' => 1, 'label' => 'Mar'],
            ['key' => 2, 'label' => 'Mer'],
            ['key' => 3, 'label' => 'Gio'],
            ['key' => 4, 'label' => 'Ven'],
            ['key' => 5, 'label' => 'Sab'],
            ['key' => 6, 'label' => 'Dom'],
        ];

        $hours = [];
        for ($h = 9; $h <= 20; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        $matrix = [];
        foreach ($days as $d) {
            $dayKey = (int) $d['key'];
            $matrix[$dayKey] = [];
            foreach ($hours as $hour) {
                $matrix[$dayKey][$hour] = null;
            }
        }

        $slots = WeeklyAvailability::query()
            ->where('active', true)
            ->where('operator_id', $user->id)
            ->get(['day_of_week', 'start_time', 'room_id']);

        foreach ($slots as $slot) {
            $dayKey = (int) $slot->day_of_week;
            $hour = Carbon::createFromFormat('H:i:s', $slot->start_time)->format('H:i');
            if (array_key_exists($dayKey, $matrix) && array_key_exists($hour, $matrix[$dayKey])) {
                $matrix[$dayKey][$hour] = (int) $slot->room_id;
            }
        }

        // 🔹 NEW: legenda dinamica per le stanze effettivamente usate
        $roomIds = $slots->pluck('room_id')->filter()->unique()->values();
        $rooms = Room::whereIn('id', $roomIds)->orderBy('name')->get(['id', 'name']);

        $alphabet = range('A', 'Z');
        $legend = []; // es: [ room_id => ['abbr' => 'A', 'name' => 'Sala Grande'] ]
        foreach ($rooms as $i => $room) {
            $legend[(int) $room->id] = [
                'abbr' => $alphabet[$i] ?? ('S' . ($i + 1)), // fallback oltre Z
                'name' => $room->name,
            ];
        }

        $hasAny = $slots->isNotEmpty();

        return view('operator.availability.show', [
            'operatorName' => $user->full_name,
            'days' => $days,
            'hours' => $hours,
            'matrix' => $matrix,
            'legend' => $legend,
            'hasAny' => $hasAny,
        ]);
    }
}
