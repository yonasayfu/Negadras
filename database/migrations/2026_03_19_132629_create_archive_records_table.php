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
        Schema::create('archive_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ranking_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->string('archive_status');
            $table->string('public_visibility');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'stage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_records');
    }
};
