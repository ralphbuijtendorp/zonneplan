# Begin met de officiële PHP-apache image
FROM php:8.1-apache

# Installeer de benodigde systeemtools en PHP-extensies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    cron \
    nano \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql zip

# Installeer Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configureer Xdebug
RUN echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Schakel mod_rewrite in voor Apache
RUN a2enmod rewrite

# Stel de werkdirectory in
WORKDIR /var/www/html

# Kopieer de composer.json en composer.lock (indien aanwezig)
COPY composer.json composer.lock ./

# Installeer Composer
RUN curl -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installeer de PHP afhankelijkheden (via Composer)
RUN composer install --no-dev --optimize-autoloader

# Kopieer de applicatiebestanden naar de container
COPY . .

# Stel de juiste permissies in voor de bestanden
RUN chown -R www-data:www-data /var/www/html

# Stel de DocumentRoot in (optioneel)
RUN echo "DocumentRoot /var/www/html/public" >> /etc/apache2/apache2.conf

# Add crontab file in the cron directory
ADD cronjobs.txt /etc/cron.d/dataprocessor-cron

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/dataprocessor-cron

RUN crontab /etc/cron.d/dataprocessor-cron

# Add supervisord configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start supervisord
CMD ["/usr/bin/supervisord"]