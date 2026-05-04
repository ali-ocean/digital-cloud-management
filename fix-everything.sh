#!/bin/bash

# Complete fix for all issues
echo "🔧 Complete fix for Redis and Database issues..."

# Step 1: Fix Redis extension
echo "📦 Step 1: Rebuilding with Redis extension..."
docker-compose down
docker-compose rm -f app
docker-compose build --no-cache app
docker-compose up -d

# Wait for containers to start
echo "⏳ Waiting for containers to start..."
sleep 20

# Step 2: Test Redis extension
echo "🔍 Step 2: Testing Redis extension..."
if docker-compose exec app php -m | grep -q redis; then
    echo "✅ Redis extension is installed!"
else
    echo "❌ Redis extension still missing, checking logs..."
    docker-compose logs app | tail -10
    exit 1
fi

# Step 3: Fix database migration order
echo "🗄️ Step 3: Fixing database migration order..."

# Drop and recreate database to fix foreign key issues
echo "🔄 Resetting database..."
docker-compose exec mysql mysql -u root -pSecurePassword123! -e "DROP DATABASE IF EXISTS digital_cloud_management; CREATE DATABASE digital_cloud_management;"

# Run migrations in correct order
echo "📋 Running migrations..."
docker-compose exec app php artisan migrate:fresh --force

# Step 4: Clear and cache everything
echo "🔐 Step 4: Optimizing application..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan view:cache

# Step 5: Test application
echo "🌐 Step 5: Testing application..."
if curl -f http://localhost:8011 > /dev/null 2>&1; then
    echo "✅ Application is working!"
    echo "🎯 Access: http://101.46.58.130:8011"
    echo "🗄️  Database: http://101.46.58.130:8080"
    echo "🔴 Redis UI: http://101.46.58.130:8081"
else
    echo "❌ Still having issues, checking logs..."
    docker-compose logs app | tail -20
fi

echo "📊 Final container status:"
docker-compose ps
