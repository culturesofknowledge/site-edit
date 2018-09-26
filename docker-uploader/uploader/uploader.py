from xls_to_csv import create_csvs

class Uploader:

	def __init__( self, logger ):

		self.log = logger


	def initiate(self, data):

		self.log.info( "def:initiate")

		output = '/uploader/' + data['foldername']

		create_csvs( data['filelocation'], output )

if __name__ == "__main__":
	import json
	import logging

	LOG_FORMAT = ('%(levelname) -10s %(asctime)s %(name) -30s %(funcName) -35s %(lineno) -5d: %(message)s')
	LOGGER = logging.getLogger(__name__)
	logging.basicConfig(level=logging.INFO, format=LOG_FORMAT)

	body = '{"foldername":"test-180926-142259","email":"matthew.wilcoxson@oerc.ox.ac.uk","filelocation":"\\/uploader\\/test-180926-142259\\/test.xlsx"}'

	data = json.loads( body )
	upload = Uploader(LOGGER)

	upload.initiate(data)



