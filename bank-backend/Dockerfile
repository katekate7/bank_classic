FROM php:8.2-apache
 
RUN a2enmod rewrite headers
 
RUN apt-get update \
  && apt-get install -y libzip-dev git wget --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
 
RUN docker-php-ext-install pdo mysqli pdo_mysql zip;
 
RUN wget https://getcomposer.org/download/2.0.9/composer.phar \
    && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer
 
COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY . /var/www
 
WORKDIR /var/www

# Fix permissions for cache and log directories
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && mkdir -p /var/www/var/cache /var/www/var/log \
    && chown -R www-data:www-data /var/www/var \
    && chmod -R 775 /var/www/var
 
CMD ["apache2-foreground"]