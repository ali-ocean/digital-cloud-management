#!/bin/bash

# Complete fix for vendor directory issue
echo "🔧 Complete vendor directory fix..."

# Stop all containers first
echo "⏹️  Stopping all containers..."
docker-compose down

# Remove the app container to force rebuild
echo "🗑️  Removing app container..."
docker-compose rm -f app

# Rebuild the app container with the fixed Dockerfile
echo "📦 Rebuilding app container with fixed Dockerfile..."
docker-compose build --no-cache app

# Start the app container only first to verify vendor
echo "🚀 Starting app container only..."
docker-compose up -d app

# Wait for container to start
echo "⏳ Waiting for app container to start..."
sleep 15

# Check if vendor directory exists now
echo "🔍 Checking vendor directory..."
if docker-compose exec app ls -la vendor/ > /dev/null 2>&1; then
    echo "✅ Vendor directory found!"
    echo "📁 Vendor contents:"
    docker-compose exec app ls -la vendor/
else
    echo "❌ Vendor directory still missing, installing manually..."
    # Install composer dependencies manually
    docker-compose exec app composer install --no-dev --optimize-autoloader --no-interaction
fi

# Now start all services
echo "🔄 Starting all services..."
docker-compose up -d

# Wait for all services to be ready
echo "⏳ Waiting for all services to start..."
sleep 20

# Test the application
echo "🌐 Testing application..."
if curl -f http://localhost:8011 > /dev/null 2>&1; then
    echo "✅ Application is working!"
else
    echo "❌ Application still has issues, checking logs..."
    docker-compose logs app | tail -10
fi

echo "🎯 Try accessing: http://YOUR_VM_IP:8011"
echo "📊 Check status with: docker-compose ps"
