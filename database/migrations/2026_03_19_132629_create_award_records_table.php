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
        Schema::create('award_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ranking_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('award_type');
            $table->unsignedInteger('rank_position')->nullable();
            $table->decimal('prize_value_optional', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('award_records');
    }
};
