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
        Schema::create('requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 勤怠と紐付ける場合がある
            $t->foreignId('attendance_id')->nullable()
                ->constrained()->nullOnDelete();

            $t->enum('type', ['attendance_correction','leave','overtime']);
            $t->enum('status', ['draft','pending','approved','rejected','canceled'])
                ->default('pending');

            $t->timestamp('from_at')->nullable();
            $t->timestamp('to_at')->nullable();

            // ★ これを追加（単日申請用、検索用に index も付ける）
            $t->date('target_date')->nullable()->index();

            $t->timestamp('approved_at')->nullable();
            $t->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $t->text('reason')->nullable();

            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
