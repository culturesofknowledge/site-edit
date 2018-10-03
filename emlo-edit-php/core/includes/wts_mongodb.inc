<?php

	$serverMongo  = 'uploadermongo';
	$serverPgSQL  = 'postgres:5432';

	//try{
		//$mongodb  = new MongoDBClient($serverMongo);
		$database = new \MongoDB\Driver\Manager( 'mongodb://uploadermongo' );//$mongodb->selectDB('emlo-edit');
		//$N_addressee			='kircher_addressee';
		//$N_author       	='kircher_author';
		//$N_location   		='kircher_location';
		//$N_manifestation	='kircher_manifestation';
		//$N_person         ='kircher_person';
		//$N_work 					='kircher_work';
		//$N_upload 				='upload';
		//$addressee 				= $database->selectCollection($N_addressee);
		//$author           = $database->selectCollection($N_author);
		//$location   			= $database->selectCollection($N_location);
		//$manifestation    = $database->selectCollection($N_manifestation);
		//$person         	= $database->selectCollection($N_person);
		//$work 						= $database->selectCollection($N_work);
		//$upload 					= $database->selectCollection($N_upload);
	//} catch (Exception | MongoConnectionException $e) {
	//	die(get_class($e) . ' Failed to connect to MongoDB ' . $e->getMessage());
	//}
