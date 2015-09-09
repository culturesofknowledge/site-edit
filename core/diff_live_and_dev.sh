#! /bin/bash

livedir=/var/apache2/cgi-bin/aeolus/aeolus2/cofk
devdir=/home/cofkadmin/backend/php
output_file=/home/cofkadmin/diff_live_and_dev.txt

date > $output_file
echo "< Live: $livedir" >> $output_file
echo "> Dev : $devdir" >> $output_file

cd $livedir
for i in $(ls *.php)
do
  echo ' '
  echo $i
  diff -w $i $devdir
done >> $output_file 2>&1

more $output_file


