Database setup
==============

The file cofk-initial.postgres.schema.data.sql will set up the database for the cofk project. A default admin user called "cofka" (password the same) will be created. Use this to log on for the first time, and to create other users.

Use the file in the postgres container, something like:

    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U postgres ouls < cofk-initial.postgres.schema.data.sql