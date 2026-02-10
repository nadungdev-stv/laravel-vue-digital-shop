<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id')->nullable()->index();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('tags', 500)->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->text('features')->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('image', 500)->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('sold_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->boolean('featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->enum('delivery_type', ['account', 'email_only', 'customer_account'])->default('account');
            $table->text('delivery_instructions')->nullable();
            $table->text('warranty_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
