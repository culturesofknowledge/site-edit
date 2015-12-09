# This readme assumes you already have a postgres docker container (and is named "emlo-edit-postgres")

# First build the image "php with postgres extension" (if you haven't already) with:
cd docker-php-pgsql/
docker build -t php-with-pgsql .
# and create an instance with:
docker run -p 32769:5432 -d --name emlo-edit-postgres-with-emlo postgres-with-emlo

# Now build our specific version of php to run EMLO-EDIT in (it'll copy the php files into itself)
docker build -t emlo-edit-php .

# Run the new build
#    - apache server will run on port 8080,
#    - it'll have access to the postgresDB via the link (change the name if your postgres container is different)
#    - Add the current directory to a volume at /var/www/html in the container.
docker run -p 8080:80 --link emlo-edit-postgres-with-emlo:pg -d -v $(pwd)/:/var/www/html --name emlo-edit-php emlo-edit-php

