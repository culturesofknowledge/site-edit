FROM php-with-pgsql

# Copy php config file
COPY config/php.ini /usr/local/etc/php/

# Copy main PHP files
COPY interface/ core/ /var/www/html/

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
