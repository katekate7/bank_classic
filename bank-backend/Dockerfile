# Base stage
FROM php:8.2-apache as base
 
RUN a2enmod rewrite
 
RUN apt-get update \
  && apt-get install -y libzip-dev git wget curl libsqlite3-dev --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
 
RUN docker-php-ext-install pdo mysqli pdo_mysql pdo_sqlite zip;
 
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Production stage
FROM base as production
COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY . /var/www
 
WORKDIR /var/www
 
CMD ["apache2-foreground"]

# Test stage
FROM base as test
COPY . /var/www/html
WORKDIR /var/www/html

# Install test dependencies
RUN composer install --dev --no-interaction --optimize-autoloader

# Set up test environment
RUN mkdir -p var/log var/cache
RUN chmod -R 777 var/

# Create test database directory
RUN mkdir -p var/data

CMD ["vendor/bin/phpunit"]