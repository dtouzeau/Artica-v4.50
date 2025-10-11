<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.main_cf.inc');


if(count($_POST)==0){return null;}

$main=new main_cf();


foreach ($_POST as $num=>$val){
	
	writelogs("receive from POST[$num]=$val",__FUNCTION__,__FILE__);
	$main->main_array[$num]=$val;
	}

$main->save_conf();
echo "Data saved"

?>


