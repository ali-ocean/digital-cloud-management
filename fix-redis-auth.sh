#!/bin/bash

echo "🔧 Fixing Redis Authentication Issue"
echo "================================="

# Fix .env file with proper Redis configuration
echo "📝 Creating proper .env configuration..."
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

# Recreate config cache
echo "🔄 Recreating configuration cache..."
docker-compose exec app php artisan config:cache

# Test basic Laravel functionality
echo "🧪 Testing Laravel..."
docker-compose exec app php artisan --version

# Test routes
echo "🛣️ Testing routes..."
docker-compose exec app php artisan route:list --name=dashboard

# Restart queue worker to stop Redis errors
echo "🔄 Restarting queue worker..."
docker-compose restart queue

# Wait for services
echo "⏳ Waiting for services to stabilize..."
sleep 10

echo ""
echo "✅ Redis authentication fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
