<?php

use App\ScoreVisibilityAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_visibility_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_submission_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('action')->default(ScoreVisibilityAction::Hide->value);
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_visibility_events');
    }
};
