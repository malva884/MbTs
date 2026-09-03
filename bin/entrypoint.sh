#!/bin/sh
mkdir -p storage/framework/cache/data \
         storage/framework/cache/laravel-excel \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/pdf \
         storage/app/all \
         storage/app/google \
         public/file
chmod -R 777 storage bootstrap/cache public/file

php-fpm -D
nginx -g 'daemon off;'
