<?php

if(isset($_GET["debug"])){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}



if (!function_exists('apache_request_headers')) {
	function apache_request_headers() {
		foreach($_SERVER as $key=>$value) {
			if (substr($key,0,5)=="HTTP_") {
				$key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
				$out[$key]=$value;
			}else{
				$out[$key]=$value;
			}
		}
		return $out;
	}
}

if(!isset($_SESSION['uid'])){RequestsAuth();exit;}
if($_SESSION['uid']=="-100"){$_SESSION['uid']=null;}
if($_SESSION['uid']==null){RequestsAuth();exit;}


function RequestsAuth(){

	$headers = apache_request_headers(); // Récupération des l'entêtes client
	if(!isset($_SERVER['HTTP_VIA'])){$_SERVER['HTTP_VIA']=null;}
	if ($_SERVER['HTTP_VIA'] != NULL){ echo "Proxy bypass!";die("DIE " .__FILE__." Line: ".__LINE__);}
	
	
	if($headers['Authorization'] == NULL){ //si l'entete autorisation est inexistante
		header( "HTTP/1.0 401 Unauthorized" ); //envoi au client le mode d'identification
		header( "WWW-Authenticate: NTLM" ); //dans notre cas le NTLM
		exit; //on quitte
	}
	
	if($_SERVER['PHP_AUTH_USER']<>null){
		$_SESSION['uid']=$_SERVER['PHP_AUTH_USER'];
		//$_SERVER['PHP_AUTH_PW'];
		$page=CurrentPageName();
		header("Location: $page");
		return;
	}


	if(isset($headers['Authorization'])){
			if(substr($headers['Authorization'],0,5) == 'NTLM '){ 
				$chaine=$headers['Authorization'];
				$chaine=substr($chaine, 5); // recuperation du base64-encoded type1 message
				$chained64=base64_decode($chaine); // decodage base64 dans $chained64
				if(ord($chained64{8}) == 1){
					// |_ byte signifiant l'etape du processus d'identification (etape 3)
					// verification du drapeau NTLM "0xb2" à l'offset 13 dans le message type-1-message (comp ie 5.5+) :
					if (ord($chained64[13]) != 178){
						header('HTTP/1.0 401 Unauthorized');
						header('WWW-Authenticate: Basic realm="Basic Auth"');
						exit;
					}
					$retAuth = "NTLMSSP".chr(000).chr(002).chr(000).chr(000).chr(000).chr(000).chr(000).chr(000);
					$retAuth .= chr(000).chr(040).chr(000).chr(000).chr(000).chr(001).chr(130).chr(000).chr(000);
					$retAuth .= chr(000).chr(002).chr(002).chr(002).chr(000).chr(000).chr(000).chr(000).chr(000);
					$retAuth .= chr(000).chr(000).chr(000).chr(000).chr(000).chr(000).chr(000);
					$retAuth64 =base64_encode($retAuth); // encode en base64
					$retAuth64 = trim($retAuth64); // enleve les espaces de debut et de fin
					header( "HTTP/1.0 401 Unauthorized" ); // envoi le nouveau header
					header( "WWW-Authenticate: NTLM $retAuth64" ); // avec l'identification supplémentaire
					exit;
			}
		else if(ord($chained64{8}) == 3){
					// |_ byte signifiant l'etape du processus d'identification (etape 5)
					// on recupere le domaine
					$lenght_domain = (ord($chained64[31])*256 + ord($chained64[30])); // longueur du domain
					$offset_domain = (ord($chained64[33])*256 + ord($chained64[32])); // position du domain.
					$domain = str_replace("\0","",substr($chained64, $offset_domain, $lenght_domain)); // decoupage du du domain
					//le login
					$lenght_login = (ord($chained64[39])*256 + ord($chained64[38])); // longueur du login.
					$offset_login = (ord($chained64[41])*256 + ord($chained64[40])); // position du login.
					$login = str_replace("\0","",substr($chained64, $offset_login, $lenght_login)); // decoupage du login
					if ( $login != NULL){
							$_SESSION['uid']=$login;
							return;
						}
				else{
					echo "NT Login empty!";
				}
			}
		}
	}
	
}
?>