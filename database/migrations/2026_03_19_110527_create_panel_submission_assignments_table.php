<?php

use App\PanelSubmissionAssignmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_submission_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('session_id_optional')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default(PanelSubmissionAssignmentStatus::Assigned->value);
            $table->timestamps();

            $table->unique(['panel_id', 'submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_submission_assignments');
    }
};
