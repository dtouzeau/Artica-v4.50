<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["cluster-key"])){CLUSTER_KEY();exit;}
session_start();



$key=$_GET["key"];
$uid=$_GET["uid"];

if(isset($_SESSION[$key])){echo "<articadatascgi>{$_SESSION[$key]}</articadatascgi>";return;}
$datas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("$key");
$_SESSION[$key]=$datas;
echo "<articadatascgi>$datas</articadatascgi>";


function CLUSTER_KEY(){
	$key=$_GET["cluster-key"];
	$datas=@file_get_contents("/etc/artica-cluster/$key");
	echo "<articadatascgi>$datas</articadatascgi>";
}

?>