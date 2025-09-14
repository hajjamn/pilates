<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityChangeRequest;
use App\Models\WeeklyAvailability;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $requests = AvailabilityChangeRequest::query()
            ->forOperator($user->id)
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('operator.availability.requests.index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, AvailabilityChangeRequest $acr)
    {
        $user = $request->user();
        if ($acr->operator_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        $daysLabels = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];

        $hours = [];
        for ($h = 9; $h <= 20; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        $current = $proposed = [];
        foreach (range(0, 6) as $d) {
            $current[$d] = $proposed[$d] = [];
            foreach ($hours as $hstr) {
                $current[$d][$hstr] = $proposed[$d][$hstr] = null;
            }
        }

        $curr = WeeklyAvailability::where('active', true)
            ->where('operator_id', $acr->operator_id)
            ->get(['day_of_week', 'start_time', 'room_id']);

        foreach ($curr as $s) {
            $d = (int) $s->day_of_week;
            $h = Carbon::createFromFormat('H:i:s', $s->start_time)->format('H:i');
            $current[$d][$h] = (int) $s->room_id;
        }

        // NEW: valid room ids + proposta dinamica
        $validRoomIds = Room::query()->pluck('id')->map(fn($v) => (int) $v)->all();
        $validRoomSet = array_flip($validRoomIds);

        $daysPayload = $acr->payload['days'] ?? [];
        foreach ($daysPayload as $dayStr => $slots) {
            $d = (int) $dayStr;
            foreach ((array) $slots as $s) {
                $h = $s['start'] ?? null;
                $r = isset($s['room_id']) ? (int) $s['room_id'] : null;
                if ($h && in_array($h, $hours, true) && $r !== null && isset($validRoomSet[$r])) {
                    $proposed[$d][$h] = $r;
                }
            }
        }

        // NEW: legenda dinamica (union current+proposed)
        $usedRoomIds = [];
        foreach (range(0, 6) as $d) {
            foreach ($hours as $h) {
                if ($current[$d][$h])
                    $usedRoomIds[$current[$d][$h]] = true;
                if ($proposed[$d][$h])
                    $usedRoomIds[$proposed[$d][$h]] = true;
            }
        }
        $usedRoomIds = array_keys($usedRoomIds);

        $rooms = Room::whereIn('id', $usedRoomIds)->orderBy('name')->get(['id', 'name']);
        $alphabet = range('A', 'Z');
        $legend = []; // [id => ['abbr'=>'A','name'=>'Sala ...']]
        foreach ($rooms as $i => $room) {
            $legend[(int) $room->id] = [
                'abbr' => $alphabet[$i] ?? ('S' . ($i + 1)),
                'name' => $room->name,
            ];
        }

        $diff = [];
        $summary = ['added' => 0, 'removed' => 0, 'changed' => 0, 'unchanged' => 0];

        foreach (range(0, 6) as $d) {
            $diff[$d] = [];
            foreach ($hours as $h) {
                $cur = $current[$d][$h];
                $prop = $proposed[$d][$h];

                if ($cur === null && $prop === null)
                    $st = 'unchanged';
                elseif ($cur === null && $prop !== null)
                    $st = 'added';
                elseif ($cur !== null && $prop === null)
                    $st = 'removed';
                else
                    $st = ($cur === $prop) ? 'unchanged' : 'changed';

                $summary[$st]++;

                $diff[$d][$h] = [
                    'status' => $st,
                    'from' => $cur ? ($legend[$cur]['name'] ?? ('Sala ' . $cur)) : '—',
                    'to' => $prop ? ($legend[$prop]['name'] ?? ('Sala ' . $prop)) : '—',
                ];
            }
        }

        return view('operator.availability.requests.show', [
            'acr' => $acr,
            'days_labels' => $daysLabels,
            'hours' => $hours,
            'diff' => $diff,
            'summary' => $summary,
            'legend' => $legend,
        ]);
    }

    public function create(Request $request)
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
        $hoursSet = array_flip($hours);

        $matrix = [];
        foreach ([0, 1, 2, 3, 4, 5, 6] as $dayKey) {
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
            $hour = substr($slot->start_time, 0, 5); // 'HH:MM'
            if (!isset($hoursSet[$hour]))
                continue;
            $matrix[$dayKey][$hour] = (int) $slot->room_id;
        }

        $rooms = Room::query()
            // ->where('active', true) // ← se hai una colonna 'active', scommenta
            ->orderBy('name')
            ->get(['id', 'name']);

        $alphabet = range('A', 'Z');
        $legend = []; // [ room_id => ['abbr'=>'A','name'=>'Sala X'] ]
        foreach ($rooms as $i => $room) {
            $legend[(int) $room->id] = [
                'abbr' => $alphabet[$i] ?? ('S' . ($i + 1)), // ⬅️ fallback con "S"
                'name' => $room->name,
            ];
        }

        $effectiveFrom = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        return view('operator.availability.requests.create', [
            'operatorName' => $user->full_name ?? ($user->first_name . ' ' . $user->last_name),
            'days' => $days,
            'hours' => $hours,
            'matrix' => $matrix,
            'effective_from' => $effectiveFrom,
            'legend' => $legend,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'slots' => ['nullable', 'array'],
        ]);

        // NEW: set di room_id validi
        $validRoomIds = Room::query()->pluck('id')->map(fn($v) => (int) $v)->all();
        $validRoomSet = array_flip($validRoomIds);

        $hoursWhitelist = array_map(fn($h) => sprintf('%02d:00', $h), range(9, 20));

        $daysPayload = [];
        foreach ((array) ($data['slots'] ?? []) as $dayStr => $hoursMap) {
            $day = (int) $dayStr;
            if ($day < 0 || $day > 6 || !is_array($hoursMap))
                continue;

            foreach ($hoursMap as $hour => $roomId) {
                if (!in_array($hour, $hoursWhitelist, true))
                    continue;
                if ($roomId === '' || $roomId === null)
                    continue;

                $room = (int) $roomId;
                if (!isset($validRoomSet[$room]))
                    continue; // scarta id sala non valido

                $daysPayload[(string) $day][] = ['start' => $hour, 'room_id' => $room];
            }
        }

        $payload = ['days' => $daysPayload];

        $acr = AvailabilityChangeRequest::create([
            'operator_id' => $user->id,
            'status' => $user->hasRole('admin') ? 'approved' : 'pending',
            'effective_from' => Carbon::createFromFormat('Y-m-d', $data['effective_from'])->toDateString(),
            'payload' => $payload,
            'reviewed_by' => $user->hasRole('admin') ? $user->id : null,
            'reviewed_at' => $user->hasRole('admin') ? now() : null,
            'applied_at' => $user->hasRole('admin') ? now() : null,
            'reason' => $user->hasRole('admin') ? 'Auto-approvazione admin' : null,
        ]);

        if ($user->hasRole('admin')) {
            $this->applyAvailabilityChange($acr);
            return redirect()->route('operator.availability.requests.show', $acr)
                ->with('status', 'Modifiche applicate subito (auto-approvate).');
        }

        return redirect()->route('operator.availability.requests.index')
            ->with('status', 'Richiesta inviata: in attesa di approvazione.');
    }


    private function applyAvailabilityChange(AvailabilityChangeRequest $acr): void
    {
        $operatorId = (int) $acr->operator_id;
        $daysPayload = $acr->payload['days'] ?? [];

        // NEW: set di room_id validi
        $validRoomIds = Room::query()->pluck('id')->map(fn($v) => (int) $v)->all();
        $validRoomSet = array_flip($validRoomIds);

        $desired = [];
        foreach ($daysPayload as $dayStr => $slots) {
            $d = (int) $dayStr;
            foreach ((array) $slots as $slot) {
                $h = $slot['start'] ?? null;
                $r = isset($slot['room_id']) ? (int) $slot['room_id'] : null;
                if ($h && preg_match('/^\d{2}:\d{2}$/', $h) && $r !== null && isset($validRoomSet[$r])) {
                    $desired[$d . '|' . $h] = $r;
                }
            }
        }

        $rows = WeeklyAvailability::where('operator_id', $operatorId)->get();
        $current = [];
        foreach ($rows as $row) {
            $key = (int) $row->day_of_week . '|' . Carbon::createFromFormat('H:i:s', $row->start_time)->format('H:i');
            $current[$key] = $row;
        }

        foreach ($desired as $key => $roomId) {
            [$dStr, $hStr] = explode('|', $key, 2);
            $d = (int) $dStr;
            $startTime = $hStr . ':00';
            $endTime = Carbon::createFromFormat('H:i:s', $startTime)->addHour()->format('H:i:s');

            if (isset($current[$key])) {
                $row = $current[$key];
                $needsUpdate = false;
                if ((int) $row->room_id !== $roomId) {
                    $row->room_id = $roomId;
                    $needsUpdate = true;
                }
                if (!$row->active) {
                    $row->active = true;
                    $needsUpdate = true;
                }
                if ($row->end_time !== $endTime) {
                    $row->end_time = $endTime;
                    $needsUpdate = true;
                }
                if ($needsUpdate)
                    $row->save();
            } else {
                WeeklyAvailability::create([
                    'operator_id' => $operatorId,
                    'day_of_week' => $d,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'room_id' => $roomId,
                    'active' => true,
                ]);
            }
        }

        foreach ($current as $key => $row) {
            if (!array_key_exists($key, $desired) && $row->active) {
                $row->active = false;
                $row->save();
            }
        }
    }
}
