# docker build -t postgres-with-emlo:<version> .
#
# Production
# docker run --name emlo-edit-postgres -d postgres-with-emlo:<version>
#
# Development (You can remove -d too if you want to see what it is doing)
# docker run --name emlo-edit-postgres -p 5432:5432 -d postgres-with-emlo:<version>
#
# The container doesn't run the emlo_database.sh file
# So manually do it:
# docker exec -it <CONTAINER> /bin/bash
# source /docker-entrypoint-initdb.d/emlo_database.sh
#

# https://hub.docker.com/_/postgres/
FROM postgres

MAINTAINER matthew

LABEL version="0.1.0"

COPY database-dump/pg_dumpall.out.gz /tmp/

RUN mkdir -p /docker-entrypoint-initdb.d
COPY emlo_database.sh /docker-entrypoint-initdb.d/
RUN chmod +x /docker-entrypoint-initdb.d/emlo_database.sh

# To enable loggin on postgres add these to posgres config file.
# log_destination = 'csvlog'
# log_directory = 'pg_log'
# log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
# log_statement = 'all'
#
