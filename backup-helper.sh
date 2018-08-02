#!/bin/false
# Rename a file and rotate the names so that older files get replaced.
# - Keep one backup per day for last seven days. Labelled "Sunday", "Monday", etc.
# - Keep one backup per week for the last four weeks and a few days. (on 1st,7th,14th,21st,28th)
# - Keep one backup per month for the last 12 months, stored on the 28th of each month. Labelled "January", "February", etc.
#
# For instance, export a database to a file each day then pass it to this function.
backup_rotate_store () {

	usage="Usage: 'backup_rotate_store <directory> <original_filename>'.
The original file should be in <directory>. Pass in the current name of the file."

	# Check for parameter $1
	if [ -z "$1" ]
	then
		echo "First parameter not set to a directory"
		echo ${usage}
		return 1
	fi

	# Check for parameter $2
	if [ -z "$2" ]
	then
		echo "Second parameter not set to original filename"
		echo ${usage}
		return 1
	fi

	local directory=$1
	local original_filename=$2

	local DAY=`date +%A`

	# Day backupds (Sunday, Monday, etc.)
	mv ${directory}/${original_filename} ${directory}/${DAY}.${original_filename}

	local DATE=`date +%d`
	if (( $DATE == 1 || $DATE == 8 || $DATE == 15 || $DATE == 22 || $DATE == 28 )); then

		local EXTENSION='th'
		if (( $DATE == 1 )); then
			EXTENSION='st'
		fi
		if (( $DATE ==22 )); then
			EXTENSION='nd'
		fi

		# Weeks backup (1st, 7th, etc.)
		cp --archive ${directory}/${DAY}.${original_filename} ${directory}/${DATE}${EXTENSION}.${original_filename}

		if (( $DATE == 28 )); then
			local MONTH=`date +%B`

			# Months backup (January, February, etc.)
			cp --archive ${directory}/${DAY}.${original_filename} ${directory}/${MONTH}.${original_filename}
		fi

	fi
}
