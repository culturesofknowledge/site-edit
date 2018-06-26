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

 - cp docker-compose.php.template.env to docker-compose.php.env and fill in the missing values.
 - cp docker-exporter/exporter/config.template.py to docker-exporter/exporter.config.py and fill in the missing values.
 - cp emlo-edit-php/interface/proform/lib/config.template.php emlo-edit-php/interface/proform/lib/config.php

 - generate/obtain ssl key and cert file for nginx build (e.g. sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout ssl/ssl.key -out ssl/ssl.crt )


Enable indexing of front
------------------------

Create an ssh key for access to the front end servers (QA *or* PRD).

Jump on to container:

    docker-compose exec php bash
    
Generate a key, accepting the defaults (i.e. /root/.ssh)

    ssh-keygen  # accept defaults
    
Now copy the key to the remote server we need to update, you'll need to log in:
    
    ssh-copy-id user@server # login to remote

Enable transferring to Recon
---------------------------
Repeat above but with the key which is allowed to hit the recon server

Enable exporter
---------------
Change owners of folders to www-data (You my need to run id www-data on php container to find the right uid and gid)

    chown 33:33 exporter/exports exporter/exporter_data


Insert latest data (if necessary, don't overwrite new data!)
------------------

Get database data, e.g.:

    pg_dumpall --username=<USERNAME_HERE> | gzip > pg_dumpall.out.gz

Copy to container, e.g.:

    docker cp pg_dumpall.out.gz <containername>:/tmp/

Connect to container:

    docker-compose exec postgres bash

Extract and index:

    gunzip /tmp/pg_dumpall.out.gz
    psql -h "$POSTGRES_PORT_5432_TCP_ADDR" -p "$POSTGRES_PORT_5432_TCP_PORT" -U <USERNAME_HERE> < /tmp/pg_dumpall.out
    rm -f /tmp/pg_dumpall.out