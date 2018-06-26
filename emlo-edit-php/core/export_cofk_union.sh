#!/bin/bash

SCRIPTDIR=/var/www/core/
CSVSOURCE=/csv/

DATABASE=ouls
# DATABASE=oulstestdata

echo "Output directory will be $CSVSOURCE"

#---------------------------------------------------------

function cleanup() {

  for tempfile in \
    manifestation_batches.txt \
    get_min_id.sql \
    get_nextid.sql \
    rowcount.txt \
    max_id.txt \
    min_id.txt \
    get_rowcount.sql \
    get_max_id.sql \
    nextid.txt \
    export_cofk_union_comment.log \
    export_cofk_union_image.log \
    export_cofk_union_institution.log \
    export_cofk_union_location.log \
    export_cofk_union_manifestation.log \
    export_cofk_union_person.log \
    export_cofk_union_relationship.log \
    export_cofk_union_relationship_type.log \
    export_cofk_union_resource.log \
    export_cofk_union_work.log
  do
    if [ -f ${tempfile} ]
    then
      \rm ${tempfile}
    fi
  done
}
#---------------------------------------------------------

cleanup

batch_size=$(( 5000 )) # evaluate as integer

for tabroot in institution location resource person comment image work relationship
do
  tab=cofk_union_${tabroot}

  echo
  echo "Starting $tab" $(date)
  COFK_TABLE_TO_EXPORT=${tab}

  tab_id=${tabroot}_id
  if [ "$tabroot" = "person" -o "$tabroot" = "work" ]
  then
    tab_id=i${tab_id}
  fi

  sql_min="select min( $tab_id ) from $tab"
  psql ${DATABASE} -h postgres -U postgres -q  -t -c "${sql_min}" > min_id.txt
  first_id=$(cat min_id.txt)
  first_id=$(( $first_id )) # evaluate as integer

  echo "First ID in $tab:   $first_id"

  if [ ${first_id} != "0" ]
  then

	  sql_max="select max( $tab_id ) from $tab"
	  sql_count="select count( $tab_id ) from $tab"

	  psql ${DATABASE} -h postgres -U postgres -q  -t -c "${sql_max}" > max_id.txt
	  psql ${DATABASE} -h postgres -U postgres -q  -t -c "${sql_count}" > rowcount.txt

	  last_id=$(cat max_id.txt)
	  last_id=$(( $last_id )) # evaluate as integer
	  rowcount=$(cat rowcount.txt)

	  echo "Last ID in $tab:    $last_id"
	  echo "Rows count in $tab: $rowcount"

	  COFK_FIRST_ID_IN_TABLE=$(( $first_id ))
	  COFK_LAST_ID_IN_TABLE=$(( $last_id - 1 )) # Go into while the first time!
	  COFK_WRITE_CSV_HEADER=1

	  while [ ${COFK_LAST_ID_IN_TABLE} -lt ${last_id} ]
	  do

	    if [ ${rowcount} -gt ${batch_size} ]
	    then
	      COFK_LAST_ID_IN_TABLE=$(( COFK_FIRST_ID_IN_TABLE + $batch_size ))
	    else
	      COFK_LAST_ID_IN_TABLE=${last_id}
	    fi

	    echo
	    php -f ${SCRIPTDIR}export_cofk_union.php ${tab} ${COFK_WRITE_CSV_HEADER} ${COFK_FIRST_ID_IN_TABLE} ${COFK_LAST_ID_IN_TABLE} | tee export_${tab}.log

	    result=$(tail -n 1 export_${tab}.log)
	    success=$(echo ${result} | grep Finished)
	    if [ "$success" = "" ]
	    then
	      echo "Failed to complete $tab"
	      exit
	    fi

	    sql_next="select $tab_id from $tab where $tab_id > $COFK_LAST_ID_IN_TABLE order by $tab_id limit 1"
	    psql ${DATABASE} -h postgres -U postgres -q  -t -c "${sql_next}" > nextid.txt
	    next_id=$(cat nextid.txt)
	    next_id=$(( next_id )) # evaluate as integer

	    COFK_FIRST_ID_IN_TABLE=$(( next_id ))
	    COFK_WRITE_CSV_HEADER=0
	  done
  fi
done

echo
echo Done.

#---------------------------------------------------------------------------------------

# Special handing for tables with character key columns
tab=cofk_union_manifestation

php -q ${SCRIPTDIR}batch_manifestations_union.php

COFK_TABLE_TO_EXPORT=${tab}
COFK_WRITE_CSV_HEADER=1

while read first_id last_id
do
  COFK_FIRST_ID_IN_TABLE=${first_id}
  COFK_LAST_ID_IN_TABLE=${last_id}
  
  echo #"Processing $tab from ID $COFK_FIRST_ID_IN_TABLE to $COFK_LAST_ID_IN_TABLE"
  php -q ${SCRIPTDIR}export_cofk_union.php ${tab} ${COFK_WRITE_CSV_HEADER} ${COFK_FIRST_ID_IN_TABLE} ${COFK_LAST_ID_IN_TABLE} | tee export_${tab}.log

  result=$(tail -n 1 export_${tab}.log)
  success=$(echo ${result} | grep Finished)
  if [ "$success" = "" ]
  then
    echo "Failed to complete $tab"
    exit
  fi
  COFK_WRITE_CSV_HEADER=0

done < manifestation_batches.txt

#---------------------------------------------------------------------------------------
echo ''
echo ''
echo 'Finished writing out first version of CSV files'
echo ''
echo ''
echo 'Reinstating foreign characters etc in Selden End data'
\mv cofk_union*.csv ${CSVSOURCE}
${SCRIPTDIR}reinstate_accents_selden_end.sh y

#---------------------------------------------------------------------------------------

psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_activity to ${CSVSOURCE}pro_activity.csv csv header"
psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_assertion to ${CSVSOURCE}pro_assertion.csv csv header"
psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_location to ${CSVSOURCE}pro_location.csv csv header"
psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_primary_person to ${CSVSOURCE}pro_primary_person.csv csv header"
psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_relationship to ${CSVSOURCE}pro_relationship.csv csv header"
psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_role_in_activity to ${CSVSOURCE}pro_role_in_activity.csv csv header"
psql ${DATABASE} -h postgres -U cofkadmin -c "\\copy pro_textual_source to ${CSVSOURCE}pro_textual_source.csv csv header"

echo 'Export to CSV files now complete.'


# Transfer new files to front server
echo 'Copying CSV files to front end server.'
${SCRIPTDIR}/transfer_cofk_union.sh ${CSVSOURCE}


## -- I think we'll just link to images on the back-end server instead -- ${SCRIPTDIR}transfer_uploaded_images.sh


cleanup  # the log files only get removed if there was no error during the process

date
#---------------------------------------------------------------------------------------
