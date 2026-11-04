# GYMFINDER — PHP 8.2 + Apache (Railway / Docker)
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Document root = project root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess (API rewrite)
RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/gymfinder.conf \
    && a2enconf gymfinder

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Railway sets PORT; map Apache to it at start
COPY docker/apache-start.sh /usr/local/bin/apache-start.sh
RUN chmod +x /usr/local/bin/apache-start.sh

EXPOSE 80
CMD ["/usr/local/bin/apache-start.sh"]
