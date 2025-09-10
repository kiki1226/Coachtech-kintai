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
       Schema::table('users', function (Blueprint $t) {
            $t->string('employee_code')->nullable()->unique();
            $t->foreignId('dept_id')->nullable()->constrained('departments');
            $t->foreignId('work_rule_id')->nullable()->constrained('work_rules');
            $t->enum('role', ['employee','manager','hr','admin'])->default('employee');
            $t->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['dept_id']);
            $t->dropForeign(['work_rule_id']);
            $t->dropColumn(['employee_code','dept_id','work_rule_id','role','is_active']);
        });
    }
};
