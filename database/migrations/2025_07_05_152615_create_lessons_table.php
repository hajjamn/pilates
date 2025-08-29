<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('users', 'id')->onDelete('cascade');
            $table->dateTime('starts_at');
            $table->unsignedTinyInteger('max_clients')->default(7); // TODO: spostare su rooms/weekly_availabilities se serve
            $table->boolean('canceled')->default(false);
            $table->boolean('manual_override')->default(false); // NEW
            $table->timestamps();
            $table->softDeletes();

            // Indici utili per query e conflitti
            $table->index(['operator_id', 'starts_at'], 'idx_lessons_operator_starts_at');
            $table->index(['room_id', 'starts_at'], 'idx_lessons_room_starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
