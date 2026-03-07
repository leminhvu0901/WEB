FROM richarvey/php-apache-heroku:8.2
COPY . /var/www/html
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
