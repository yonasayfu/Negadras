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
        Schema::create('ranking_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->decimal('aggregate_score', 8, 2);
            $table->unsignedInteger('rank_position');
            $table->string('tie_break_reason_optional')->nullable();
            $table->text('override_reason_optional')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['stage_id', 'competition_session_id', 'submission_id'],
                'ranking_snapshots_stage_session_submission_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_snapshots');
    }
};
