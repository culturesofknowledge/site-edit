#!  /bin/bash
# /home/burgess/scripts/sccs/cofk/sh/s.reinstate_accents_selden_end.sh 1.5 2011/07/29 15:11:24

SCRIPTDIR=/var/www/core/
CSVSOURCE=/csv/

for csvfile in cofk_union_comment.csv \
               cofk_union_institution.csv \
               cofk_union_location.csv \
               cofk_union_manifestation.csv \
               cofk_union_person.csv \
               cofk_union_work.csv
do
  echo "Starting to process $csvfile"
  csvfile=$CSVSOURCE$csvfile

  export COFK_CSV_FILE=$csvfile
  php -q ${SCRIPTDIR}reinstate_accents_union.php

  cd $CSVSOURCE
  echo "Cleaning up old file: will move ${csvfile}_new to $csvfile..."
  \mv ${csvfile}_new $csvfile
  # ls -l $csvfile
  cd - 

  echo ''
done

