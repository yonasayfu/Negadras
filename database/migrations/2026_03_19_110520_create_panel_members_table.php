<?php

use App\PanelRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->string('role_in_panel')->default(PanelRole::Member->value);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();

            $table->unique(['panel_id', 'judge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_members');
    }
};
