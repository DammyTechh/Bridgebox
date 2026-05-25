<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lessons') && !Schema::hasTable('topics')) {
            Schema::rename('lessons', 'topics');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('topics') && !Schema::hasTable('lessons')) {
            Schema::rename('topics', 'lessons');
        }
    }
};
