#!  /bin/bash
# /home/burgess/scripts/sccs/cofk/sh/s.reinstate_accents_selden_end.sh 1.5 2011/07/29 15:11:24

clear
echo ''
echo ''
if [ "$CSVSOURCE" = "" ]
then
  echo 'Environment variable CSVSOURCE containing the location of the CSV files must be set.'
  exit
fi

answer=$1
if [ "$answer" = "" ]
then
  echo -n "Remove numeric entities etc from Selden End CSV files in $CSVSOURCE? (y/n) "
  answer=$(line)
fi

if [ "$answer" != "y" -a "$answer" != "Y" ]
then
  echo 'Cancelled.'
  exit
fi
 
for csvfile in cofk_union_comment.csv \
               cofk_union_institution.csv \
               cofk_union_location.csv \
               cofk_union_manifestation.csv \
               cofk_union_person.csv \
               cofk_union_work.csv
do
  echo "Starting to process $csvfile"
  csvfile=$CSVSOURCE$csvfile
  # ls -l $csvfile
  export COFK_CSV_FILE=$csvfile

  php -q ${SCRIPTDIR}reinstate_accents_union.php

  cd $CSVSOURCE
  echo "Cleaning up old file: will move ${csvfile}_new to $csvfile..."
  \mv ${csvfile}_new $csvfile
  # ls -l $csvfile
  cd - 

  echo ''
done

