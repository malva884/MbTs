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

FROM php:8.2-fpm
WORKDIR /app
COPY --from=composer /app/vendor ./vendor
COPY --from=node /app/public ./public
COPY --from=node /app/public/build ./public/build
COPY . .
RUN chown -R www-data:www-data /app

# Install Nginx and Supervisor
RUN apt-get update && apt-get install -y nginx supervisor && rm -rf /var/lib/apt/lists/*

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

# Configure Supervisor
RUN mkdir -p /var/log/supervisor
RUN printf "[supervisord]\nnodaemon=true\nuser=root\nlogfile=/var/log/supervisor/supervisord.log\npidfile=/var/run/supervisord.pid\n\n[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\nstdout_logfile=/var/log/supervisor/php-fpm.log\nstderr_logfile=/var/log/supervisor/php-fpm-error.log\n\n[program:nginx]\ncommand=nginx -g 'daemon off;'\nautostart=true\nautorestart=true\nstdout_logfile=/var/log/supervisor/nginx.log\nstderr_logfile=/var/log/supervisor/nginx-error.log\n" > /etc/supervisor/supervisord.conf

EXPOSE 3000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
