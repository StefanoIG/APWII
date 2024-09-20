#!/bin/bash

echo "Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

echo "Ejecutando migraciones..."
php artisan migrate --force

echo "Cacheando configuraciones..."
php artisan config:cache

echo "Cacheando rutas..."
php artisan route:cache

echo "cacheando endpoints..."
php artisan route:list