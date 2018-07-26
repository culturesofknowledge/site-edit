Emlo Editor
===========

Install Docker
--------------

    <PACKAGE_MANAGER> install docker docker-compose
    
Recommended: If you want to move the docker storage location (i.e. to use different disk space):

    mv /var/lib/docker /data/
    ln -s /data/docker /var/lib/docker

Now start docker:

    systemctl enable docker
    systemctl start docker

Setup servers
--------------

Assuming you have already git cloned this repo (i.e. the one this readme is in):

 - mkdir -p volumes/csv volumes/ssh

 - Create config files:
   - cp docker-compose.php.template.env to docker-compose.php.env and fill in the missing values (See below if you need new identity keys)
   - cp docker-exporter/exporter/config.template.py to docker-exporter/exporter/config.py and fill in the missing values.
   - cp emlo-edit-php/interface/proform/lib/config.template.php emlo-edit-php/interface/proform/lib/config.php

 - generate/obtain ssl key and cert file for nginx build (e.g. sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout nginx/ssl/ssl.key -out nginx/ssl/ssl.crt )


Enable publishing of data via csv export
----------------------------------------

An export runs daily. You can configure where it goes with the docker-compose.php.env but if you need new keys do this:
    
Generate or move a key, to the file volumes/ssh/id_rsa . If generating, accept the defaults (but you might want to change the filename)

    ssh-keygen  # use to generate a new key
    
Now copy the key to the remote server we need to update, you'll need to log in:
    
     ssh-copy-id -i <file-name> <user@server> # login to remote using id

Add a cronjob that runs daily:

    docker-compose exec php bash -c '/var/www/core/export_cofk_union.sh' | tee export.log

Note: the first connection will ask you to authenticate the remote host so you may want to try a connection before continuing.  

Enable simple data exporter
---------------
Change owners of the data folder to www-data

    chown 33:33 docker-exporter/exporter/exports exporter/exporter_data

Backup
------

Make a directory

	mk /data/backups

Add cronjob that runs daily

	backup.sh


Insert latest data (only if necessary, don't overwrite new data!)
------------------

Get database data, e.g.:

    pg_dumpall --username=<USERNAME_HERE> | gzip > pg_dumpall.out.gz

Copy to container, e.g.:

    docker cp pg_dumpall.out.gz <containername>:/tmp/

Connect to container:

    docker-compose exec postgres bash

Delete, Extract, and re-index:

    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U <USERNAME_HERE> -c "DROP DATABASE ouls;"
    
    gunzip /tmp/pg_dumpall.out.gz
    
    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U <USERNAME_HERE> < /tmp/pg_dumpall.out
    
    rm -f /tmp/pg_dumpall.out