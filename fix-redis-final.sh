#!/bin/bash

# Final Redis fix with specific version
echo "🔧 Final Redis extension fix..."

# Stop and rebuild
echo "⏹️  Stopping containers..."
docker-compose down

echo "🗑️  Removing app container..."
docker-compose rm -f app

echo "📦 Rebuilding with Redis 5.3.7..."
docker-compose build --no-cache app

echo "🚀 Starting services..."
docker-compose up -d

# Wait for containers
echo "⏳ Waiting for containers to start..."
sleep 25

# Test Redis extension
echo "🔍 Testing Redis extension..."
if docker-compose exec app php -m | grep -q redis; then
    echo "✅ Redis extension installed successfully!"
    
    # Test database connection
    echo "🗄️  Testing database connection..."
    docker-compose exec app php artisan tinker --execute="echo 'Database OK';"
    
    # Reset database and run migrations
    echo "🔄 Resetting database..."
    docker-compose exec mysql mysql -u root -pSecurePassword123! -e "DROP DATABASE IF EXISTS digital_cloud_management; CREATE DATABASE digital_cloud_management;"
    
    echo "📋 Running migrations..."
    docker-compose exec app php artisan migrate:fresh --force
    
    # Optimize application
    echo "🔐 Optimizing application..."
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    
    # Test application
    echo "🌐 Testing application..."
    if curl -f http://localhost:8011 > /dev/null 2>&1; then
        echo "✅ SUCCESS! Application is working!"
        echo "🎯 Access: http://101.46.58.130:8011"
        echo "🗄️  Database: http://101.46.58.130:8080"
        echo "🔴 Redis UI: http://101.46.58.130:8081"
    else
        echo "❌ Application still has issues, checking logs..."
        docker-compose logs app | tail -15
    fi
else
    echo "❌ Redis extension still missing"
    echo "📋 Available modules:"
    docker-compose exec app php -m
    echo "🔍 Checking build logs..."
    docker-compose logs app | grep -i redis || echo "No Redis logs found"
fi

echo "📊 Container status:"
docker-compose ps
