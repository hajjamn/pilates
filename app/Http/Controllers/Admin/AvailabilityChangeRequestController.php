<?php

namespace App\Http\Controllers\Admin;

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
        $requests = AvailabilityChangeRequest::query()
            ->with(['operator:id,first_name,last_name'])
            ->orderByRaw("FIELD(status, 'pending','approved','rejected')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.availability.requests.index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, AvailabilityChangeRequest $acr)
    {
        $operator = $acr->operator;
        $daysLabels = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];

        $hours = [];
        for ($h = 9; $h <= 20; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        // Stato ATTUALE a DB
        $current = [];
        foreach (range(0, 6) as $d) {
            $current[$d] = [];
            foreach ($hours as $hstr) {
                $current[$d][$hstr] = null;
            }
        }

        $currSlots = WeeklyAvailability::query()
            ->where('active', true)
            ->where('operator_id', $acr->operator_id)
            ->get(['day_of_week', 'start_time', 'room_id']);

        foreach ($currSlots as $s) {
            $d = (int) $s->day_of_week;
            $h = Carbon::createFromFormat('H:i:s', $s->start_time)->format('H:i');
            $current[$d][$h] = (int) $s->room_id;
        }

        // Stato PROPOSTO dal payload (DINAMICO su tutte le rooms esistenti)
        $proposed = [];
        foreach (range(0, 6) as $d) {
            $proposed[$d] = [];
            foreach ($hours as $hstr) {
                $proposed[$d][$hstr] = null;
            }
        }

        // set room validi
        $validRoomIds = Room::query()->pluck('id')->map(fn($v) => (int) $v)->all();
        $validRoomSet = array_flip($validRoomIds);

        $daysPayload = $acr->payload['days'] ?? [];
        foreach ($daysPayload as $dayStr => $slots) {
            $d = (int) $dayStr;
            foreach ((array) $slots as $slot) {
                $h = $slot['start'] ?? null;
                $r = isset($slot['room_id']) ? (int) $slot['room_id'] : null;
                if ($h && in_array($h, $hours, true) && $r !== null && isset($validRoomSet[$r])) {
                    $proposed[$d][$h] = $r;
                }
            }
        }

        // Legenda dinamica: union di stanze usate in current + proposed
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
        $legend = []; // [id => ['abbr'=>'A','name'=>'Sala Foo']]
        foreach ($rooms as $i => $room) {
            $legend[(int) $room->id] = [
                'abbr' => $alphabet[$i] ?? ('S' . ($i + 1)),
                'name' => $room->name,
            ];
        }

        // Diff
        $diffByDay = []; // [day][hour] => ['status','from','to','from_id','to_id']
        $summary = ['added' => 0, 'removed' => 0, 'changed' => 0, 'unchanged' => 0];

        foreach (range(0, 6) as $d) {
            $diffByDay[$d] = [];
            foreach ($hours as $h) {
                $cur = $current[$d][$h];
                $prop = $proposed[$d][$h];

                if ($cur === null && $prop === null) {
                    $status = 'unchanged';
                } elseif ($cur === null && $prop !== null) {
                    $status = 'added';
                } elseif ($cur !== null && $prop === null) {
                    $status = 'removed';
                } else {
                    $status = ($cur === $prop) ? 'unchanged' : 'changed';
                }

                $summary[$status]++;

                $diffByDay[$d][$h] = [
                    'status' => $status,
                    'from' => $cur ? ($legend[$cur]['name'] ?? ('Sala ' . $cur)) : '—',
                    'to' => $prop ? ($legend[$prop]['name'] ?? ('Sala ' . $prop)) : '—',
                    'from_id' => $cur,
                    'to_id' => $prop,
                ];
            }
        }

        $operatorName = trim(($operator->first_name ?? '') . ' ' . ($operator->last_name ?? '')) ?: ('Operatore #' . $acr->operator_id);

        return view('admin.availability.requests.show', [
            'acr' => $acr,
            'operatorName' => $operatorName,
            'days_labels' => $daysLabels,
            'hours' => $hours,
            'legend' => $legend,
            'diff' => $diffByDay,
            'summary' => $summary,
        ]);
    }

    public function approve(Request $request, AvailabilityChangeRequest $acr)
    {
        if ($acr->status !== 'pending') {
            return back()->with('error', 'La richiesta non è più in stato pending.');
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $operatorId = (int) $acr->operator_id;
        $payloadDays = $acr->payload['days'] ?? [];

        // ✅ room_id validi dal DB
        $validRoomIds = Room::query()->pluck('id')->map(fn($v) => (int) $v)->all();
        $validRoomSet = array_flip($validRoomIds);

        // desiderata: chiave "d|HH:MM" => room_id (solo se valido)
        $desired = [];
        foreach ($payloadDays as $dayStr => $slots) {
            $d = (int) $dayStr;
            foreach ((array) $slots as $slot) {
                $h = $slot['start'] ?? null;
                $r = isset($slot['room_id']) ? (int) $slot['room_id'] : null;
                if ($h && preg_match('/^\d{2}:\d{2}$/', $h) && $r !== null && isset($validRoomSet[$r])) {
                    $desired[$d . '|' . $h] = $r;
                }
            }
        }

        // attuale: tutte le weekly_availabilities dell’operatore (non cancellate)
        $rows = WeeklyAvailability::where('operator_id', $operatorId)->get();

        $current = [];  // chiave "d|HH:MM" => model
        foreach ($rows as $row) {
            $key = (int) $row->day_of_week . '|' . Carbon::createFromFormat('H:i:s', $row->start_time)->format('H:i');
            $current[$key] = $row;
        }

        $created = 0;
        $updated = 0;
        $deactivated = 0;

        // crea/aggiorna desiderati
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
                    $updated++;
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
                $created++;
            }
        }

        // disattiva quelli attuali che NON sono più desiderati
        foreach ($current as $key => $row) {
            if (!array_key_exists($key, $desired) && $row->active) {
                $row->active = false;
                $row->save();
                $deactivated++;
            }
        }

        // chiudi la richiesta
        $acr->status = 'approved';
        $acr->reviewed_by = $request->user()->id;
        $acr->reviewed_at = now();
        $acr->applied_at = now();
        $acr->reason = $request->input('reason');
        $acr->save();

        return redirect()
            ->route('admin.availability.requests.show', $acr)
            ->with('status', "Richiesta approvata. Creati: $created, aggiornati: $updated, disattivati: $deactivated.");
    }

    public function reject(Request $request, AvailabilityChangeRequest $acr)
    {
        if ($acr->status !== 'pending') {
            return back()->with('error', 'La richiesta non è più in stato pending.');
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $acr->status = 'rejected';
        $acr->reviewed_by = $request->user()->id;
        $acr->reviewed_at = now();
        $acr->reason = $request->input('reason');
        $acr->save();

        return redirect()
            ->route('admin.availability.requests.show', $acr)
            ->with('status', 'Richiesta rifiutata.');
    }

}
