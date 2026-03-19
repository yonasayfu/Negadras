<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panel_submission_assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('session_id_optional')->nullable();
            $table->foreignId('panel_id_optional')->nullable()->constrained('panels')->nullOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained()->cascadeOnDelete();
            $table->decimal('score_value', 8, 2)->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_secret')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['panel_submission_assignment_id', 'judge_id', 'rubric_criterion_id'],
                'score_entries_unique_judge_criterion_assignment'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_entries');
    }
};
