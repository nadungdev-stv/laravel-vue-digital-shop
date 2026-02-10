<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sqlFile = database_path('sql/DBweb_ban_tai_khoan.sql');

        if (!File::exists($sqlFile)) {
            throw new \Exception("SQL file not found: {$sqlFile}");
        }

        $sqlContent = File::get($sqlFile);

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Remove CREATE TABLE statements to avoid conflicts with migrations
            // Pattern matches: CREATE TABLE `...` (...) ENGINE=...;
            $sqlContent = preg_replace('/CREATE TABLE\s+`[^`]+`\s*\(.*?\)\s*ENGINE=[^;]+;/s', '', $sqlContent);

            // Remove ALTER TABLE statements (indexes/constraints) to avoid conflicts
            $sqlContent = preg_replace('/ALTER TABLE\s+`[^`]+`\s+[^;]+;/s', '', $sqlContent);

            DB::unprepared($sqlContent);
        } catch (\Exception $e) {
            \Log::error("Failed to execute SQL dump: " . $e->getMessage());
            throw $e;
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncate all tables that have data inserted
        $tables = [
            'accounts_stock',
            'banners',
            'cart',
            'categories',
            'coupons',
            'products',
            'product_categories',
            'product_gallery',
            'product_variants',
            'settings',
            'transactions',
            'users',
            'visitor_stats',
            'wallet_transactions',
            'wishlist',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
