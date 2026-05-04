#!/bin/bash

echo "🔧 Fixing 502 Bad Gateway Error"
echo "================================="

# Check app container logs
echo "📋 Checking app container logs..."
docker-compose logs app --tail=20

# Restart app container
echo "🔄 Restarting app container..."
docker-compose restart app

# Wait for app to start
echo "⏳ Waiting for app container to start..."
sleep 15

# Check if app is running
echo "📋 Checking app container status..."
docker-compose ps app

# Test PHP-FPM connectivity
echo "🧪 Testing PHP-FPM..."
docker-compose exec app ps aux | grep php-fpm

# Test nginx to PHP-FPM connection
echo "🧪 Testing nginx to PHP-FPM connection..."
docker-compose exec nginx ping -c 3 app 2>/dev/null || echo "Cannot reach app container from nginx"

# Restart nginx as well
echo "🔄 Restarting nginx container..."
docker-compose restart nginx

# Wait for nginx to start
echo "⏳ Waiting for nginx to start..."
sleep 10

# Test application
echo "🧪 Testing application..."
docker-compose exec app curl -I http://localhost/

# Test from nginx perspective
echo "🧪 Testing from nginx..."
docker-compose exec nginx curl -I http://app/ 2>/dev/null || echo "Nginx cannot reach app"

echo ""
echo "✅ 502 error fix attempt complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
