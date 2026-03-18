<?php

use App\Models\Applicant;
use App\Models\Organization;
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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Applicant::class)->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('role_title');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'is_primary_contact']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
