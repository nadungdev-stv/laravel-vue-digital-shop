<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->string('product_name', 200);
            $table->string('image', 500)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2);
            $table->decimal('total_price', 15, 2)->storedAs('quantity * price');
            $table->text('account_delivered')->nullable()->comment('Thông tin tài khoản đã giao (JSON)');
            $table->text('customer_account_info')->nullable()->comment('Thông tin tài khoản khách hàng cung cấp (JSON)');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
