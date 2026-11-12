FROM php:8.2-apache

# curl ext is bundled in the base image; enable pretty defaults
RUN a2enmod rewrite \
 && printf 'DirectoryIndex indexVenmo.php index.php index.html\n' > /etc/apache2/conf-available/venmo.conf \
 && a2enconf venmo

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
