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
        Schema::create('lesson_users', function (Blueprint $table) {
            $table->foreignId('lesson_id')->constrained('lessons', 'id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->boolean('attended')->default(true);
            $table->foreignId('added_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null')->comment('Operator/admin/client that added the client');
            $table->foreignId('user_package_id')->nullable()->constrained('user_packages')->onDelete('set null')->comment('Packaged used for the reservation');
            $table->boolean('counted')->default(true)->comment('Indicates if lesson was deducted from user package');
            $table->timestamps();
            $table->softDeletes();
            $table->primary(['lesson_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_users');
    }
};
