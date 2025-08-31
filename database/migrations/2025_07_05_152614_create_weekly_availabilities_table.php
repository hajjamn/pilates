<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_availabilities', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('operator_id')->constrained('users', 'id')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week')->comment('0=Monday, 6=Sunday');
            $table->time('start_time');
            $table->time('end_time')->nullable(); // ← niente ->after()
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['operator_id', 'day_of_week', 'start_time'], 'uniq_operator_day_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_availabilities');
    }
};
