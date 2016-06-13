#!/usr/bin/env bash
gunzip /tmp/pg_dumpall.out.gz
psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U postgres < /tmp/pg_dumpall.out
rm -f /tmp/pg_dumpall.out

