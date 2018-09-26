#!/usr/bin/env python
import pika
import logging

LOG_FORMAT = ('%(levelname) -10s %(asctime)s %(name) -30s %(funcName) '
			  '-35s %(lineno) -5d: %(message)s')
LOGGER = logging.getLogger(__name__)

logging.basicConfig(level=logging.INFO, format=LOG_FORMAT)

credentials = pika.PlainCredentials('guest', 'guest')
connection_parameters = pika.ConnectionParameters('rabbitmq', 5672, '/', credentials)

connection = pika.BlockingConnection(connection_parameters)
channel = connection.channel()


channel.queue_declare(queue='uploader')

def callback(ch, method, properties, body):

	LOGGER.info(" [x] Received %r" % body)

channel.basic_consume(callback,
					  queue='uploader',
					  no_ack=True)


LOGGER.info('Waiting for uploaded excel files')
channel.start_consuming()