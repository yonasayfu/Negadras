<?php

use App\JudgeCommentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panel_submission_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('session_id_optional')->nullable();
            $table->string('comment_type')->default(JudgeCommentType::Private->value);
            $table->text('content');
            $table->boolean('is_archived')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_comments');
    }
};
