<?php

use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
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
        Schema::create('screening_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('eligibility_status')->nullable()->default(ScreeningEligibilityStatus::NeedsClarification->value);
            $table->string('recommendation')->nullable()->default(ScreeningRecommendation::Escalate->value);
            $table->unsignedSmallInteger('score_optional')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique('reviewer_assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screening_reviews');
    }
};
