FROM php:8.3-fpm
WORKDIR /app
COPY --from=composer /app/vendor ./vendor
COPY --from=node /app/public ./public
COPY --from=node /app/public/build ./public/build
COPY . .

# Create storage directories and set permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/pdf storage/app/all && \
    chmod -R 777 storage bootstrap/cache

# Configure PHP to display errors
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/errors.ini

# =========================================================================
# MODIFICA QUI: Installazione Nginx, curl, smbclient e SQL Server Driver via HTTPS (Porta 443)
# =========================================================================
RUN sed -i 's/http:\/\//https:\/\//g' /etc/apt/sources.list.d/*.list || true && \
    sed -i 's/http:\/\//https:\/\//g' /etc/apt/sources.list || true && \
    apt-get update && \
    apt-get install -y --no-install-recommends ca-certificates nginx curl gnupg apt-transport-https smbclient && \
    curl --https https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft.gpg && \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://microsoft.com bookworm main" > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools unixodbc-dev && \
    pecl install sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
    rm -rf /var/lib/apt/lists/*

# =========================================================================
# MODIFICA QUI: Installazione MySQL PDO driver, GD extension e Zip extension via HTTPS
# =========================================================================
RUN sed -i 's/http:\/\//https:\/\//g' /etc/apt/sources.list.d/*.list || true && \
    sed -i 's/http:\/\//https:\/\//g' /etc/apt/sources.list || true && \
    apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype-dev libzip-dev zip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql gd zip && \
    rm -rf /var/lib/apt/lists/*

# Configure Nginx
RUN rm /etc/nginx/sites-enabled/default
RUN echo "server { \
    listen 3000; \
    server_name localhost; \
    root /app/public; \
    index index.php index.html; \
    location / { \
        try_files \$uri \$uri/ /index.php?\$query_string; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name; \
    } \
}" > /etc/nginx/sites-enabled/default

EXPOSE 3000
CMD php-fpm -D && nginx -g 'daemon off;'
