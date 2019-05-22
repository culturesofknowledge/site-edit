from __future__ import print_function

from tweaker import automater

import pika
import logging
import json
import time
import sys

import smtplib
from email.mime.text import MIMEText

from config import config

sleep_time = 1
print( "Sleeping " + str(sleep_time) + " seconds to let rabbitmq start" )
time.sleep(sleep_time)

LOG_FORMAT = ('%(levelname) -10s %(asctime)s %(name) -30s %(funcName) -35s %(lineno) -5d: %(message)s')
LOGGER = logging.getLogger(__name__)

logging.basicConfig(level=logging.INFO, format=LOG_FORMAT)

credentials = pika.PlainCredentials('guest', 'guest')
connection_parameters = pika.ConnectionParameters('rabbitmq', 5672, '/', credentials)

connection = pika.BlockingConnection(connection_parameters)
channel = connection.channel()

channel.queue_declare(queue='tweaker')


def callback(ch, method, properties, body):

	LOGGER.info(" [x] Received %r" % body)

	data = json.loads( body )
	originial_filelocation = data['filelocation']
	email = data['email']
	# type = data['type']

	filelocation = originial_filelocation.replace( "/tweaker/", "/tweaker_data/")

	error = None
	output = None

	try :
		output = automater.main( filelocation, config, LOGGER )

	except Exception as ex:
		error = "Error: " + str(ex)

	if error:
		LOGGER.info( error )

	if output :
		LOGGER.info( "Done: " + output )

	#sendMail( email, output, error )


def sendMail( email, output, error ) :
	# Send email.

	if error:
		msg = MIMEText(
			"Hi. Unfortunately the batch process has not completed. " + error
		)
	else:
		msg = MIMEText(
			"Hi. The batch process has completed. " + output
		)

	msg["Subject"] = "Your batch operation"
	email_from = "cok_bot@emlo-edit.bodleian.ox.ac.uk"
	email_to = email
	msg["From"] = email_from
	msg["To"] = email_to

	s = smtplib.SMTP('smtp.ox.ac.uk')
	s.sendmail( email_from, email_to, msg.as_string())
	s.quit()

channel.basic_consume(on_message_callback=callback, queue='tweaker', auto_ack=False)

LOGGER.info('Waiting for batch upload...')
channel.start_consuming()

