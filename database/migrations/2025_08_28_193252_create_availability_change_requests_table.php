<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availability_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('effective_from');

            // settimana proposta: giorni -> array di slot { start:"HH:MM", room_id?:int }
            $table->json('payload');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reason')->nullable();

            $table->timestamp('applied_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['operator_id', 'status']);
            $table->index('effective_from');
            $table->index(['operator_id', 'effective_from', 'status'], 'acr_op_eff_stat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_change_requests');
    }
};
