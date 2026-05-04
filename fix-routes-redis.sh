#!/bin/bash

echo "🔧 Fixing Routes and Redis Issues"
echo "================================="

# Fix Redis connection first
echo "📝 Fixing Redis configuration..."
docker-compose exec app bash -c "cat > .env << 'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:Ck5cNttDOYX3LnX0Ab2X3jNFzhVA6/tcLkCd8qlwetc=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=digital_cloud_management
DB_USERNAME=digital_cloud_user
DB_PASSWORD=SecurePassword123!

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=\"hello@example.com\"
MAIL_FROM_NAME=\"\${APP_NAME}\"
EOF"

# Clear all caches
echo "🧹 Clearing all caches..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan config:cache

# Check routes
echo "🛣️ Checking routes..."
docker-compose exec app php artisan route:list --name=dashboard

# Test basic Laravel
echo "🧪 Testing Laravel basic functionality..."
docker-compose exec app php artisan tinker --execute="echo 'Laravel is working';"

# Restart all services to ensure clean state
echo "🔄 Restarting all services..."
docker-compose restart app nginx redis mysql

# Wait for services
echo "⏳ Waiting for services to start..."
sleep 20

# Test application
echo "🧪 Testing application access..."
docker-compose exec app curl -I http://localhost/ 2>/dev/null || echo "Local test failed"

echo ""
echo "✅ Routes and Redis fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
