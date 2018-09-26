#!/bin/sh
#==> impKircher.sh <==

#./impCSV.sh	kircher_img ../_data/csv/Kircher_img_221013_MLILRG.csv
#./impCSV.sh	kircher_let ../_data/csv/Kircher_Letters_Received_EMLO_12092013final.csv
#_work
#_manifestation
#_people
#_places
#_repositories
prefix="collect_"
time mongo uploadermongo/emlo-edit  --eval 'var ingestname="'$1'"' ../js/dropIngest.js

for name in works manifestations people places repositories ;
do
  collection="$prefix"$name; 
  csv_file=$1/$name.csv
  echo ./impCSV.sh	$collection /uploader/$csv_file
  ./impCSV.sh	$collection /uploader/$csv_file
done
