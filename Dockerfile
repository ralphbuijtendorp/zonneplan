# Begin met de officiële PHP-apache image
FROM php:8.1-apache

# Installeer de benodigde systeemtools en PHP-extensies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    nano \
    && docker-php-ext-install pdo pdo_mysql zip

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