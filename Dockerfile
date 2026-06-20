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
RUN pnpm build

FROM php:8.3-fpm
WORKDIR /app
COPY --from=composer /app/vendor ./vendor
COPY --from=node /app/public ./public
COPY --from=node /app/public/build ./public/build
COPY . .

# Create storage directories and set permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs && \
    chmod -R 777 storage bootstrap/cache

# Generate .env file with complete Laravel configuration
RUN echo "APP_NAME=Portale" > /app/.env && \
    echo "APP_ENV=production" >> /app/.env && \
    echo "APP_KEY=base64:48AqRhz9Cz0DL4Yvt112UCRuOu3U1mpNCHbHUqIo8EQ=" >> /app/.env && \
    echo "APP_DEBUG=false" >> /app/.env && \
    echo "APP_URL=http://portale.metallurgicabresciana.it/" >> /app/.env && \
    echo "BASE_URL=http://portale.metallurgicabresciana.it/" >> /app/.env && \
    echo "APP_MAJOR=1.4.0" >> /app/.env && \
    echo "LOG_CHANNEL=errorlog" >> /app/.env && \
    echo "LOG_DEPRECATIONS_CHANNEL=null" >> /app/.env && \
    echo "LOG_LEVEL=error" >> /app/.env && \
    echo "DB_CONNECTION=sqlsrv" >> /app/.env && \
    echo "DB_HOST=10.141.1.17" >> /app/.env && \
    echo "DB_PORT=1434" >> /app/.env && \
    echo "DB_DATABASE=portale_dev" >> /app/.env && \
    echo "DB_USERNAME=sa" >> /app/.env && \
    echo "DB_PASSWORD=@MtB2021" >> /app/.env && \
    echo "DB_CONNECTION_GP=sqlsrv" >> /app/.env && \
    echo "DB_HOST_GP=10.141.1.8" >> /app/.env && \
    echo "DB_PORT_GP=1433" >> /app/.env && \
    echo "DB_DATABASE_GP=GP_NX_AGG" >> /app/.env && \
    echo "DB_USERNAME_GP=stl" >> /app/.env && \
    echo "DB_PASSWORD_GP=Stl@5678" >> /app/.env && \
    echo "DB_CONNECTION_F=sqlsrv" >> /app/.env && \
    echo "DB_HOST_F=10.141.1.17" >> /app/.env && \
    echo "DB_PORT_F=1434" >> /app/.env && \
    echo "DB_DATABASE_F=Fornitori" >> /app/.env && \
    echo "DB_USERNAME_F=sa" >> /app/.env && \
    echo "DB_PASSWORD_F=@MtB2021" >> /app/.env && \
    echo "DB_CONNECTION_ROOT_GP=sqlsrv" >> /app/.env && \
    echo "DB_HOST_ROOT_GP=10.141.1.8" >> /app/.env && \
    echo "DB_PORT_ROOT_GP=1433" >> /app/.env && \
    echo "DB_DATABASE_ROOT_GP=200134_MB" >> /app/.env && \
    echo "DB_USERNAME_ROOT_GP=GPSCH" >> /app/.env && \
    echo "DB_PASSWORD_ROOT_GP=GPSCH" >> /app/.env && \
    echo "DB_CONNECTION_MS=mysql" >> /app/.env && \
    echo "DB_HOST_MS=w2019web" >> /app/.env && \
    echo "DB_PORT_MS=3306" >> /app/.env && \
    echo "DB_DATABASE_MS=gestionale" >> /app/.env && \
    echo "DB_USERNAME_MS=portale" >> /app/.env && \
    echo "DB_PASSWORD_MS=laravel" >> /app/.env && \
    echo "DB_CONNECTION_PORTALE=mysql" >> /app/.env && \
    echo "DB_HOST_PORTALE=db.imaginae.it" >> /app/.env && \
    echo "DB_PORT_PORTALE=3306" >> /app/.env && \
    echo "DB_DATABASE_PORTALE=metallurgicaftp_dbapp" >> /app/.env && \
    echo "DB_USERNAME_PORTALE=metallurgicaftp_prova" >> /app/.env && \
    echo "DB_PASSWORD_PORTALE=Pisolo84." >> /app/.env && \
    echo "DB_CONNECTION_DIPENDENTI=mysql" >> /app/.env && \
    echo "DB_HOST_DIPENDENTI=10.141.255.22" >> /app/.env && \
    echo "DB_PORT_DIPENDENTI=3306" >> /app/.env && \
    echo "DB_DATABASE_DIPENDENTI=db-dipendenti" >> /app/.env && \
    echo "DB_USERNAME_DIPENDENTI=admin-dipendenti" >> /app/.env && \
    echo "DB_PASSWORD_DIPENDENTI=Pisolo84." >> /app/.env && \
    echo "BROADCAST_DRIVER=log" >> /app/.env && \
    echo "CACHE_DRIVER=file" >> /app/.env && \
    echo "FILESYSTEM_CLOUD=google" >> /app/.env && \
    echo "QUEUE_CONNECTION=database" >> /app/.env && \
    echo "SESSION_DRIVER=file" >> /app/.env && \
    echo "SESSION_DOMAIN=portale.metallurgicabresciana.it" >> /app/.env && \
    echo "SANCTUM_STATEFUL_DOMAINS=portale.metallurgicabresciana.it" >> /app/.env && \
    echo "SESSION_LIFETIME=120" >> /app/.env && \
    echo "MEMCACHED_HOST=127.0.0.1" >> /app/.env && \
    echo "REDIS_HOST=redis" >> /app/.env && \
    echo "REDIS_PASSWORD=null" >> /app/.env && \
    echo "REDIS_PORT=6379" >> /app/.env && \
    echo "MAIL_DRIVER=smtp" >> /app/.env && \
    echo "MAIL_HOST=smtp.gmail.com" >> /app/.env && \
    echo "MAIL_PORT=465" >> /app/.env && \
    echo "MAIL_USERNAME=portale.metallurgica@stl.tech" >> /app/.env && \
    echo "MAIL_PASSWORD=iiaxpopknjlgtbyr" >> /app/.env && \
    echo "MAIL_ENCRYPTION=ssl" >> /app/.env && \
    echo "MAIL_FROM_ADDRESS=portale.metallurgica@stl.tech" >> /app/.env && \
    echo "MAIL_FROM_NAME=Portale" >> /app/.env

# Configure PHP to display errors
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/errors.ini

# Install Nginx, curl and SQL Server drivers
RUN apt-get update && apt-get install -y nginx curl gnupg apt-transport-https && \
    curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft.gpg && \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools unixodbc-dev && \
    pecl install sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
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
