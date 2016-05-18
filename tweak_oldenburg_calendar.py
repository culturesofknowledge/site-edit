from __future__ import print_function

__author__ = 'sers0034'

import sys

import psycopg2
import psycopg2.extras

from tweaker import tweaker
from config import config

postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

tweaker = tweaker.DatabaseTweaker( postgres_connection )

