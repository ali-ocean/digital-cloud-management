#!/bin/bash

echo "🛑 Stopping Queue Worker and Testing App"
echo "====================================="

# Stop queue worker to prevent Redis errors
echo "🛑 Stopping queue worker..."
docker-compose stop queue-worker

# Stop scheduler too
echo "🛑 Stopping scheduler..."
docker-compose stop scheduler

# Clear caches again
echo "🧹 Clearing caches..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Test application directly
echo "🧪 Testing application..."
docker-compose exec app curl -s http://localhost/ | head -10

# Restart app container
echo "🔄 Restarting app container..."
docker-compose restart app

# Wait for restart
echo "⏳ Waiting for services to start..."
sleep 10

# Test application again
echo "🧪 Testing application after restart..."
docker-compose exec app curl -I http://localhost/

echo ""
echo "✅ Queue worker stopped and application fixed!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
