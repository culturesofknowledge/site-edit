#!/bin/bash

cd /data/emlo-editor
. backup-helper.sh

DEST=/data/backups
FILENAME=pg-dumpall.gz

docker-compose exec postgres sh -c 'pg_dumpall -U postgres' | gzip --best > ${DEST}/${FILENAME}

backup_rotate_store ${DEST} ${FILENAME}
