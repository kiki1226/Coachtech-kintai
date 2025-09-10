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
        Schema::create('work_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->time('work_start')->nullable();
            $t->time('work_end')->nullable();
            $t->unsignedSmallInteger('break_default')->default(60);
            $t->time('night_start')->default('22:00:00');
            $t->time('night_end')->default('05:00:00');
            $t->unsignedTinyInteger('round_unit')->default(1);
            $t->enum('round_mode', ['round','ceil','floor'])->default('round');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('work_rules');
    }
};
