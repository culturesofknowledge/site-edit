cd ../../php
php import2Postgres.php $1 > ../logs/import2Postgres.log 2> ../logs/import2Postgres.err
ret=$?
tail -n 40 ../logs/import2Postgres.log
exit $ret;
