<?php

use App\ConflictOfInterestStatus;
use App\ConflictOfInterestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conflict_of_interest_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('session_id_optional')->nullable();
            $table->string('conflict_type')->default(ConflictOfInterestType::SelfDeclared->value);
            $table->text('description');
            $table->timestamp('declared_at')->nullable();
            $table->string('status')->default(ConflictOfInterestStatus::Active->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflict_of_interest_declarations');
    }
};
