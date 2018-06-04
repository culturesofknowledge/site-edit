#! /bin/bash
echo 'Copying CSV files to front end server.'

CSVSOURCE=$1

# New server data tranfers
folder_location=/data/emlo-docker-compose/data/
remote_location=${PUBLISH_SERVER_ACCESS}:${folder_location}

for objects in manifestation comment image institution location person relationship resource work
do
    csv_local_file=${CSVSOURCE}cofk_union_${objects}.csv

    csv_remote_file=${remote_location}${objects}.csv
    echo "Export to $csv_remote_file"
    rsync -zqt ${csv_local_file} ${csv_remote_file}
done

ssh ${PUBLISH_SERVER_ACCESS} 'echo 1 > '${folder_location}'need_index'

#---------------------------------------------------------------------------------------
