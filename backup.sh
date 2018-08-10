#!/bin/bash

# An example backup script using backup-helper. To be run daily.
destination=/data/backups
filename=pg-dumpall.gz
filename_temp=working...${filename}

cd /data/emlo-editor

# First include the backup script
backup_helper=backup-script/backup-helper.sh

if [ ! -f ${backup_helper} ]; then
    echo "The backup-helper.sh file is required. Not found at ${backup_helper}. Download it at https://bitbucket.org/akademy/backup-script"
	exit 1
fi

. ${backup_helper}

now=$(date)
echo "Backing up at ${now} to ${destination}/${filename} ..."

# Docker-compose should work, but there's an bug (see https://github.com/docker/compose/issues/3352)
#docker-compose exec postgres pg_dumpall -U postgres | gzip --best > ${destination}/${filename_temp}
docker exec emloeditor_postgres_1 pg_dumpall -U postgres | gzip --best > ${destination}/${filename_temp}

mv ${destination}/${filename_temp} ${destination}/${filename}

backup_rotate_store ${destination} ${filename}

now=$(date)
echo "... ${now} backup complete."