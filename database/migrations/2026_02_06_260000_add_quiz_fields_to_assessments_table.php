<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedInteger('total_mark')->nullable();
            $table->unsignedInteger('pass_mark')->nullable();
            $table->unsignedInteger('retake_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['total_mark', 'pass_mark', 'retake_attempts']);
        });
    }
};
