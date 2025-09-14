<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Sala Reformer', 'max_clients' => 7, 'description' => 'Sala principale'],
            ['name' => 'Sala Chair', 'max_clients' => 5, 'description' => 'Sala secondaria'],
        ];

        foreach ($rooms as $r) {
            Room::updateOrCreate(
                ['name' => $r['name']],
                [
                    'max_clients' => $r['max_clients'],
                    'description' => $r['description'],
                ]
            );
        }
    }
}
