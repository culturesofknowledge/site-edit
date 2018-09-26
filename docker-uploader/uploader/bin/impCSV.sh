#!/bin/sh
#==> impCSV.sh <==
NOW=`date`
echo $NOW >> ../logs/impCSV.log 
echo "" > ../logs/impCSV.err
mongoimport --host=uploadermongo --db emlo-edit --collection $1 --drop --type csv --headerline --file $2 >> ../logs/impCSV.log 2>> ../logs/impCSV.err
