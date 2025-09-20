<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use App\Models\WeeklyAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class WeeklyAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        //MAPPA GIORNI
        $D = ['lun' => 0, 'mar' => 1, 'mer' => 2, 'gio' => 3, 'ven' => 4, 'sab' => 5, 'dom' => 6];

        $rooms = Room::query()->pluck('id', 'name');
        $plan = [

            'ada.turco@gmail.com' => [
                ['days' => ['lun', 'mer', 'ven'], 'times' => ['09:00', '10:00', '11:00'], 'room' => 'Sala Reformer'],
                ['days' => ['mar', 'gio'], 'times' => ['13:00', '14:00'], 'room' => 'Sala Reformer'],
            ],

            'mariapiamanniello15@gmail.com' => [
                ['days' => ['lun'], 'times' => ['15:00'], 'room' => 'Sala Reformer'],
                ['days' => ['mar'], 'times' => ['10:00', '11:00'], 'room' => 'Sala Reformer'],
                ['days' => ['sab'], 'times' => ['09:00', '10:00'], 'room' => 'Sala Reformer'],
            ],

            'lucreziaviolante99@gmail.com' => [
                ['days' => ['gio'], 'times' => ['09:00', '10:00'], 'room' => 'Sala Reformer'],
            ],

            'giuliamariniello00@gmail.com' => [
                ['days' => ['lun', 'mer'], 'times' => ['09:00'], 'room' => 'Sala Chair'],
                ['days' => ['lun'], 'times' => ['18:00', '19:00'], 'room' => 'Sala Chair'],
                ['days' => ['mar', 'gio'], 'times' => ['15:00'], 'room' => 'Sala Reformer'],
                ['days' => ['mer'], 'times' => ['18:00', '19:00'], 'room' => 'Sala Reformer'], // <-- se non è corretta, rimuovi
            ],

            'circondatomariarita22@gmail.com' => [
                ['days' => ['lun', 'ven'], 'times' => ['18:00', '19:00', '20:00'], 'room' => 'Sala Reformer'],
                ['days' => ['mar', 'gio'], 'times' => ['18:00', '19:00', '20:00'], 'room' => 'Sala Reformer'],
                ['days' => ['mer'], 'times' => ['18:00', '19:00', '20:00'], 'room' => 'Sala Chair'],
            ],
        ];

        foreach (['Sala Reformer', 'Sala Chair'] as $roomName) {
            if (!$rooms->has($roomName)) {
                $this->command?->warn("Stanza mancante: {$roomName}. Esegui prima il RoomSeeder con i nomi corretti.");
            }
        }

        foreach ($plan as $email => $blocks) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->command?->warn("Operatore non trovato: {$email}. Salto.");
                continue;
            }

            foreach ($blocks as $b) {
                $roomId = $rooms[Arr::get($b, 'room')] ?? null;
                if (!$roomId) {
                    $this->command?->warn("Sala non trovata per {$email}: " . Arr::get($b, 'room') . ". Salto.");
                    continue;
                }

                foreach ($b['days'] as $dayKey) {
                    $dow = $D[$dayKey];

                    foreach ($b['times'] as $t) {
                        $start = Carbon::createFromTimeString($t . ':00');
                        $end = $start->copy()->addHour()->format('H:i:s');

                        WeeklyAvailability::updateOrCreate(
                            [
                                'operator_id' => $user->id,
                                'day_of_week' => $dow,
                                'start_time' => $start->format('H:i:s'),
                            ],
                            [
                                'end_time' => $end,
                                'room_id' => $roomId,
                                'active' => true,
                            ]
                        );
                    }
                }
            }
        }
    }
}
