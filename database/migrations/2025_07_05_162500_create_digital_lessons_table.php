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
        Schema::create('digital_lessons', function (Blueprint $table) {
            $table->id('digital_lesson_id');
            $table->string('title');
            $table->string('youtube_url');
            $table->decimal('price', 6, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_lessons');
    }
};
