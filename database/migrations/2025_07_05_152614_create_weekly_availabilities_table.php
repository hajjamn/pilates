<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weekly_availabilities', function (Blueprint $table) {
            $table->id('weekly_availability_id');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade')->comment('Operator');
            $table->unsignedTinyInteger('day_of_week')->comment('0=Monday, 6=Sunday');
            $table->time('start_time');
            $table->foreignId('room_id')->constrained('rooms', 'room_id')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_availabilities');
    }
};
