#!/bin/bash

cd /data/emlo-editor
. backup-helper.sh

DEST=/data/backups
FILENAME=pg-dump_ouls.gz

docker-compose exec postgres sh -c 'pg_dump -U postgres ouls' | gzip --best > $DEST/SFILENAME

backup_rotate_store $DEST $FILENAME
