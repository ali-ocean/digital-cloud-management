#!/bin/bash

echo "🔧 Final Routes and Views Fix"
echo "============================="

# Check if dashboard view exists
echo "📋 Checking dashboard view..."
docker-compose exec app ls -la resources/views/dashboard.blade.php 2>/dev/null || echo "Dashboard view missing"

# Check if app layout exists
echo "📋 Checking app layout..."
docker-compose exec app ls -la resources/views/layouts/app.blade.php 2>/dev/null || echo "App layout missing"

# Clear all caches again
echo "🧹 Clearing all caches..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Recreate all caches
echo "🔄 Recreating caches..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Test routes list
echo "🛣️ Testing routes list..."
docker-compose exec app php artisan route:list

# Test basic route
echo "🧪 Testing basic route..."
docker-compose exec app php artisan route:list --name=/

# Test Laravel serve
echo "🌐 Testing Laravel serve..."
docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000 &
SERVE_PID=$!
sleep 5
kill $SERVE_PID 2>/dev/null

# Restart app container
echo "🔄 Restarting app container..."
docker-compose restart app

# Wait for restart
echo "⏳ Waiting for services to start..."
sleep 15

# Test application access
echo "🧪 Testing application access..."
docker-compose exec app curl -s http://localhost/ | head -20 || echo "Local test failed"

echo ""
echo "✅ Final routes fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
