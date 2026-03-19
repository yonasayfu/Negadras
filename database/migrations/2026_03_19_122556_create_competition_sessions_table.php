<?php

use App\CompetitionSessionStatus;
use App\CompetitionSessionType;
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
        Schema::create('competition_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('session_type')->default(CompetitionSessionType::Pitch->value);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('broadcasted_at')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default(CompetitionSessionStatus::Scheduled->value);
            $table->string('etv_video_url_optional')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('scores_revealed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_sessions');
    }
};
