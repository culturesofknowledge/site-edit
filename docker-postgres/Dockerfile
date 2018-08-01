# docker build -t postgres-with-emlo:<version> .
#
# Production
# docker run --name emlo-edit-postgres -d postgres-with-emlo:<version>
#
# Development (You can remove -d too if you want to see what it is doing)
# docker run --name emlo-edit-postgres -p 5432:5432 -d postgres-with-emlo:<version>
#

# https://hub.docker.com/_/postgres/
FROM postgres:10.4

MAINTAINER matthew

COPY postgres.conf /etc/postgresql/postgresql.conf

# To enable loggin on postgres add these to postgres config file.
# log_destination = 'csvlog'
# log_directory = 'pg_log'
# log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
# log_statement = 'all'

CMD ["postgres", "-c", "config_file=/etc/postgresql/postgresql.conf"]