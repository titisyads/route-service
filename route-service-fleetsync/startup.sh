#!/bin/bash
echo "Running driver service migrations.."
cd /app/route
php artisan migrate

php artisan serve --host=0.0.0.0 --port=8002