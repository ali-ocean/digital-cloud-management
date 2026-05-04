#!/bin/bash

echo "🔧 Fixing PHP Memory Limit Issue"
echo "================================="

# Update PHP memory limit in php.ini
echo "📝 Updating PHP memory limit..."
docker-compose exec app bash -c "echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/custom.ini"

# Update Laravel memory limit in .env
echo "📝 Updating Laravel memory limit..."
docker-compose exec app bash -c "sed -i '/APP_DEBUG=/a MEMORY_LIMIT=512M' .env"

# Update composer memory limit
echo "📝 Updating composer memory limit..."
docker-compose exec app bash -c "echo 'export COMPOSER_MEMORY_LIMIT=-1' >> ~/.bashrc"

# Restart app container to apply PHP changes
echo "🔄 Restarting app container..."
docker-compose restart app

# Wait for restart
echo "⏳ Waiting for app to start..."
sleep 15

# Clear caches with higher memory
echo "🧹 Clearing caches..."
docker-compose exec app php -d memory_limit=512M artisan cache:clear
docker-compose exec app php -d memory_limit=512M artisan config:clear
docker-compose exec app php -d memory_limit=512M artisan route:clear
docker-compose exec app php -d memory_limit=512M artisan view:clear

# Test application
echo "🧪 Testing application..."
docker-compose exec app curl -I http://localhost/

# Test simple route
echo "🧪 Testing simple route..."
docker-compose exec app curl -s http://localhost/test 2>/dev/null || echo "Simple route failed"

echo ""
echo "✅ Memory limit fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
