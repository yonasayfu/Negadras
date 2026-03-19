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
        Schema::create('session_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_session_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('summary');
            $table->text('quote_optional')->nullable();
            $table->string('quote_source_optional')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_highlights');
    }
};
