time mongo --verbose uploadermongo/emlo-edit --eval 'var ingestname="'$1'"' ../js/transIngest.js > ../logs/transIngest.log 2> ../logs/transIngest.err
tail -n 20 ../logs/transIngest.*