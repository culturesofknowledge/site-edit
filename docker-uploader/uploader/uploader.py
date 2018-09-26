from xls_to_csv import create_csvs
import subprocess

class Uploader:

	def __init__( self, logger ):

		self.log = logger


	def initiate(self, data):

		self.log.info( "def:initiate")

		output = '/uploader/' + data['foldername']

		create_csvs( data['filelocation'], output )
		self.log.info( "...Created csvs")

		command = 'cd /usr/src/app/bin && ./runIngest.sh ' + data['foldername']

		process = subprocess.Popen(command, shell=True, stdout=subprocess.PIPE)
		process.wait()

		self.log.info( '...Ran shell process')



if __name__ == "__main__":
	import json
	import logging

	LOG_FORMAT = ('%(levelname) -10s %(asctime)s %(name) -30s %(funcName) -35s %(lineno) -5d: %(message)s')
	LOGGER = logging.getLogger(__name__)
	logging.basicConfig(level=logging.INFO, format=LOG_FORMAT)

	body = '{"foldername":"INGEST_test_Pennant_to_Skene_2015.8.28a-180926-161233","email":"matthew.wilcoxson@oerc.ox.ac.uk","filelocation":"\\/uploader\\/INGEST_test_Pennant_to_Skene_2015.8.28a-180926-161233\\/INGEST_test_Pennant_to_Skene_2015.8.28a.xlsx"}'

	data = json.loads( body )
	upload = Uploader(LOGGER)

	upload.initiate(data)



