<?php
session_start();
if(!GetAuth()){header('WWW-Authenticate: Basic realm="auth please"');header('HTTP/1.0 401 Unauthorized');echo "<H1>Die</H1>";exit;}

function GetAuth(){
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="auth please"');
		header('HTTP/1.0 401 Unauthorized');
		return false;
	}

	return CheckPassword($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
	return false;

}


function CheckPassword($username,$password){
	if($username==null){return false;}
	return true;

}