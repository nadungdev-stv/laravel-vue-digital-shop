#!/bin/bash

# Laravel Production Optimization Script
# Run this after deploying to production

echo "🚀 Starting Laravel Production Optimization..."

# 1. Clear all caches first
echo "📦 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Run database migrations
echo "📊 Running migrations..."
php artisan migrate --force

# 3. Cache configuration
echo "⚙️ Caching configuration..."
php artisan config:cache

# 4. Cache routes
echo "🛤️ Caching routes..."
php artisan route:cache

# 5. Cache views
echo "👁️ Caching views..."
php artisan view:cache

# 6. Optimize autoloader
echo "📚 Optimizing autoloader..."
composer dump-autoload --optimize --no-dev

# 7. Laravel optimize command
echo "⚡ Running Laravel optimize..."
php artisan optimize

# 8. Build frontend assets for production
echo "🎨 Building frontend assets..."
npm run build

echo ""
echo "✅ Production optimization complete!"
echo ""
echo "📋 Checklist for production .env file:"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "   - CACHE_STORE=file (or redis for better performance)"
echo "   - SESSION_DRIVER=file (or redis for better performance)"
echo ""
