<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('netflix_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('email_uid', 255);
            $table->string('recipient', 255);
            $table->text('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('code', 500)->nullable();
            $table->dateTime('email_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('netflix_notifications');
    }
};
