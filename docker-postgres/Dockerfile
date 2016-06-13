FROM postgres

COPY database-dump/pg_dumpall.out.gz /tmp/

RUN mkdir -p /docker-entrypoint-initdb.d
COPY emlo_database.sh /docker-entrypoint-initdb.d/

# Once built:
# > docker build -t postgres-with-emlo .
# and container created:
# > docker create
# This doesn't start the database import for some reason... (Maybe because there was no shebang which I;ve nnow added (needs testing)
# So log in and manually do it:
# docker exec -it <CONTAINER> bash
# source /docker-entrypoint-initdb.d/emlo_database.sh
