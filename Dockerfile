# Usa una imagen base con PHP 8 y Nginx
FROM richarvey/nginx-php-fpm:latest

# Copia los archivos de tu aplicación al contenedor
COPY . /var/www/html

# Establece los permisos correctos para los archivos
RUN chown -R www-data:www-data /var/www/html

# Instala las dependencias con Composer
RUN composer install --no-dev --optimize-autoloader

# Corre comandos de cache en producción
RUN php artisan config:cache
RUN php artisan route:cache

# Expone el puerto 80
EXPOSE 80

# Comando por defecto al iniciar el contenedor
CMD ["/start.sh"]
