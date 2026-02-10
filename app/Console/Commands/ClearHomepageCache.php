<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearHomepageCache extends Command
{
    protected $signature = 'cache:clear-homepage';
    protected $description = 'Clear homepage cache (products, categories, banners)';

    public function handle(): int
    {
        Cache::forget('homepage_data');
        Cache::forget('app_settings_all');
        Cache::forget('admin_pending_orders');
        Cache::forget('admin_low_stock');
        Cache::forget('admin_pending_reviews');
        Cache::forget('admin_unread_chats');

        $this->info('Homepage cache cleared successfully!');
        return Command::SUCCESS;
    }
}
