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
        Schema::create('digital_lesson_users', function (Blueprint $table) {
            $table->foreignId('digital_lesson_id')->constrained('digital_lessons', 'digital_lesson_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamp('unlocked_at')->useCurrent();
            $table->softDeletes();
            $table->primary(['digital_lesson_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_lesson_users');
    }
};
