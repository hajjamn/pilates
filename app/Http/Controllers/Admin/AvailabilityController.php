<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\WeeklyAvailability;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

        $rooms = [
            ['id' => 1, 'label' => 'Sala A'],
            ['id' => 2, 'label' => 'Sala B'],
        ];

        $weekParam = $request->query('week');
        if ($weekParam) {
            $weekStart = Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY);
        } else {
            $weekStart = Carbon::today()->next(Carbon::MONDAY);
        }
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();

        $dayDate = [];
        foreach ($days as $d) {
            $dayDate[$d['key']] = $weekStart->copy()->addDays($d['key'])->toDateString();
        }

        $matrix = [];
        foreach ($days as $d) {
            $dayKey = (int) $d['key'];
            $matrix[$dayKey] = [];
            foreach ($hours as $hour) {
                $matrix[$dayKey][$hour] = [1 => [], 2 => []];
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
            $name = trim(($slot->operator->first_name ?? '') . ' ' . ($slot->operator->last_name ?? '')) ?: ('Operatore #' . $slot->operator_id);
            $matrix[$dayKey][$hour][$room][] = ['id' => (int) $slot->operator_id, 'name' => $name];
        }

        $lessons = Lesson::query()
            ->whereBetween('starts_at', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->where('canceled', false)
            ->with(['operator:id,first_name,last_name'])
            ->get(['id', 'room_id', 'operator_id', 'starts_at']);

        $occupied = [];
        foreach ($lessons as $L) {
            $d = $L->starts_at->toDateString();
            $h = $L->starts_at->format('H:i');
            $r = (int) $L->room_id;
            $occupied[$d][$h][$r][] = $L->id;
        }

        $availKeySet = [];
        foreach ($slots as $slot) {
            $k = $slot->operator_id . '|' . $slot->room_id . '|' . $slot->day_of_week . '|' . Carbon::createFromFormat('H:i:s', $slot->start_time)->format('H:i:s');
            $availKeySet[$k] = true;
        }

        $uncovered = [];
        foreach ($lessons as $L) {
            $dow = $L->starts_at->dayOfWeekIso - 1;
            $startKey = $L->starts_at->format('H:i:00');
            $k = $L->operator_id . '|' . $L->room_id . '|' . $dow . '|' . $startKey;
            if (!isset($availKeySet[$k])) {
                $name = trim(($L->operator->first_name ?? '') . ' ' . ($L->operator->last_name ?? '')) ?: ('Operatore #' . $L->operator_id);
                $uncovered[] = [
                    'date' => $L->starts_at->toDateString(),
                    'time' => $L->starts_at->format('H:i'),
                    'room_id' => (int) $L->room_id,
                    'operator_id' => (int) $L->operator_id,
                    'operator_name' => $name,
                    'lesson_id' => (int) $L->id,
                ];
            }
        }

        $conflictMap = [];
        $availabilityConflicts = [];
        foreach ($days as $d) {
            $dk = $d['key'];
            foreach ($hours as $h) {
                foreach ([1, 2] as $r) {
                    $ops = $matrix[$dk][$h][$r];
                    if (count($ops) > 1) {
                        $conflictMap[$dk][$h][$r] = true;
                        $availabilityConflicts[] = [
                            'date' => $dayDate[$dk],
                            'time' => $h,
                            'room_id' => $r,
                            'operators' => array_map(fn($o) => $o['name'], $ops),
                        ];
                    }
                }
            }
        }

        $healthCounts = [
            'uncovered' => count($uncovered),
            'conflicts' => count($availabilityConflicts),
            'occupied' => collect($occupied)->flatten(2)->count(),
        ];

        return view('admin.availability.index', [
            'days' => $days,
            'hours' => $hours,
            'rooms' => $rooms,
            'matrix' => $matrix,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'prev_week' => $prevWeek,
            'next_week' => $nextWeek,
            'day_date' => $dayDate,
            'occupied' => $occupied,
            'uncovered' => $uncovered,
            'availability_conflicts' => $availabilityConflicts,
            'conflict_map' => $conflictMap,
            'health_counts' => $healthCounts,
        ]);
    }

    public function showGenerate(Request $request)
    {
        $daysLabels = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
        $hours = [];
        for ($h = 9; $h <= 20; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }
        $rooms = [
            ['id' => 1, 'label' => 'Sala A'],
            ['id' => 2, 'label' => 'Sala B'],
        ];

        $from = $request->query('from');
        $to = $request->query('to');

        if (!$from || !$to) {
            $start = Carbon::today()->next(Carbon::MONDAY);
            $end = $start->copy()->addDays(6);
            $from = $start->toDateString();
            $to = $end->toDateString();
        }

        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $period = CarbonPeriod::create($fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay());
        $dates = [];
        foreach ($period as $d) {
            $dates[$d->toDateString()] = true;
        }

        $avail = WeeklyAvailability::query()
            ->where('active', true)
            ->whereIn('day_of_week', collect(array_keys($dates))->map(fn($dt) => Carbon::parse($dt)->dayOfWeekIso - 1)->unique())
            ->with(['operator:id,first_name,last_name'])
            ->get(['operator_id', 'day_of_week', 'start_time', 'room_id']);

        $availByDow = $avail->groupBy('day_of_week');

        $lessons = Lesson::query()
            ->whereBetween('starts_at', [$fromDate, $toDate])
            ->where('canceled', false)
            ->get(['id', 'room_id', 'operator_id', 'starts_at']);

        $existsByExact = [];
        $existsByRoomTime = [];
        foreach ($lessons as $L) {
            $keyExact = $L->operator_id . '|' . $L->room_id . '|' . $L->starts_at->toDateTimeString();
            $keyRoomTime = $L->room_id . '|' . $L->starts_at->toDateTimeString();
            $existsByExact[$keyExact] = $L->id;
            $existsByRoomTime[$keyRoomTime][] = $L->id;
        }

        $previewByDay = [];
        $summary = [
            'create_count' => 0,
            'already_exists_count' => 0,
            'conflicts_availability' => 0,
            'conflicts_existing' => 0,
        ];
        $warnings = [
            'room_conflicts' => [],
            'existing_lessons' => [],
        ];

        foreach (array_keys($dates) as $dateStr) {
            $date = Carbon::parse($dateStr);
            $dow = $date->dayOfWeekIso - 1;

            $previewByDay[$dateStr] = [];
            foreach ($hours as $hstr) {
                $previewByDay[$dateStr][$hstr] = [
                    1 => ['operators' => [], 'already_exists' => [], 'has_existing_lesson' => false],
                    2 => ['operators' => [], 'already_exists' => [], 'has_existing_lesson' => false],
                ];
            }

            $slots = $availByDow->get($dow, collect());
            foreach ($slots as $slot) {
                $hour = Carbon::createFromFormat('H:i:s', $slot->start_time)->format('H:i');
                $startsAt = Carbon::parse($dateStr . ' ' . $slot->start_time);
                $room = (int) $slot->room_id;
                $name = trim(($slot->operator->first_name ?? '') . ' ' . ($slot->operator->last_name ?? '')) ?: ('Operatore #' . $slot->operator_id);

                $keyExact = $slot->operator_id . '|' . $room . '|' . $startsAt->toDateTimeString();
                $keyRoomTime = $room . '|' . $startsAt->toDateTimeString();

                if (isset($existsByExact[$keyExact])) {
                    $previewByDay[$dateStr][$hour][$room]['already_exists'][] = ['operator_id' => (int) $slot->operator_id];
                    $summary['already_exists_count']++;
                } else {
                    $previewByDay[$dateStr][$hour][$room]['operators'][] = ['id' => (int) $slot->operator_id, 'name' => $name];
                    $summary['create_count']++;
                }

                if (!empty($existsByRoomTime[$keyRoomTime])) {
                    $previewByDay[$dateStr][$hour][$room]['has_existing_lesson'] = true;
                }
            }

            foreach ($hours as $hstr) {
                foreach ([1, 2] as $roomId) {
                    $ops = $previewByDay[$dateStr][$hstr][$roomId]['operators'];
                    if (count($ops) > 1) {
                        $summary['conflicts_availability']++;
                        $warnings['room_conflicts'][] = [
                            'date' => $dateStr,
                            'time' => $hstr,
                            'room_id' => $roomId,
                            'operators' => array_map(fn($o) => $o['name'], $ops),
                        ];
                    }
                    if ($previewByDay[$dateStr][$hstr][$roomId]['has_existing_lesson']) {
                        $summary['conflicts_existing']++;
                        $keyRoomTime = $roomId . '|' . Carbon::parse($dateStr . ' ' . $hstr . ':00')->toDateTimeString();
                        $warnings['existing_lessons'][] = [
                            'date' => $dateStr,
                            'time' => $hstr,
                            'room_id' => $roomId,
                            'lesson_ids' => $existsByRoomTime[$keyRoomTime] ?? [],
                        ];
                    }
                }
            }
        }

        return view('admin.availability.generate', [
            'from' => $from,
            'to' => $to,
            'days_labels' => $daysLabels,
            'hours' => $hours,
            'rooms' => $rooms,
            'summary' => $summary,
            'preview_by_day' => $previewByDay,
            'warnings' => $warnings,
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $roomMaxById = Room::pluck('max_clients', 'id');

        $fromDate = Carbon::createFromFormat('Y-m-d', $data['from'])->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $data['to'])->endOfDay();
        $period = CarbonPeriod::create($fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay());

        $hours = [];
        for ($h = 9; $h <= 20; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        $avail = WeeklyAvailability::query()
            ->where('active', true)
            ->whereIn('day_of_week', collect(iterator_to_array($period))->map(fn($d) => Carbon::parse($d)->dayOfWeekIso - 1)->unique())
            ->get(['operator_id', 'day_of_week', 'start_time', 'room_id']);

        $availByDow = $avail->groupBy('day_of_week');

        $lessons = Lesson::query()
            ->whereBetween('starts_at', [$fromDate, $toDate])
            ->where('canceled', false)
            ->get(['id', 'room_id', 'operator_id', 'starts_at']);

        $existsByExact = [];
        $existsByRoomTime = [];
        foreach ($lessons as $L) {
            $keyExact = $L->operator_id . '|' . $L->room_id . '|' . $L->starts_at->toDateTimeString();
            $keyRoomTime = $L->room_id . '|' . $L->starts_at->toDateTimeString();
            $existsByExact[$keyExact] = $L->id;
            $existsByRoomTime[$keyRoomTime][] = $L->id;
        }

        $createdIds = [];
        $alreadyExists = [];
        $skippedConflicts = [];

        foreach (CarbonPeriod::create($fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()) as $date) {
            $dateStr = $date->toDateString();
            $dow = $date->dayOfWeekIso - 1;

            $slots = $availByDow->get($dow, collect());
            foreach ($slots as $slot) {
                $startsAt = Carbon::parse($dateStr . ' ' . $slot->start_time);
                $room = (int) $slot->room_id;

                $keyExact = $slot->operator_id . '|' . $room . '|' . $startsAt->toDateTimeString();
                if (isset($existsByExact[$keyExact])) {
                    $alreadyExists[] = [
                        'date' => $dateStr,
                        'time' => Carbon::createFromFormat('H:i:s', $slot->start_time)->format('H:i'),
                        'room_id' => $room,
                        'operator_id' => (int) $slot->operator_id,
                    ];
                    continue;
                }

                $keyRoomTime = $room . '|' . $startsAt->toDateTimeString();
                if (!empty($existsByRoomTime[$keyRoomTime])) {
                    $skippedConflicts[] = [
                        'date' => $dateStr,
                        'time' => Carbon::createFromFormat('H:i:s', $slot->start_time)->format('H:i'),
                        'room_id' => $room,
                        'operator_id' => (int) $slot->operator_id,
                        'existing_lesson_ids' => $existsByRoomTime[$keyRoomTime],
                    ];
                    continue;
                }

                $maxClients = (int) ($roomMaxById[$room] ?? 0);

                $lesson = Lesson::create([
                    'room_id' => $room,
                    'operator_id' => (int) $slot->operator_id,
                    'starts_at' => $startsAt,
                    'max_clients' => $maxClients,
                    'canceled' => false,
                    'manual_override' => false,
                ]);

                $createdIds[] = $lesson->id;

                $existsByExact[$keyExact] = $lesson->id;
                $existsByRoomTime[$keyRoomTime][] = $lesson->id;
            }
        }

        return redirect()
            ->route('admin.availability.generate.form', ['from' => $data['from'], 'to' => $data['to']])
            ->with([
                'result' => [
                    'created_ids' => $createdIds,
                    'already_exists' => $alreadyExists,
                    'skipped_conflicts' => $skippedConflicts,
                ]
            ]);
    }
}
