<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // SQL dump is int NOT NULL, not foreignId constrained
            $table->enum('type', ['deposit', 'withdraw', 'order', 'refund']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('description')->nullable();
            $table->integer('reference_id')->nullable(); // SQL dump is int
            $table->timestamp('created_at')->useCurrent();
            // Note: SQL dump doesn't have updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
