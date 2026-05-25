<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedInteger('access_code')->nullable();
            $table->unsignedInteger('retake_attempts')->default(0);
            $table->boolean('allow_late')->default(false);
            $table->unsignedInteger('late_mark')->nullable();
            $table->timestamp('late_due_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['access_code', 'retake_attempts', 'allow_late', 'late_mark', 'late_due_at']);
        });
    }
};
