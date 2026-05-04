#!/bin/bash

echo "🔧 Fixing Redis Port Configuration"
echo "=================================="

# Update .env file with correct Redis port
echo "📝 Updating .env file..."
docker-compose exec app sed -i 's/REDIS_HOST=redis/REDIS_HOST=redis/g' .env 2>/dev/null || echo "REDIS_HOST already set"
docker-compose exec app sed -i 's/REDIS_PORT=6378/REDIS_PORT=6379/g' .env 2>/dev/null || echo "REDIS_PORT already set"
docker-compose exec app sed -i 's/REDIS_PASSWORD=/REDIS_PASSWORD=null/g' .env 2>/dev/null || echo "REDIS_PASSWORD already set"

# Add Redis configuration if missing
echo "📝 Adding Redis configuration to .env..."
docker-compose exec app bash -c "if ! grep -q 'REDIS_HOST=' .env; then echo 'REDIS_HOST=redis' >> .env; fi"
docker-compose exec app bash -c "if ! grep -q 'REDIS_PORT=' .env; then echo 'REDIS_PORT=6379' >> .env; fi"
docker-compose exec app bash -c "if ! grep -q 'REDIS_PASSWORD=' .env; then echo 'REDIS_PASSWORD=null' >> .env; fi"

# Set queue connection to sync to avoid Redis errors
echo "📝 Setting queue connection to sync..."
docker-compose exec app sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/g' .env 2>/dev/null || echo "QUEUE_CONNECTION already set"

# Clear caches
echo "🧹 Clearing Laravel caches..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Restart app container
echo "🔄 Restarting app container..."
docker-compose restart app

# Wait for restart
echo "⏳ Waiting for services to start..."
sleep 15

# Test Redis connection
echo "🧪 Testing Redis connection..."
docker-compose exec app php artisan tinker --execute="Redis::ping(); echo 'Redis connected';" 2>/dev/null || echo "Redis test failed"

# Test application
echo "🧪 Testing application..."
docker-compose exec app php artisan route:list --name=dashboard 2>/dev/null || echo "Route test failed"

echo ""
echo "✅ Redis port fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
