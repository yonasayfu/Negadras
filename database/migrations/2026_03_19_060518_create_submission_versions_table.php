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
        Schema::create('submission_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->json('snapshot_json');
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_locked')->default(true);
            $table->timestamps();

            $table->unique(['submission_id', 'version_no']);
        });

        Schema::table('submissions', function (Blueprint $table): void {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('submission_versions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('submission_versions');
    }
};
