#!/bin/bash

# Quick fix for missing vendor directory issue
echo "🔧 Fixing vendor directory issue..."

# Rebuild the app container with proper vendor installation
echo "📦 Rebuilding app container..."
docker-compose build --no-cache app

# Restart the services
echo "🔄 Restarting services..."
docker-compose up -d

# Wait for containers to start
echo "⏳ Waiting for containers to start..."
sleep 10

# Check if vendor directory exists
echo "🔍 Checking vendor directory..."
docker-compose exec app ls -la vendor

# Test the application
echo "🌐 Testing application..."
curl -f http://localhost:8011 || echo "❌ Application not responding yet"

echo "✅ Fix completed! Try accessing: http://YOUR_VM_IP:8011"
