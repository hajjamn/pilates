<?php

namespace Database\Seeders;

use App\Models\Room;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::create([
            'name' => 'Sala A',
            'description' => 'Stanza principale',
        ]);

        Room::create([
            'name' => 'Sala B',
            'description' => 'Stanza secondaria',
        ]);
    }
}