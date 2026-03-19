<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('submissions')
            ->where('status', 'screening_rejected')
            ->update([
                'status' => 'rejected',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('submissions')
            ->where('status', 'rejected')
            ->update([
                'status' => 'screening_rejected',
            ]);
    }
};
