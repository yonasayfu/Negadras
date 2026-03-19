<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('override_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            $table->foreignId('competition_session_id')->nullable()->constrained('competition_sessions')->nullOnDelete();
            $table->foreignId('panel_submission_assignment_id')->nullable()->constrained('panel_submission_assignments')->nullOnDelete();
            $table->foreignId('reviewer_assignment_id')->nullable()->constrained('reviewer_assignments')->nullOnDelete();
            $table->string('event_type');
            $table->text('reason')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('override_events');
    }
};
