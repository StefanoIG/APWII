# Use a base image with PHP 8 and Nginx
FROM richarvey/nginx-php-fpm:latest

# Copy your application's files to the container
COPY . /var/www/html

# Set the correct permissions for the files
RUN chown -R www-data:www-data /var/www/html

# Install dependencies with Composer
RUN composer install --no-dev --optimize-autoloader

# Run cache commands in production
RUN php artisan config:cache
RUN php artisan route:cache

# Expose port 80
EXPOSE 80

# Default command to run when the container starts
CMD ["/start.sh"]
