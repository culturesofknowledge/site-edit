# This readme assumes you already have a postgres docker container (and is named "emlo-edit-postgres")

# First build the image "php with postgres extension" (if you haven't already) with:
cd docker-php-pgsql/
docker build -t php-with-pgsql .

# Now build our specific version of php to run EMLO-EDIT in (it'll copy the php files into itself)
docker build -t emlo-edit-php .

# Run the new build (apache server will run on port 8080, it'll have access to the postgresDB via the link, change the name if your postgres container is different)
docker run -p 8080:80 --link emlo-edit-postgres:pg -d --name emlo-edit-php emlo-edit-php