<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Illuminate\Database\Schema\Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();

            $t->date('work_date');

            $t->dateTime('clock_in_at')->nullable();
            $t->dateTime('clock_out_at')->nullable();

            $t->dateTime('break_started_at')->nullable();
            $t->dateTime('break_ended_at')->nullable();

            // 休憩2
            $t->dateTime('break2_started_at')->nullable();
            $t->dateTime('break2_ended_at')->nullable();

            // 数値集計を持つ場合
            $t->integer('break_minutes')->nullable()->default(0);
            $t->integer('work_minutes')->nullable();

            $t->text('note')->nullable();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
