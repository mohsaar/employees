<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['branches', 'departments'] as $table) {
            if (Schema::hasTable($table)) {
                continue;
            }

            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name', 100)->unique();
                $t->timestamps();
            });
        }

        if (Schema::hasTable('job_titles') && ! Schema::hasColumn('job_titles', 'name')) {
            Schema::table('job_titles', function (Blueprint $table) {
                $table->string('name', 100)->unique()->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['job_titles', 'departments', 'branches'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
