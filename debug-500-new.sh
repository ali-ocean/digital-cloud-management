#!/bin/bash

echo "🔍 Debugging 500 Error - Digital Cloud Management"
echo "=========================================="

# Check if containers are running
echo "📋 Checking container status..."
docker-compose ps

echo ""
echo "📋 Checking Laravel logs..."
docker-compose exec app tail -50 storage/logs/laravel.log 2>/dev/null || echo "No Laravel log file found"

echo ""
echo "📋 Checking Nginx error logs..."
docker-compose exec app tail -20 /var/log/nginx/error.log 2>/dev/null || echo "No Nginx error log found"

echo ""
echo "📋 Checking PHP-FPM logs..."
docker-compose exec app tail -20 /var/log/php8.2-fpm.log 2>/dev/null || echo "No PHP-FPM log found"

echo ""
echo "📋 Testing Laravel configuration..."
docker-compose exec app php artisan config:cache 2>/dev/null || echo "Failed to cache config"

echo ""
echo "📋 Checking storage permissions..."
docker-compose exec app ls -la storage/ 2>/dev/null || echo "Cannot access storage directory"

echo ""
echo "📋 Checking if vendor directory exists..."
docker-compose exec app ls -la vendor/ 2>/dev/null || echo "Vendor directory missing"

echo ""
echo "📋 Testing Laravel environment..."
docker-compose exec app php artisan --version 2>/dev/null || echo "Laravel not working"

echo ""
echo "📋 Testing database connection..."
docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected';" 2>/dev/null || echo "Database connection failed"

echo ""
echo "📋 Checking if routes are cached..."
docker-compose exec app php artisan route:cache 2>/dev/null || echo "Failed to cache routes"

echo ""
echo "📋 Testing direct PHP execution..."
docker-compose exec app php -r "echo 'PHP working';" 2>/dev/null || echo "PHP not working"

echo ""
echo "📋 Checking if .env file exists..."
docker-compose exec app cat .env 2>/dev/null | head -5 || echo "No .env file found"

echo ""
echo "🔧 Fixing common issues..."
docker-compose exec app chmod -R 755 storage/ bootstrap/cache/ 2>/dev/null || echo "Failed to set permissions"
docker-compose exec app chown -R www-data:www-data storage/ bootstrap/cache/ 2>/dev/null || echo "Failed to set ownership"
docker-compose exec app php artisan cache:clear 2>/dev/null || echo "Failed to clear cache"
docker-compose exec app php artisan config:clear 2>/dev/null || echo "Failed to clear config"
docker-compose exec app php artisan route:clear 2>/dev/null || echo "Failed to clear routes"
docker-compose exec app php artisan view:clear 2>/dev/null || echo "Failed to clear views"

echo ""
echo "🔄 Restarting services..."
docker-compose restart app

echo ""
echo "⏳ Waiting for services to start..."
sleep 10

echo ""
echo "✅ Debug complete. Try accessing the application now."
echo "🌐 URL: http://101.46.58.130:8011"
