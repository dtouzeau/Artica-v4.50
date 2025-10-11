<?php
 
/** 
 * Build nuSoap objects 
 */

$NAMESPACE = 'http://localhost/mapicontactsldapsoap';
$server = new soap_server();

$server->configureWSDL('mapicontactsldapsoap', $NAMESPACE);


/** 
 * Register SOAP functions 
 */


$server->register('getPrivateContactFolders',
		array(), 					// input parameters
		array('user'=>'tns:userArray'), 		// output parameters
		$NAMESPACE,  					// namespace
		$NAMESPACE.'#getPrivateContactFolders',		// soapaction
		'rpc', 						// style
		'encoded',					// use
		'Returns all users and their private contacts' 	// documentation
);

$server->register('getPublicContactFolders',
		array(), 					// input parameters
		array('user'=>'tns:folderArray'), 		// output parameters
		$NAMESPACE,  					// namespace
		$NAMESPACE.'#getPublicContactFolders',		// soapaction
		'rpc', 						// style
		'encoded',					// use
		'Returns all public folders and their contacts' // documentation
);

/** 
 * Define XML types
 */

$server->wsdl->addComplexType(
	'contact',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'uid'=>array('name'=>'uid', 'type'=>'xsd:string'),
		'email1address'=>array('name'=>'email1address', 'type'=>'xsd:string'),
		'email2address'=>array('name'=>'email2address', 'type'=>'xsd:string'),
		'email3address'=>array('name'=>'email3address', 'type'=>'xsd:string'),
		'displayName'=>array('name'=>'displayName', 'type'=>'xsd:string'),
		'surname'=>array('name'=>'surname', 'type'=>'xsd:string'),
		'givenname'=>array('name'=>'givenname', 'type'=>'xsd:string'),
		'mobile'=>array('name'=>'mobile', 'type'=>'xsd:string'),
		'telephoneNumber'=>array('name'=>'telephoneNumber', 'type'=>'xsd:string'),
		'facsimileTelephoneNumber'=>array('name'=>'facsimileTelephoneNumber', 'type'=>'xsd:string'),
		'o'=>array('name'=>'o', 'type'=>'xsd:string'),
		'ou'=>array('name'=>'ou', 'type'=>'xsd:string'),
		'street'=>array('name'=>'street', 'type'=>'xsd:string'),
		'postalAddress'=>array('name'=>'postalAddress', 'type'=>'xsd:string'),
		'postalCode'=>array('name'=>'postalCode', 'type'=>'xsd:string'),
		'homePostalAddress'=>array('name'=>'homePostalAddress', 'type'=>'xsd:string'),
		'title'=>array('name'=>'title', 'type'=>'xsd:string'),
		'homePhone'=>array('name'=>'homePhone', 'type'=>'xsd:string'),
		'description'=>array('name'=>'description', 'type'=>'xsd:string'),
		'secretary'=>array('name'=>'secretary', 'type'=>'xsd:string'),
		'pager'=>array('name'=>'pager', 'type'=>'xsd:string'),
		'employeeType'=>array('name'=>'employeeType', 'type'=>'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'users',
	'complexType',
	'struct',
	'all',
	'',
	array(
        	'username'=>array('name'=>'username', 'type'=>'xsd:string'),
		'contacts'=>array('name'=>'contacts', 'type'=>'tns:contactArray')
	)
);

$server->wsdl->addComplexType(
	'folders',
	'complexType',
	'struct',
	'all',
	'',
	array(
        	'foldername'=>array('name'=>'foldername', 'type'=>'xsd:string'),
		'contacts'=>array('name'=>'contacts', 'type'=>'tns:contactArray')
	)
);

$server->wsdl->addComplexType(
	'userArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
        	array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:users[]')
	),
	'tns:users'
);

$server->wsdl->addComplexType(
	'folderArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
        	array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:folders[]')
	),
	'tns:folders'
);

$server->wsdl->addComplexType(
	'contactArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
        	array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:contact[]')
	),
	'tns:contact'
);

?>