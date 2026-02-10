<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_stats', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->integer('access_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_stats');
    }
};
