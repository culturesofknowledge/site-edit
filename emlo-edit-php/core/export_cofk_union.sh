#! /bin/bash
# /home/burgess/scripts/sccs/cofk/sh/s.export_cofk_union.sh 1.6 2011/09/28 15:07:19 

export SCRIPTDIR=/var/www/core/
export CSVSOURCE=/csv/

echo "Output directory will be $CSVSOURCE"

#---------------------------------------------------------

function cleanup() {

  if [ -f manifestation_batches.txt ]
  then
    \rm manifestation_batches.txt
  fi

  if [ -f get_min_id.sql ]
  then
    \rm get_min_id.sql
  fi

  if [ -f get_max_id.sql ]
  then
    \rm get_max_id.sql
  fi

  if [ -f get_rowcount.sql ]
  then
    \rm get_rowcount.sql
  fi

  if [ -f min_id.txt ]
  then
    \rm min_id.txt
  fi

  if [ -f max_id.txt ]
  then
    \rm max_id.txt
  fi

  if [ -f rowcount.txt ]
  then
    \rm rowcount.txt
  fi

  if [ -f get_nextid.sql ]
  then
    \rm get_nextid.sql
  fi

  if [ -f nextid.txt ]
  then
    \rm nextid.txt
  fi

  for tabroot in \
    comment \
    image \
    institution \
    location \
    manifestation \
    person \
    relationship \
    relationship_type \
    resource \
    work 
  do
    if [ -f export_cofk_union_$tabroot.log ]
    then
      \rm export_cofk_union_$tabroot.log
    fi
  done
}
#---------------------------------------------------------

cleanup

batch_size=$(( 5000 )) # evaluate as integer

#for tabroot in comment institution location person work 
#for tabroot in work
for tabroot in comment image institution location person relationship resource work
do
  tab=cofk_union_$tabroot
  echo "Starting $tab"
  date
  export COFK_TABLE_TO_EXPORT=$tab

  tab_id=${tabroot}_id
  if [ "$tabroot" = "person" -o "$tabroot" = "work" ]
  then
    tab_id=i$tab_id
  fi

  echo "select min( $tab_id ) from $tab" > get_min_id.sql
  echo "select max( $tab_id ) from $tab" > get_max_id.sql
  echo "select count( $tab_id ) from $tab" > get_rowcount.sql

  psql ouls -h postgres -U postgres -q  -t < get_min_id.sql > min_id.txt
  psql ouls -h postgres -U postgres -q  -t < get_max_id.sql > max_id.txt
  psql ouls -h postgres -U postgres -q  -t < get_rowcount.sql > rowcount.txt

  first_id=$(cat min_id.txt)
  first_id=$(( $first_id )) # evaluate as integer
  last_id=$(cat max_id.txt)
  last_id=$(( $last_id )) # evaluate as integer
  rowcount=$(cat rowcount.txt)

  echo "First ID in $tab is $first_id"
  echo "Last ID in $tab is $last_id"
  echo "Rows in $tab = $rowcount"

  export COFK_FIRST_ID_IN_TABLE=$(( $first_id ))
  export COFK_LAST_ID_IN_TABLE=$(( $first_id ))
  export COFK_WRITE_CSV_HEADER=1
  
  while [ $COFK_LAST_ID_IN_TABLE -lt $last_id ]
  do
    if [ $rowcount -gt $batch_size ]
    then
      export COFK_LAST_ID_IN_TABLE=$(( COFK_FIRST_ID_IN_TABLE + $batch_size ))
    else
      export COFK_LAST_ID_IN_TABLE=$last_id
    fi

    echo "Processing $tab from ID $COFK_FIRST_ID_IN_TABLE to $COFK_LAST_ID_IN_TABLE"

    php -q ${SCRIPTDIR}export_cofk_union.php | tee export_$tab.log

    result=$(tail export_$tab.log)
    success=$(echo $result|grep Finished)
    if [ "$success" = "" ]
    then
      echo "Failed to complete $tab"
      exit
    fi

    echo "select $tab_id from $tab where $tab_id > $COFK_LAST_ID_IN_TABLE order by $tab_id limit 1" > get_nextid.sql
    psql ouls -h postgres -U postgres -q  -t < get_nextid.sql > nextid.txt
    next_id=$(cat nextid.txt)
    next_id=$(( next_id )) # evaluate as integer

    export COFK_FIRST_ID_IN_TABLE=$(( next_id ))
    echo "Next id: ". ${COFK_FIRST_ID_IN_TABLE}
    export COFK_WRITE_CSV_HEADER=0
  done
done
echo Done.

#---------------------------------------------------------------------------------------

# Special handing for tables with character key columns
tab=cofk_union_manifestation

php -q ${SCRIPTDIR}batch_manifestations_union.php

export COFK_TABLE_TO_EXPORT=$tab
export COFK_WRITE_CSV_HEADER=1

while read first_id last_id
do
  export COFK_FIRST_ID_IN_TABLE=$first_id
  export COFK_LAST_ID_IN_TABLE=$last_id
  
  echo "Processing $tab from ID $COFK_FIRST_ID_IN_TABLE to $COFK_LAST_ID_IN_TABLE"

  php -q ${SCRIPTDIR}export_cofk_union.php | tee export_$tab.log

  result=$(tail export_$tab.log)
  success=$(echo $result|grep Finished)
  if [ "$success" = "" ]
  then
    echo "Failed to complete $tab"
    exit
  fi
  export COFK_WRITE_CSV_HEADER=0

done < manifestation_batches.txt

#---------------------------------------------------------------------------------------

# Special handing for tables with character key columns
tab=cofk_union_relationship_type
echo "Starting $tab"
date
tab_id=relationship_code

echo "select min( $tab_id ) from $tab" > get_min_id.sql
echo "select max( $tab_id ) from $tab" > get_max_id.sql

psql ouls -h postgres -U postgres -q  -t < get_min_id.sql > min_id.txt
psql ouls -h postgres -U postgres -q  -t < get_max_id.sql > max_id.txt

first_id=$(cat min_id.txt)
last_id=$(cat max_id.txt)

echo "First ID in $tab is $first_id"
echo "Last ID in $tab is $last_id"

export COFK_TABLE_TO_EXPORT=$tab
export COFK_FIRST_ID_IN_TABLE=$first_id
export COFK_LAST_ID_IN_TABLE=$last_id
export COFK_WRITE_CSV_HEADER=1

echo "Processing $tab from ID $COFK_FIRST_ID_IN_TABLE to $COFK_LAST_ID_IN_TABLE"

php -q ${SCRIPTDIR}export_cofk_union.php | tee export_$tab.log

result=$(tail export_$tab.log)
success=$(echo $result|grep Finished)
if [ "$success" = "" ]
then
  echo "Failed to complete $tab"
  exit
fi

echo Done.

#---------------------------------------------------------------------------------------
echo ''
echo ''
echo 'Finished writing out first version of CSV files'
echo ''
echo ''
echo 'Reinstating foreign characters etc in Selden End data'
\mv cofk_union*.csv $CSVSOURCE
${SCRIPTDIR}reinstate_accents_selden_end.sh y

#---------------------------------------------------------------------------------------

psql ouls -h postgres -U cofkadmin -c "\\copy pro_activity to ${CSVSOURCE}pro_activity.csv csv header"
psql ouls -h postgres -U cofkadmin -c "\\copy pro_assertion to ${CSVSOURCE}pro_assertion.csv csv header"
psql ouls -h postgres -U cofkadmin -c "\\copy pro_location to ${CSVSOURCE}pro_location.csv csv header"
psql ouls -h postgres -U cofkadmin -c "\\copy pro_primary_person to ${CSVSOURCE}pro_primary_person.csv csv header"
psql ouls -h postgres -U cofkadmin -c "\\copy pro_relationship to ${CSVSOURCE}pro_relationship.csv csv header"
psql ouls -h postgres -U cofkadmin -c "\\copy pro_role_in_activity to ${CSVSOURCE}pro_role_in_activity.csv csv header"
psql ouls -h postgres -U cofkadmin -c "\\copy pro_textual_source to ${CSVSOURCE}pro_textual_source.csv csv header"

echo 'Export to CSV files now complete.'

echo 'Copying CSV files to front end server.'

# New server data tranfers
folder_location=/data/emlo-docker-compose/data/
remote_location_qa=bodl-emlo-svc@emlo-qa-site1.bodleian.ox.ac.uk:${folder_location}
remote_location_prd=bodl-emlo-svc@emlo-prd-site1.bodleian.ox.ac.uk:${folder_location}

for objects in manifestation comment image institution location person relationship resource work
do
    csv_local_file=${CSVSOURCE}cofk_union_${objects}.csv

    csv_remote_qa_file=${remote_location_qa}${objects}.csv
    echo "QA Export to $csv_remote_qa_file"
    rsync -zqt ${csv_local_file} ${csv_remote_qa_file}

    csv_remote_prd_file=${remote_location_prd}${objects}.csv
    echo "PRD Export to $csv_remote_prd_file"
    rsync -zqt ${csv_local_file} ${csv_remote_prd_file}
done

ssh bodl-emlo-svc@emlo-qa-site1.bodleian.ox.ac.uk 'echo 1 > '${folder_location}'need_index'
ssh bodl-emlo-svc@emlo-prd-site1.bodleian.ox.ac.uk 'echo 1 > '${folder_location}'need_index'

## -- I think we'll just link to images on the back-end server instead -- ${SCRIPTDIR}transfer_uploaded_images.sh


cleanup  # the log files only get removed if there was no error during the process

date
#---------------------------------------------------------------------------------------
