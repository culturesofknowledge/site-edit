#!/usr/bin/env python
import pika
import logging
import json
import time
import sys

from uploader import Uploader

sleep_time = 20
print( "Sleeping " + str(sleep_time) + " seconds to let rabbitmq start" )
time.sleep(sleep_time)

LOG_FORMAT = ('%(levelname) -10s %(asctime)s %(name) -30s %(funcName) -35s %(lineno) -5d: %(message)s')
LOGGER = logging.getLogger(__name__)

logging.basicConfig(level=logging.INFO, format=LOG_FORMAT)

credentials = pika.PlainCredentials('guest', 'guest')
connection_parameters = pika.ConnectionParameters('rabbitmq', 5672, '/', credentials)

connection = pika.BlockingConnection(connection_parameters)
channel = connection.channel()

channel.queue_declare(queue='uploader')
channel.queue_declare(queue='uploader-processed')


def callback(ch, method, properties, body):

	LOGGER.info(" [x] Received %r" % body)

	data = json.loads( body )

	error = None
	output = None

	try :

		upload = Uploader( LOGGER )
		output = upload.initiate( data )

	except Exception as ex:

		error = ex.message

	data = {
		'foldername' : data['foldername']
	}

	if output is not None:
		data['output'] = output

	if error is not None:
		data['error'] = error
		LOGGER.info("uploader FAILED")
	else:
		LOGGER.info("uploader COMPLETED")

	LOGGER.info(data)
	data = json.dumps(data)

	channel.basic_publish(exchange='', routing_key='uploader-processed', body=data)


channel.basic_consume(callback, queue='uploader', no_ack=True)

LOGGER.info('Waiting for uploaded excel files')
channel.start_consuming()