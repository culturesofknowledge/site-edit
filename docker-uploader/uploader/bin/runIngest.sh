#!/bin/sh
if [ -z $1 ]
then
  echo " $0  Must supply an ingestname e.g. Peiresc1"
  exit $E_MISSING_POS_PARAM
fi

set -e
# Any subsequent(*) commands which fail will cause the shell script to exit immediately

# Load CSV into mongo and then Postgres collect area.
sh impMongo.sh $1
sh procIngest.sh $1
#sh procImport2Postgres.sh $1
