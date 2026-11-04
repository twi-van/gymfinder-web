#!/bin/bash
set -eu

# Railway gán PORT động (vd. 8080). Apache mặc định chỉ Listen 80 → phải đổi.
PORT="${PORT:-8080}"

echo "GYMFINDER: starting Apache on PORT=${PORT}"

# Tránh cảnh báo/lỗi FQDN trên container
if ! grep -qE '^ServerName ' /etc/apache2/apache2.conf 2>/dev/null; then
  echo 'ServerName localhost' >> /etc/apache2/apache2.conf
fi

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

# Smoke-check (không fail cứng nếu chỉ warning)
apache2ctl configtest || {
  echo "GYMFINDER: apache2ctl configtest failed — dumping ports.conf:" >&2
  cat /etc/apache2/ports.conf >&2 || true
  exit 1
}

echo "GYMFINDER: Apache config OK, launching foreground"
exec apache2-foreground
