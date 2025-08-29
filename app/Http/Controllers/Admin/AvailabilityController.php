<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyAvailability;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
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
                $matrix[$dayKey][$hour] = [
                    1 => [],
                    2 => [],
                ];
            }
        }

        $slots = WeeklyAvailability::query()
            ->where('active', true)
            ->with(['operator:id,first_name,last_name'])
            ->get(['operator_id', 'day_of_week', 'start_time', 'room_id']);

        foreach ($slots as $slot) {
            $dayKey = (int) $slot->day_of_week;
            $hour = Carbon::createFromFormat('H:i:s', $slot->start_time)->format('H:i');
            $room = (int) $slot->room_id;

            if (!isset($matrix[$dayKey][$hour][$room])) {
                continue;
            }

            $opName = trim(($slot->operator->first_name ?? '') . ' ' . ($slot->operator->last_name ?? ''));
            if ($opName === '') {
                $opName = 'Operatore #' . $slot->operator_id;
            }

            $matrix[$dayKey][$hour][$room][] = [
                'id' => (int) $slot->operator_id,
                'name' => $opName,
            ];
        }

        $rooms = [
            ['id' => 1, 'label' => 'Sala A'],
            ['id' => 2, 'label' => 'Sala B'],
        ];

        return view('admin.availability.index', [
            'days' => $days,
            'hours' => $hours,
            'rooms' => $rooms,
            'matrix' => $matrix,
        ]);
    }
}
