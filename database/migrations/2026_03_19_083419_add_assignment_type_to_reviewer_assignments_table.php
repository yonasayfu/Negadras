<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviewer_assignments', function (Blueprint $table) {
            $table->string('assignment_type', 32)
                ->default('screening')
                ->after('stage_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviewer_assignments', function (Blueprint $table) {
            $table->dropColumn('assignment_type');
        });
    }
};
