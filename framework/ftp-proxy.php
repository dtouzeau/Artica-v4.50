<?php
// stamp 01/11/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["reconfigure-silent"])){reconfigure_silent();exit;}
writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function reconfigure_silent():bool{
	return true;
	
}

