FROM richarvey/php-apache-heroku:8.2
COPY . /var/www/html
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
RUN composer install --no-dev
