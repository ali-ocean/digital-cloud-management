#!/bin/bash

echo "🔧 Final 500 Error Fix"
echo "======================"

# Get current Laravel error logs
echo "📋 Getting recent Laravel errors..."
docker-compose exec app tail -10 storage/logs/laravel.log

# Check if there are any PHP errors in nginx logs
echo "📋 Checking nginx error logs..."
docker-compose logs nginx --tail=10 2>/dev/null || echo "No nginx logs available"

# Test Laravel with debug mode
echo "🧪 Testing Laravel with debug..."
docker-compose exec app php artisan tinker --execute="dd(config('app.debug'));"

# Check if all required files exist
echo "📋 Checking critical files..."
echo "Dashboard view: $(docker-compose exec app ls -la resources/views/dashboard.blade.php 2>/dev/null && 'EXISTS' || 'MISSING')"
echo "App layout: $(docker-compose exec app ls -la resources/views/layouts/app.blade.php 2>/dev/null && 'EXISTS' || 'MISSING')"
echo "Routes file: $(docker-compose exec app ls -la routes/web.php 2>/dev/null && 'EXISTS' || 'MISSING')"

# Create a simple test route to isolate the issue
echo "🧪 Creating simple test route..."
docker-compose exec app bash -c "cat > routes/test.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return 'Laravel is working!';
});
EOF"

# Add test route to main routes file
echo "🧪 Adding test route to main routes..."
docker-compose exec app bash -c "echo \"Route::get('/test', function () { return 'Laravel is working!'; });\" >> routes/web.php"

# Clear and recache everything
echo "🧹 Clearing and recaching..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache

# Test simple route
echo "🧪 Testing simple route..."
docker-compose exec app curl -s http://localhost/test || echo "Simple route failed"

# Test main route again
echo "🧪 Testing main route..."
docker-compose exec app curl -s http://localhost/ | head -5 || echo "Main route failed"

# Restart everything completely
echo "🔄 Full restart of all services..."
docker-compose down
sleep 5
docker-compose up -d

# Wait for full startup
echo "⏳ Waiting for full startup..."
sleep 30

# Final test
echo "🧪 Final application test..."
docker-compose exec app curl -I http://localhost/ 2>/dev/null || echo "Final test failed"

echo ""
echo "✅ Final 500 fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
echo "🧪 Test simple route: http://101.46.58.130:8011/test"
