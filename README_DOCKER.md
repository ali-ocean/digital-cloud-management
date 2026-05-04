# Digital Cloud Management LaunchPad - Docker Setup Guide

This guide will help you set up the Digital Cloud Management LaunchPad on your Huawei VM using Docker Compose.

## 🚀 Quick Setup

### Prerequisites
- Huawei VM with Ubuntu 20.04+ or CentOS 8+
- At least 2GB RAM and 20GB disk space
- Root or sudo access
- Internet connection

### One-Command Setup
```bash
# Clone or extract the project files
cd digital-cloud-management

# Make setup script executable
chmod +x setup.sh

# Run the setup script
./setup.sh
```

## 📋 Manual Setup Steps

### 1. Install Docker & Docker Compose
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create necessary directories
mkdir -p mysql storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
chmod -R 777 storage bootstrap/cache
```

### 3. Start Services
```bash
# Build and start containers
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Optimize application
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Create storage link
docker-compose exec app php artisan storage:link
```

## 🌐 Access Points

After setup, you can access the application at:

- **Main Application**: http://YOUR_VM_IP
- **phpMyAdmin**: http://YOUR_VM_IP:8080
  - Username: `root`
  - Password: `SecurePassword123!`
- **Redis Commander**: http://YOUR_VM_IP:8081
- **MailHog**: http://YOUR_VM_IP:8025

## 🔧 Management Commands

### Docker Compose Commands
```bash
# View running containers
docker-compose ps

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql

# Restart services
docker-compose restart

# Stop all services
docker-compose down

# Stop and remove volumes
docker-compose down -v

# Rebuild containers
docker-compose build --no-cache
```

### Laravel Commands
```bash
# Run artisan commands inside container
docker-compose exec app php artisan list

# Clear cache
docker-compose exec app php artisan cache:clear

# Run migrations
docker-compose exec app php artisan migrate

# Create new migration
docker-compose exec app php artisan make:migration create_new_table

# Queue worker status
docker-compose exec app php artisan queue:failed
```

## 🔒 Security Configuration

### Update Default Passwords
Edit `docker-compose.yml` to change these default values:

```yaml
# MySQL password
- MYSQL_ROOT_PASSWORD=YourSecurePassword
- MYSQL_PASSWORD=YourSecurePassword

# Application key
- APP_KEY=base64:YourGeneratedKeyHere
```

### Firewall Configuration
```bash
# Open required ports on Huawei VM
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS (if needed)
sudo ufw allow 8080/tcp  # phpMyAdmin
sudo ufw allow 8081/tcp  # Redis Commander
sudo ufw allow 8025/tcp  # MailHog

# Enable firewall
sudo ufw enable
```

## 📁 Project Structure

```
digital-cloud-management/
├── app/                    # Laravel application code
├── bootstrap/              # Bootstrap files
├── config/                 # Configuration files
├── database/               # Database migrations and seeds
├── docker-compose.yml      # Docker Compose configuration
├── Dockerfile              # PHP container definition
├── nginx.conf              # Nginx configuration
├── supervisord.conf        # Supervisor configuration
├── storage/                # Application storage
├── vendor/                 # Composer dependencies
└── .env                    # Environment variables
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Container Won't Start
```bash
# Check logs
docker-compose logs app

# Rebuild container
docker-compose down
docker-compose build --no-cache app
docker-compose up -d
```

#### 2. Database Connection Failed
```bash
# Check MySQL container
docker-compose logs mysql

# Restart MySQL
docker-compose restart mysql

# Wait 30 seconds and try again
sleep 30
docker-compose exec app php artisan migrate
```

#### 3. Permission Issues
```bash
# Fix storage permissions
sudo chmod -R 777 storage bootstrap/cache

# Recreate storage link
docker-compose exec app php artisan storage:link
```

#### 4. Queue Not Working
```bash
# Check queue worker
docker-compose logs queue-worker

# Restart queue worker
docker-compose restart queue-worker
```

### Health Checks
```bash
# Check if all services are running
docker-compose ps

# Test application
curl http://localhost

# Test database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

## 🔄 Backup and Restore

### Backup Database
```bash
# Export database
docker-compose exec mysql mysqldump -u root -pSecurePassword123! digital_cloud_management > backup.sql

# Copy backup file
docker cp digital-cloud-management-mysql:/backup.sql ./backup.sql
```

### Restore Database
```bash
# Copy backup to container
docker cp ./backup.sql digital-cloud-management-mysql:/backup.sql

# Restore database
docker-compose exec mysql mysql -u root -pSecurePassword123! digital_cloud_management < backup.sql
```

## 📈 Monitoring

### Resource Usage
```bash
# View container resource usage
docker stats

# View disk usage
docker system df

# Clean up unused images
docker system prune -a
```

### Application Logs
```bash
# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# Nginx logs
docker-compose exec nginx tail -f /var/log/nginx/access.log

# MySQL logs
docker-compose exec mysql tail -f /var/log/mysql/error.log
```

## 🚀 Production Deployment

For production deployment, consider these additional steps:

1. **SSL Certificate**: Configure HTTPS with Let's Encrypt
2. **Domain Name**: Point your domain to the Huawei VM
3. **Security**: Update all default passwords and secrets
4. **Backup**: Set up automated backups
5. **Monitoring**: Implement monitoring and alerting
6. **Scaling**: Consider load balancing for high traffic

## 📞 Support

If you encounter any issues:

1. Check the troubleshooting section above
2. Review Docker logs: `docker-compose logs`
3. Verify all ports are open on your Huawei VM
4. Ensure sufficient system resources

## 🔄 Updates

To update the application:

```bash
# Pull latest code
git pull origin main

# Rebuild containers
docker-compose build --no-cache

# Restart services
docker-compose up -d

# Run any new migrations
docker-compose exec app php artisan migrate --force
```
