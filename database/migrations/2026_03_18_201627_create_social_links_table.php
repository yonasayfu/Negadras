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
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('platform');
            $table->string('url', 2048);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['applicant_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
