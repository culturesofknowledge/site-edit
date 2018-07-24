#! /bin/bash
usage="Usage: '$0 <csv-source-directory> <user@server>'"

# Check for paramater $1
if [ -z "$1" ]
then
	echo "First parameter not set to csv source directory"
	echo ${usage}
	exit 1
fi

# Check for parameter $2
if [ -z "$2" ]
then
	echo "Second parameter not set to server access string, e.g. user@server"
	echo ${usage}
	exit 1
fi


csv_source=$1
server_access=$2
identity_file=$3

echo 'Copying CSV files at ${csv_source} to server ${server_access}'

# New server data transfers
folder_location=/data/emlo-docker-compose/data/
remote_location=${server_access}:${folder_location}

if [ -z "identity_file" ]
then
	rsync_settings=-zqt
else
	rsync_settings=-zqt -e "ssh -i ${identity_file}"
fi


for objects in manifestation comment image institution location person relationship resource work pro_activity pro_assertion pro_location pro_primary_person pro_relationship pro_role_in_activity pro_textual_source
do
	csv_local_file=${csv_source}cofk_union_${objects}.csv

	csv_remote_file=${remote_location}${objects}.csv
	echo "Export to $csv_remote_file"
	rsync ${rsync_settings} ${csv_local_file} ${csv_remote_file}
done

if [ -z "identity_file" ]
then
	ssh_settings=
else
	ssh_settings=-i ${identity_file}"
fi

# When all csv files are done update a flag
ssh ${ssh_settings} ${server_access} 'echo 1 > '${folder_location}'need_index'

#---------------------------------------------------------------------------------------



