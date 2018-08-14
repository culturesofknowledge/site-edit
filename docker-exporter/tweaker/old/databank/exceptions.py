# -*- coding: utf-8 -*-

from requests.exceptions import RequestException

# Databank will also throw several exceptions from requests
# see http://docs.python-requests.org/en/latest/api/#exceptions (or https://github.com/kennethreitz/requests/blob/master/requests/exceptions.py)
#
# The exceptions are likely to be just of this set:
#   - HTTPError
#   - ConnectionError
#   - Timeout
#   - SSLError

class AutoRedirectException( RequestException ):
	"""
		A redirection from a POST to a GET method has occured.
	"""
	def __init__( self, url ):
		self.url = url
	def __str__(self):
		return "Automatic redirection to " + self.url + " is not possible. Please update the URL."