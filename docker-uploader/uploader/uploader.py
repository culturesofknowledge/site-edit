from xls_to_csv import create_csvs
import subprocess

class Uploader:

	def __init__( self, logger ):

		self.log = logger


	def initiate(self, data):

		error = None
		output = None

		self.log.info( "def:initiate")

		output_folder = '/uploader/' + data['foldername']

		errors = create_csvs( data['filelocation'], output_folder )

		if len(errors) == 0 :

			self.log.info( "...Created csvs")

			command = 'cd /usr/src/app/bin && ./runIngest.sh ' + data['foldername']
			# command = ['cd', '/usr/src/app/bin', '&&', './runIngest.sh', data['foldername'] ]

			# process = subprocess.Popen(command, shell=True, stdout=subprocess.PIPE)
			# process.wait()
			output = subprocess.check_output( command, stderr=subprocess.STDOUT, shell=True, universal_newlines=True )

			output = 'Data processed. \n' + output
			self.log.info( '...Ran shell process')
		else :
			error = "\n".join( errors )

		return output, error

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



