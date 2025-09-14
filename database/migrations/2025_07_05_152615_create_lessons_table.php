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
            $table->boolean('manual_override')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Flag calcolato per “attivo”: non cancellata e non soft-deleted
            $table->boolean('is_active')
                ->storedAs('CASE WHEN deleted_at IS NULL AND canceled = 0 THEN 1 ELSE 0 END');

            // VINCOLI DI UNICITÀ: impediscono doppi slot per lezioni attive
            $table->unique(['room_id', 'starts_at', 'is_active'], 'uniq_lessons_room_start_active');
            $table->unique(['operator_id', 'starts_at', 'is_active'], 'uniq_lessons_operator_start_active');

            // (Opzionali) indici di supporto: utili se fai spesso ricerche per giorno/sala/op.
            $table->index(['room_id', 'starts_at'], 'idx_lessons_room_starts_at');
            $table->index(['operator_id', 'starts_at'], 'idx_lessons_operator_starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
