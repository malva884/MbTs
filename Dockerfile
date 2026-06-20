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
EXPOSE 9000
CMD ["php-fpm"]
