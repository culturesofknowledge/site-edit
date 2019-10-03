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

 - mkdir -p volumes/csv volumes/ssh volumes/uploader volumes/tweaker
 - chown 33:33 volumes/uploader volumes/tweaker  # i.e. the containers www-data user.

 - Create config files:
   - cp docker-compose.php.template.env to docker-compose.php.env and fill in the missing values (See below if you need new identity keys)
   - cp emlo-edit-php-helper/exporter/config.template.py to emlo-edit-php-helper/exporter/config.py and fill in the missing values.
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

    docker-compose --file /data/emlo-editor/docker-compose.yaml exec php bash -c '/var/www/core/export_cofk_union.sh' > /data/emlo-editor/logs/export.log

Note: the first connection will ask you to authenticate the remote host so you may want to try a connection before continuing.  

Enable simple data exporter
---------------
Change owners of the data folder to www-data (that is the www-data inside the container, usually the id is 33)

    chown 33:33 emlo-edit-php-helper/exporter/exports emlo-edit-php-helper/exporter/exporter_data


Start (restart) server
-----------

Run the start script in the main directory. This basically buils and runs the docker containers.

 - Run ./start.sh



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

Copy to container, e.g. (containername is likely "emloeditor_postgres_1"):

    docker cp pg_dumpall.out.gz <containername>:/tmp/

Connect to container:

    docker-compose exec postgres bash

Delete, Extract, and re-index:

	# Drop database
    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U <USERNAME_HERE> -c "DROP DATABASE ouls;"
    
    # Unzip
    gunzip /tmp/pg_dumpall.out.gz
    
    # import
    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U <USERNAME_HERE> < /tmp/pg_dumpall.out
    
    #cleanup
    rm -f /tmp/pg_dumpall.out
    
## Acknowledgements

EMLO has received funding from the Andrew W. Mellon Foundation, the UK Arts and Humanities Research Council, and the Unibersity of Oxford's John Fell Fund.
