#!/bin/bash

# Debug 500 Internal Server Error
echo "🔍 Debugging 500 Internal Server Error..."

# Check Laravel logs
echo "📋 Checking Laravel logs..."
docker-compose exec app tail -n 50 storage/logs/laravel.log

echo ""
echo "🌐 Checking Nginx logs..."
docker-compose exec nginx tail -n 20 /var/log/nginx/error.log

echo ""
echo "🐘 Checking PHP-FPM logs..."
docker-compose exec app tail -n 20 /var/log/php8.2-fpm.log

echo ""
echo "🔧 Testing database connection..."
docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo();"

echo ""
echo "📊 Checking application status..."
docker-compose exec app php artisan about

echo ""
echo "🗄️  Running migrations to ensure database is ready..."
docker-compose exec app php artisan migrate --force

echo ""
echo "🔐 Checking environment variables..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache

echo ""
echo "🌐 Testing application response..."
curl -v http://localhost:8011 2>&1 | head -20
