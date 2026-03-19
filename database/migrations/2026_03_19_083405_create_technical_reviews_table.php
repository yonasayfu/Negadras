<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('innovation_score_optional')->nullable();
            $table->unsignedTinyInteger('feasibility_score_optional')->nullable();
            $table->unsignedTinyInteger('execution_score_optional')->nullable();
            $table->unsignedTinyInteger('market_score_optional')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('risk_note')->nullable();
            $table->string('recommendation', 32);
            $table->timestamps();
            $table->timestamp('submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_reviews');
    }
};
