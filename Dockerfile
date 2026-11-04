# GYMFINDER — PHP 8.2 + Apache (Railway / Docker)
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Cho phép .htaccess (rewrite /api)
RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    Options FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/gymfinder.conf \
    && a2enconf gymfinder

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

COPY docker/apache-start.sh /usr/local/bin/apache-start.sh
RUN chmod +x /usr/local/bin/apache-start.sh \
    && sed -i 's/\r$//' /usr/local/bin/apache-start.sh

EXPOSE 80
CMD ["/usr/local/bin/apache-start.sh"]
