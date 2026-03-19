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
        Schema::table('screening_reviews', function (Blueprint $table) {
            $table->json('eligibility_checklist')->nullable()->after('eligibility_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screening_reviews', function (Blueprint $table) {
            $table->dropColumn('eligibility_checklist');
        });
    }
};
