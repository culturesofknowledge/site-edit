FROM php:5.3-apache

# Copy php config file
COPY config/php.ini /usr/local/etc/php/

# Copy main PHP files
COPY interface/ core/ /var/www/html/

# Copy and run php packages to install
#COPY packages/DB-1.7.14.tgz /tmp/
RUN pear install DB-1.7.14

# DEBUG: Add some helper apps
RUN apt-get update && apt-get install -y \
        vim \
        screen

EXPOSE 32778