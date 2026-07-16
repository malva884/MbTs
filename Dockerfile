FROM composer:2.5 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
COPY artisan ./
COPY bootstrap ./bootstrap
COPY app ./app
RUN apk add --no-cache libpng-dev libzip-dev unzip freetype-dev libjpeg-turbo-dev linux-headers && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd sockets zip ftp && \
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

FROM node:18 AS node
WORKDIR /app
COPY package.json pnpm-lock.yaml ./
RUN npm install -g pnpm@8.6.2
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm build --debug

FROM php:8.3-fpm-bookworm
ENV DEBIAN_FRONTEND=noninteractive

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

# Install Nginx, SQL Server drivers (per Debian 12 Bookworm con msodbcsql18)
RUN apt-get update && apt-get install -y nginx curl gnupg apt-transport-https smbclient supervisor && \
    curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft.gpg && \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev && \
    pecl install sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
    rm -rf /var/lib/apt/lists/*

# Install MySQL PDO driver, GD extension and Zip extension
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype-dev libzip-dev zip && \
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

# Create Supervisor configuration
RUN mkdir -p /etc/supervisor/conf.d
RUN cat > /etc/supervisor/conf.d/php-fpm.conf << 'EOF'
[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/dev/stderr
stdout_logfile_maxbytes=0
EOF
RUN cat > /etc/supervisor/conf.d/nginx.conf << 'EOF'
[program:nginx]
command=nginx -g 'daemon off;'
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/dev/stderr
stdout_logfile_maxbytes=0
EOF
RUN cat > /etc/supervisor/conf.d/queue-worker.conf << 'EOF'
[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /app/artisan queue:work --sleep=3 --tries=3
autostart=${QUEUE_WORKER_ENABLED:-true}
autorestart=true
user=root
numprocs=1
redirect_stderr=true
stdout_logfile=/dev/stderr
stdout_logfile_maxbytes=0
stopwaitsecs=3600
EOF
RUN cat > /etc/supervisor/conf.d/scheduler.conf << 'EOF'
[program:scheduler]
process_name=%(program_name)s_%(process_num)02d
command=php /app/artisan schedule:work
autostart=${SCHEDULER_ENABLED:-true}
autorestart=true
user=root
numprocs=1
redirect_stderr=true
stdout_logfile=/dev/stderr
stdout_logfile_maxbytes=0
stopwaitsecs=3600
EOF

EXPOSE 3000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
