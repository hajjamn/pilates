<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\Room;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Reformer',
            'Cadillac',
            'Wunda Chair',
            'Ladder Barrel',
            'Spine Corrector',
            'Jumpboard',
            'Springboard',
            'Pilates Ring',
            'Magic Circle',
            'Foam Roller',
            'Leg Press',
            'Lat Machine',
            'Chest Press',
            'Pectoral Machine',
            'Shoulder Press',
            'Cable Crossover',
            'Smith Machine',
            'Rowing Machine',
            'Pulley Tower',
            'Abdominal Crunch Machine',
        ];

        foreach ($types as $type) {
            Machine::create([
                'name' => $type,
                'description' => 'Qui va la descrizione',
                'room_id' => Room::inRandomOrder()->first()->id,
            ]);
        }
    }
}
