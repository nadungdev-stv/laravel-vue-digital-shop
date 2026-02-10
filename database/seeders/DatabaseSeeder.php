<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data is now imported via the 2026_01_28_000000_insert_data_from_sql migration.
        // No need to seed test data to avoid duplicates.
    }
}
