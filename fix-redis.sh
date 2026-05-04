#!/bin/bash

# Fix Redis extension issue
echo "🔧 Fixing Redis extension issue..."

# Stop containers
echo "⏹️  Stopping containers..."
docker-compose down

# Remove app container
echo "🗑️  Removing app container..."
docker-compose rm -f app

# Rebuild with Redis extension
echo "📦 Rebuilding app container with Redis extension..."
docker-compose build --no-cache app

# Start services
echo "🚀 Starting services..."
docker-compose up -d

# Wait for services to start
echo "⏳ Waiting for services to start..."
sleep 20

# Test Redis connection
echo "🔍 Testing Redis connection..."
docker-compose exec app php -m | grep redis

# Test database connection
echo "🗄️  Testing database connection..."
docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';"

# Clear and cache
echo "🔐 Optimizing application..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan route:cache

# Test application
echo "🌐 Testing application..."
if curl -f http://localhost:8011 > /dev/null 2>&1; then
    echo "✅ Application is working!"
    echo "🎯 Access: http://YOUR_VM_IP:8011"
else
    echo "❌ Still having issues, checking logs..."
    docker-compose logs app | tail -10
fi

echo "📊 Container status:"
docker-compose ps
