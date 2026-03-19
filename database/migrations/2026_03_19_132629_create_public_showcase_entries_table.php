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
        Schema::create('public_showcase_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_id_optional')->nullable()->constrained('media')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('visibility_status');
            $table->text('summary');
            $table->string('winner_label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_showcase_entries');
    }
};
