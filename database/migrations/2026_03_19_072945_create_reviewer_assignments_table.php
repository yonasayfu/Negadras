<?php

use App\ReviewerAssignmentStatus;
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
        Schema::create('reviewer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default(ReviewerAssignmentStatus::Assigned->value);
            $table->timestamps();

            $table->index(['submission_id', 'reviewer_id', 'stage_id'], 'reviewer_assignments_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviewer_assignments');
    }
};
