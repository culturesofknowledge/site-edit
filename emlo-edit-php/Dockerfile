# docker build -t emlo-edit-php:0.1.2 .
#
# docker run --name emlo-edit-php -d -p 80:80 -v $(pwd)/core:/var/www/html/core -v $(pwd)/interface:/var/www/html --link=emlo-edit-postgres:postgres emlo-edit-php:0.1.2
#
FROM php-with-pgsql

MAINTAINER matthew
LABEL version="0.1.2"

# Copy php config file
COPY config/php.ini /usr/local/etc/php/

# Copy main PHP files, interface to html folder (root files), and core to core folder
COPY interface /var/www/html/
COPY core /var/www/html/core
#VOLUME /var/www/html

RUN apt-get update

# TRYING TO FIX MISSING EXTENSION ERROR.
RUN apt-get install -y \
	libpq-dev

# Copy and run php packages to install
RUN pear install DB-1.7.14 mdb2 MDB2_Driver_pgsql pear/mdb2#pgsql

RUN mkdir /var/backups/redo-logs && \
	chown www-data:www-data /var/backups/redo-logs

# DEBUG: Add some helper apps (not needed but help when debugging the container build)
#RUN apt-get install -y \
#		vim \
#		screen \
#		postgresql
