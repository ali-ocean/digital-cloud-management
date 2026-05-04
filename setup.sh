#!/bin/bash

# Digital Cloud Management LaunchPad - Docker Setup Script
# This script sets up the project on Huawei VM using Docker Compose

set -e

echo "🚀 Setting up Digital Cloud Management LaunchPad on Huawei VM..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_status "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    rm get-docker.sh
    print_status "Docker installed successfully!"
else
    print_status "Docker is already installed!"
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_status "Installing Docker Compose..."
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    print_status "Docker Compose installed successfully!"
else
    print_status "Docker Compose is already installed!"
fi

# Create necessary directories
print_status "Creating necessary directories..."
mkdir -p mysql
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Set proper permissions
print_status "Setting proper permissions..."
chmod -R 777 storage
chmod -R 777 bootstrap/cache

# Generate application key
print_status "Generating application key..."
php artisan key:generate --force

# Create environment file
if [ ! -f .env ]; then
    print_status "Creating .env file..."
    cp .env.example .env
    
    # Update .env with Docker configuration
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
    sed -i 's/DB_DATABASE=\/Users\/usmanali\/Documents\/digital-cloud-management\/database\/database.sqlite/DB_HOST=mysql/' .env
    sed -i '/DB_DATABASE=laravel/a\DB_PORT=3306\nDB_DATABASE=digital_cloud_management\nDB_USERNAME=root\nDB_PASSWORD=SecurePassword123!/' .env
    
    # Add Redis configuration
    sed -i '/REDIS_HOST=127.0.0.1/c\REDIS_HOST=redis' .env
    sed -i '/REDIS_PASSWORD=null/a\REDIS_PORT=6379' .env
    
    # Add queue configuration
    sed -i '/QUEUE_CONNECTION=sync/c\QUEUE_CONNECTION=redis' .env
    sed -i '/CACHE_DRIVER=file/c\CACHE_DRIVER=redis' .env
    sed -i '/SESSION_DRIVER=file/c\SESSION_DRIVER=redis' .env
fi

# Build and start Docker containers
print_status "Building Docker containers..."
docker-compose build --no-cache

print_status "Starting Docker containers..."
docker-compose up -d

# Wait for MySQL to be ready
print_status "Waiting for MySQL to be ready..."
sleep 30

# Run database migrations
print_status "Running database migrations..."
docker-compose exec app php artisan migrate --force

# Clear and cache configuration
print_status "Optimizing application..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan view:cache

# Create storage link
print_status "Creating storage link..."
docker-compose exec app php artisan storage:link

# Show status
print_status "Checking container status..."
docker-compose ps

echo ""
print_status "🎉 Setup completed successfully!"
echo ""
echo "📋 Access Information:"
echo "  🌐 Application: http://localhost"
echo "  🗄️  phpMyAdmin: http://localhost:8080"
echo "  🔴 Redis UI: http://localhost:8081"
echo "  📧 MailHog: http://localhost:8025"
echo ""
echo "🔧 Management Commands:"
echo "  📊 View logs: docker-compose logs -f"
echo "  🔄 Restart: docker-compose restart"
echo "  🛑 Stop: docker-compose down"
echo "  🧹 Clean: docker-compose down -v"
echo ""
echo "📁 Important Files:"
echo "  ⚙️  Configuration: docker-compose.yml"
echo "  🔐 Environment: .env"
echo "  🌐 Nginx: nginx.conf"
echo ""
print_warning "Note: Make sure ports 80, 3306, 6379, 8080, 8081, and 8025 are open on your Huawei VM firewall!"
