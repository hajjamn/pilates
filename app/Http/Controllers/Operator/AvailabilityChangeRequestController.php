<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityChangeRequest;
use App\Models\WeeklyAvailability;
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

        $current = [];
        $proposed = [];
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

        $daysPayload = $acr->payload['days'] ?? [];
        foreach ($daysPayload as $dayStr => $slots) {
            $d = (int) $dayStr;
            foreach ((array) $slots as $s) {
                $h = $s['start'] ?? null;
                $r = $s['room_id'] ?? null;
                if ($h && in_array($h, $hours, true) && in_array($r, [1, 2], true)) {
                    $proposed[$d][$h] = (int) $r;
                }
            }
        }

        $legend = [1 => 'Sala A', 2 => 'Sala B'];
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
                    'from' => $cur ? ($legend[$cur] ?? ('Sala ' . $cur)) : '—',
                    'to' => $prop ? ($legend[$prop] ?? ('Sala ' . $prop)) : '—',
                ];
            }
        }

        return view('operator.availability.requests.show', [
            'acr' => $acr,
            'days_labels' => $daysLabels,
            'hours' => $hours,
            'diff' => $diff,
            'summary' => $summary,
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
            $dayKey = (int) $slot->day_of_week;                 // 0..6
            $hour = substr($slot->start_time, 0, 5);          // 'HH:MM'
            if (!isset($hoursSet[$hour])) {
                continue;
            }         // fuori fascia? salta
            $matrix[$dayKey][$hour] = (int) $slot->room_id;     // 1 o 2
        }

        $effectiveFrom = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        return view('operator.availability.requests.create', [
            'operatorName' => $user->full_name ?? ($user->first_name . ' ' . $user->last_name),
            'days' => $days,
            'hours' => $hours,
            'matrix' => $matrix,
            'effective_from' => $effectiveFrom,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'slots' => ['nullable', 'array'],
        ]);

        $daysPayload = [];
        foreach ((array) ($data['slots'] ?? []) as $dayStr => $hoursMap) {
            $day = (int) $dayStr;
            if ($day < 0 || $day > 6 || !is_array($hoursMap))
                continue;
            foreach ($hoursMap as $hour => $roomId) {
                if (!in_array($hour, array_map(fn($h) => sprintf('%02d:00', $h), range(9, 20)), true))
                    continue;
                if ($roomId === '' || $roomId === null)
                    continue;
                $room = (int) $roomId;
                if (!in_array($room, [1, 2], true))
                    continue;
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

        $desired = [];
        foreach ($daysPayload as $dayStr => $slots) {
            $d = (int) $dayStr;
            foreach ((array) $slots as $slot) {
                $h = $slot['start'] ?? null;
                $r = $slot['room_id'] ?? null;
                if ($h && preg_match('/^\d{2}:\d{2}$/', $h) && in_array($r, [1, 2], true)) {
                    $desired[$d . '|' . $h] = (int) $r;
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
                if ($needsUpdate) {
                    $row->save();
                }
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
