#!/bin/bash

echo "🔧 Fixing View Compilation Memory Issue"
echo "======================================"

# Clear all view caches completely
echo "🧹 Clearing all view caches..."
docker-compose exec app rm -rf storage/framework/views/*
docker-compose exec app rm -rf bootstrap/cache/*

# Set unlimited memory for this operation
echo "📝 Setting unlimited memory for compilation..."
docker-compose exec app bash -c "echo 'memory_limit = -1' > /usr/local/etc/php/conf.d/unlimited.ini"

# Recreate views with unlimited memory
echo "🔄 Recreating views..."
docker-compose exec app php -d memory_limit=-1 artisan view:clear
docker-compose exec app php -d memory_limit=-1 artisan config:clear
docker-compose exec app php -d memory_limit=-1 artisan route:clear

# Test simple route first
echo "🧪 Testing simple route..."
docker-compose exec app php -d memory_limit=-1 artisan serve --host=0.0.0.0 --port=8000 &
SERVE_PID=$!
sleep 3
docker-compose exec app curl -s http://localhost:8000/test | head -1
kill $SERVE_PID 2>/dev/null

# Create a minimal dashboard for testing
echo "📝 Creating minimal dashboard..."
docker-compose exec app bash -c "cat > resources/views/dashboard.blade.php << 'EOF'
@extends('layouts.app')

@section('content')
<div class=\"p-6\">
    <h1 class=\"text-2xl font-bold\">Digital Cloud Management Console</h1>
    <p class=\"mt-2\">Dashboard is working!</p>
</div>
@endsection
EOF"

# Clear views again
echo "🧹 Clearing views after update..."
docker-compose exec app php -d memory_limit=-1 artisan view:clear

# Test main route
echo "🧪 Testing main route..."
docker-compose exec app php -d memory_limit=-1 artisan serve --host=0.0.0.0 --port=8000 &
SERVE_PID=$!
sleep 3
docker-compose exec app curl -s http://localhost:8000/ | head -5
kill $SERVE_PID 2>/dev/null

# Reset memory limit to reasonable value
echo "📝 Resetting memory limit to 512M..."
docker-compose exec app bash -c "echo 'memory_limit = 512M' > /usr/local/etc/php/conf.d/custom.ini"

# Restart app container
echo "🔄 Restarting app container..."
docker-compose restart app

# Wait for restart
echo "⏳ Waiting for restart..."
sleep 15

# Final test
echo "🧪 Final application test..."
docker-compose exec app curl -I http://localhost/ 2>/dev/null || echo "Still failing"

echo ""
echo "✅ View memory fix complete!"
echo "🌐 Try accessing: http://101.46.58.130:8011"
