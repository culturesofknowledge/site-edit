# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import os.path

import psycopg2
import psycopg2.extras

import csv_unicode


class Exporter:

	def __init__(self, postgres_connection, output=False ):

		self.connection = psycopg2.connect( postgres_connection ) # e.g. "dbname='ouls' user='postgres' host='localhost' password=''"
		self.cursor = self.connection.cursor( cursor_factory=psycopg2.extras.DictCursor )

		self.output_commands = output