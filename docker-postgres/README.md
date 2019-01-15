Database setup
==============

The file cofk-initial.postgres.schema.data.sql will set up the database for the cofk project. A default admin user called "cofka" (password the same) will be created. Use this to log on for the first time, and to create other users.

Use the file in the postgres container, something like:

	psql --username postgres --command "create database <<NEW_DATABASE>>"
    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U postgres <<NEW_DATABASE>> < cofk-empty.postgres.schema.sql
    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U postgres <<NEW_DATABASE>> < cofk-initial.postgres.schema.data.sql

User already create with the following permissions

	# create role cofka login;
	# grant super_role_cofk to cofka;
	# grant editor_role_cofk to cofka;
	# grant viewer_role_cofk to cofka;

