# -*- coding: utf-8 -*-

import os.path
import warnings

import xml.etree.ElementTree as ET

# needs requests module. (http://docs.python-requests.org)
import requests
from requests.auth import HTTPBasicAuth

from .exceptions import AutoRedirectException
	
__version_info__ = ( 0, 3, 0 )
__version__  = ".".join( map(str, __version_info__) )

	

class Databank( object ) :

	'''
		Connect to a Databank implementation
		
		Responses in the form: 
			{
				'status_code': status_code of result, 
				'status' : deprecated, but as above
				'reason': reason behind status if error, 
				'results' : results from request
				'data' : additional data requested (e.g. file )
			}
		
		TODO:-
			Check imputs are valid (e.g. valid names, file exists, etc)
		
	'''
	
	def __init__(self, host, username='', password='' ):
	
		''' 
			Initiate the connection with the databank <host>. 
			
			Optionally, also specify <username> and <password>
			
			Debug will turn on debugging on the underlying requests package
		'''

		self.schema = "https://"

		self.schema = "https://"
		self.host = host
		
		self.username = username
		self.password = password
	
		self.session = requests.Session()
		
		self.session.auth = HTTPBasicAuth( username, password )
		self.session.headers = {
			'User-Agent':'Database API v' + __version__,
			'Accept' : 'application/json'
		}
		
		self.session.verify = True
	
	
	def __del__(self):
		self.session.close()
		
	
	def debug( self, debugging=True ):
	
		if debugging:
			# For debugging requests
			
			import logging

			# these two lines enable debugging at httplib level (requests->urllib3->httplib)
			# you will see the REQUEST, including HEADERS and DATA, and RESPONSE with HEADERS but without DATA.
			# the only thing missing will be the response.body which is not logged.
			import httplib
			httplib.HTTPConnection.debuglevel = 1

			logging.basicConfig() # you need to initialize logging, otherwise you will not see anything from requests
			logging.getLogger().setLevel( logging.DEBUG )
			requests_log = logging.getLogger( "requests.packages.urllib3" )
			requests_log.setLevel( logging.DEBUG )
			requests_log.propagate = True
			
		else:
			# TODO: Turn logging off...
			pass
	
	def auth( self, auth ):
		self.session.auth = auth
		
	def schema( self, schema="https://" ):
		self.schema = schema
	
	def verifyCertificate( self, verify=True ):
		self.session.verify = verify
	
	def timeout( self, timeout=60 ):
		self.session.timeout = timeout
	
	
	
	def getSilos( self ):
		''' Get a list of silos in this repository '''
		url = self._url( "/silos" )
		
		response = self._get( url )
		
		silos = []
		if Databank.responseGood( response ):
		    silos = response.json() #json.loads(respdata)
		    
		return self._create_response( response, silos )
		
		
	def createSilo( self, silo ):
		'''
			Create a silo in this repository
			
			You will need admin rights to the whole *repository* for this to succeed.
		'''
		
		url = self._url( "/admin" )
		
		payload = {}
		payload['silo'] = silo
		
		response = self._post( url )
		
		return self._create_response( response, None )
	
	
	def getDatasets( self, silo ) :
		''' 
			Get a list of datasets within the <silo>
			
			Only the first 100...
		'''
		
		url = self._url( "/" + silo )
		
		response = self._get( url )
		
		datasets = []
		if Databank.responseGood( response ) :
		    datasets = response.json()
		
		return self._create_response( response, datasets )
		
		
	def createDataset( self, silo, id, label=None, embargoed=None, embargoed_until=None ) :
		''' 
			Create a dataset with <id> in <silo> . 
			
			Optionally set a <label>, <emborgoed> and <embargoed until> (ISO8601)
		'''

		url = self._url( "/" + silo + "/datasets" )
		
		payload = {}
		
		# TODO: Check ID has only these characters  0-9a-zA-Z-_:
		payload['id'] = id
		
		if label:
			payload['title'] = label
		
		if embargoed_until :
			# TODO: Check date in ISO8601
			payload["embargoed_until"] = embargoed_until
		elif embargoed == False:
			payload["embargoed"] = "false"
		
		response = self._post( url, data=payload )
		
		databank_response = self._create_response( response, None )
		Databank.printResponse( databank_response )
		
		return databank_response
	
	
	def getDataset( self, silo, dataset ):
	
		url = self._url( "/" + silo + "/datasets/" + dataset )
		
		response = self._get( url )
		
		dataset = []
		if self.good( response ):
		    dataset = response.json()
		    
		return self._create_response( response, dataset )
	
	
	
	def getFiles( self, silo, dataset ):
		dataset = self.getDataset( silo, dataset )
		
		files = []
		if self.good( dataset ):
			tree_root = ET.fromstring( dataset.results['manifest_pretty'] )
			for aggregates in tree_root.iter('{http://www.openarchives.org/ore/terms/}aggregates'):
				files.append( aggregates.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource'] )
		   
		return self._create_response_split( dataset.status, dataset.reason, files )
	
	
	def uploadFile( self, silo, dataset, filepath, filename=None, format=None ):
		''' 
			Upload the file at <filepath> into the <dataset> in <silo>. 
			
			Optionally set a format, or give a filename to use in the dataset
		'''
		if format:
			warnings.warn("deprecated in 0.3.0, format is not used()", DeprecationWarning)
		
		
		if filename == None:
			filename =  os.path.basename( filepath )
		
		url = self._url( "/"+ silo +"/datasets/" + dataset )
		
		file_d = open( filepath, 'rb' )
		if filename:
			files = { 'file': ( filename, file_d ) }
		else:
			files = { 'file': file_d }
		
		response = self._post( url, files=files )

		file_d.close()
				
		return self._create_response_split( response.status_code, response.reason, None )
	
	
	def getFile( self, silo, dataset, filename, expect_type=None ) :
	
		if expect_type:
			warnings.warn("deprecated in 0.3.0, expect_type is not used()", DeprecationWarning)
	
		file_url = Databank.getFileUrl( silo, dataset, filename )
		
		
		url = self._url( file_url )
		
		headers = { 'Accept' : '*/*' }
		# TODO: Stream file.
		response = self._get( url, headers=headers )
		
		return self._create_response( response, dataset, response.content )
	
	
	
	def _url( self, part ) :
		return self.schema + self.host + part
	
		
	def _get( self, url, **kwargs ):
	
		response = self.session.get( url, **kwargs )		
		
		# Don't treat error codes as exceptions, we'll leave it up to the user to deal with return codes. # response.raise_for_status( )
		
		return response
		
	
	def _post( self, url, **kwargs ):
		
		code = 307 # Lets get started, a 307 redirect will continue to use POST (supposedly... but depends on server)
		
		while code == 307:
		
			response = self.session.post( url, allow_redirects=False, **kwargs )
			
			code = response.status_code
			url = response.headers["Location"] if "Location" in response.headers else url

			
		if response.status_code == 301 or response.status_code == 302 :
			# This redirect will switch to GET!
			raise AutoRedirectException( url )
		
		
		# Don't treat error codes as exceptions, we'll leave it up to the user to deal with return codes. # response.raise_for_status( )
		
		return response
	
	
	
	def _create_response( self, response, results, data=None ):
		return self._create_response_split( Databank._status_code(response), response.reason, results, data )

	def _create_response_split( self, status, reason, results, data=None ):
		return type("DatabankResponse",(), {
			'status_code' : status,
			'status' : status, # deprecated, use status_code
			'reason': reason,
			'results' : results,
			'data' : data 
		})
	
	
	@staticmethod
	def _status_code( response ):
		if hasattr( response, "status_code" ):
			return response.status_code;
			
		return response.status
	
	@staticmethod
	def getFileUrl( silo, dataset, filename ) :
		return "/" + silo + "/datasets/" + dataset + "/" + filename
	
	@staticmethod
	def printResponse( response ) :
		print "Status code : " + str( Databank._status_code(response) )
		print "Reason : " + response.reason
		print "Results : " + str( response.results )
		if response.data :
			if len( response.data ) > 100 :
				print "Data : " + str( response.data[0:100] ) + "..."
			else:
				print "Data : " + str( response.data )
	
	
	@staticmethod
	def responseGood( response, accept=[] ):
		''' 
			Check a response is considered a good one
			
			accept: list of response codes that you also consider "good" 
		'''
		
		return Databank._good( response ) or Databank._status_code( response ) in accept
	
	@staticmethod
	def good( response ):
		""" Deprecated. Use Databank.responseGood() """
		warnings.warn("deprecated in 0.3.0, use responseGood()", DeprecationWarning)
		return Databank.responseGood( response )
		
	@staticmethod
	def _good( response ):
		""" Good status codes """
		return 200 <= Databank._status_code( response ) <= 299
	
	
	@staticmethod
	def responseBad( response, reject=[] ) :
		''' 
			Check a response is considered a bad one
			
			reject: list of response codes that you also consider "bad"
		'''
		
		return Databank._bad( response ) or Databank._status_code( response ) in reject
	
	@staticmethod
	def error( response ) :
		""" Deprecated. Use not Databank.responseGood() or re-code to use Databank.responseBad() """
		warnings.warn("deprecated in 0.3.0, use not responseGood()", DeprecationWarning)
		return not Databank.responseGood( response )
		
	
	@staticmethod	
	def _bad( response ) :
		""" Bad status codes """
		return 400 <= Databank._status_code( response ) <= 599
	
	
	@staticmethod
	def _indifferent( response ): # code <= 199 or 300 <= code < 400 or code >= 600
		""" ugly? """
		return not Databank._good( response ) and not Databank._bad( response )
	
	
if __name__ == '__main__' :

	# Example Databank run
	
	import argparse
	
	parser = argparse.ArgumentParser()
	
	parser.add_argument( 'host' )
	parser.add_argument( 'username' )
	parser.add_argument( 'password' )
	parser.add_argument( '--silo', default="temp___Imaging" )
	parser.add_argument( '--dataset', default="002_test_dataset" )
	
	args = parser.parse_args()
	
	silo = args.silo
	dataset = args.dataset
	
	databank = Databank( args.host, args.username, " ".join(args.password) )
	databank.verify_certificate( False )
	
	# Get silos
	response = databank.getSilos()
	if Databank.good( response ) :
		print "We have silos : " + ",".join( response.results )
	
	
	if Databank.good( response ) and silo in response.results:
			
		# Create a dataset
		response = databank.createDataset( silo, dataset )
		if Databank.good( response ):
			print "Created dataset.",response.status_code,response.reason
	

	
