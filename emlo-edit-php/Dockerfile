# docker build -t emlo-edit-php:0.1.2 .
#
# docker run --name emlo-edit-php -d -p 80:80 -v $(pwd)/core:/var/www/html/core -v $(pwd)/interface:/var/www/html --link=emlo-edit-postgres:postgres emlo-edit-php:0.1.2
#
# FROM bdlssorg/php-pgsql
FROM php-pgsql:7.1.5

MAINTAINER matthew
LABEL version="0.2.0"

# Copy php config file
COPY config/php.ini /usr/local/etc/php/

# Copy main PHP files, interface to html folder (root files), and core to core folder
COPY interface /var/www/html/
COPY core /var/www/core
#VOLUME /var/www/html

RUN apt-get update

# TRYING TO FIX MISSING EXTENSION ERROR.
RUN apt-get install -y \
	libpq-dev

# Copy and run php packages to install
RUN pear install DB-1.7.14 mdb2 MDB2_Driver_pgsql pear/mdb2#pgsql

RUN mkdir /var/backups/redo-logs && \
	chown www-data:www-data /var/backups/redo-logs

RUN mkdir -p /var/www/exporter/exports && \
	mkdir -p /var/www/exporter/exporter_data && \
	chown -R www-data:www-data /var/www/exporter

# DEBUG: Add some helper apps (not needed but help when debugging the container build)
#RUN apt-get install -y \
#		vim \
#		screen \
#		postgresql
