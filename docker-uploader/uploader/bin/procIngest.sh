# Create the languages collection (TODO: we don't need to do this everytime)
mongoimport -h uploadermongo -d emlo-edit -c language-all --type csv --file ../languages.csv --headerline

# Mess with data.
time mongo --verbose uploadermongo/emlo-edit --eval 'var ingestname="'$1'"' ../js/transIngest.js > ../logs/transIngest.log 2> ../logs/transIngest.err
tail -n 20 ../logs/transIngest.*