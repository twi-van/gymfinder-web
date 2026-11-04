#!/bin/bash
set -euo pipefail

# Railway gán PORT động (vd. 8080). Apache mặc định chỉ Listen 80 → phải đổi.
PORT="${PORT:-80}"

echo "GYMFINDER: starting Apache on PORT=${PORT}"

# Ghi đè ports + VirtualHost (tránh sed hỏng khi format file đổi)
printf 'Listen %s\n' "${PORT}" > /etc/apache2/ports.conf

cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Smoke-check cấu hình trước khi chạy
apache2ctl configtest

exec apache2-foreground
