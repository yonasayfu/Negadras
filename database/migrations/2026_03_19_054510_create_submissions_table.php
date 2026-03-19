<?php

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Stage;
use App\SubmissionStatus;
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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Season::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Stage::class, 'current_stage_id')->nullable()->constrained('stages')->nullOnDelete();
            $table->foreignIdFor(Industry::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Applicant::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Organization::class)->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('solution_description')->nullable();
            $table->text('business_model')->nullable();
            $table->string('status')->default(SubmissionStatus::Draft->value);
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_public_after_approval')->default(false);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index(['applicant_id', 'status']);
            $table->index(['season_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
