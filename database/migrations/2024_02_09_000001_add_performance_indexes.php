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
        // Cart table indexes
        Schema::table('cart', function (Blueprint $table) {
            $table->index('session_id');
            $table->index(['user_id', 'session_id']);
        });

        // Settings table index
        Schema::table('settings', function (Blueprint $table) {
            $table->index('setting_key');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });

        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->index('payment_status');
            $table->index('order_status');
            $table->index(['user_id', 'created_at']);
        });

        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('stock_quantity');
            $table->index(['status', 'featured']);
            $table->index(['status', 'created_at']);
        });

        // Product variants table indexes
        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id', 'is_main']);
            $table->index(['product_id', 'status']);
        });

        // Reviews table index
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('status');
        });

        // Chat messages table indexes
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index(['sender_type', 'is_read']);
            $table->index('session_id');
        });

        // Product gallery table index
        Schema::table('product_gallery', function (Blueprint $table) {
            $table->index(['product_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropIndex(['user_id', 'session_id']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['setting_key']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_read']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['order_status']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock_quantity']);
            $table->dropIndex(['status', 'featured']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'is_main']);
            $table->dropIndex(['product_id', 'status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['sender_type', 'is_read']);
            $table->dropIndex(['session_id']);
        });

        Schema::table('product_gallery', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'sort_order']);
        });
    }
};
