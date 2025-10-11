<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");

	$html="
	<div class='row'><div id='progress-unboundstats-restart'></div>
		<div class='ibox-content'>
			<div id='table-loader-unboundstats-servers'></div>
		</div>
	



	<script>
	LoadAjax('table-loader-unboundstats-servers','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$t=time();
	$f[]="unbound_munin_hits-day.png";
	$f[]="unbound_munin_hits-month.png";
	$f[]="unbound_munin_hits-week.png";
	$f[]="unbound_munin_hits-year.png";
	
	$OUTPUT=false;
	foreach ($f as $image){if(is_file("$path/$image")){$tt[]="<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";}}
	
	if(count($tt)>0){
		$OUTPUT=true;
		echo $tpl->_ENGINE_parse_body("<H2>HITS</H2>".@implode("\n", $tt));
	}
	
	
	$tt=array();
	$f=array();
	$f[]="unbound_munin_memory-day.png";
	$f[]="unbound_munin_memory-month.png";
	$f[]="unbound_munin_memory-week.png";
	$f[]="unbound_munin_memory-year.png";
	
	foreach ($f as $image){if(is_file("$path/$image")){$tt[]="<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";}}
	if(count($tt)>0){
		$OUTPUT=true;
		echo $tpl->_ENGINE_parse_body("<H2>{memory}</H2>".@implode("\n", $tt));
	}

	$tt=array();
	$f=array();
	$f[]="unbound_munin_by_class-day.png";
	$f[]="unbound_munin_by_class-month.png";
	$f[]="unbound_munin_by_class-week.png";
	$f[]="unbound_munin_by_class-year.png";
	foreach ($f as $image){if(is_file("$path/$image")){$tt[]="<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";}}
	if(count($tt)>0){
		$OUTPUT=true;
		echo $tpl->_ENGINE_parse_body("<H2>QUERIES CLASS</H2>".@implode("\n", $tt));
	}	
	
	$tt=array();
	$f=array();
	$f[]="unbound_munin_by_type-day.png";
	$f[]="unbound_munin_by_type-month.png";
	$f[]="unbound_munin_by_type-week.png";
	$f[]="unbound_munin_by_type-year.png";
	foreach ($f as $image){if(is_file("$path/$image")){$tt[]="<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";}}
	if(count($tt)>0){
		$OUTPUT=true;
		echo $tpl->_ENGINE_parse_body("<H2>QUERIES TYPE</H2>".@implode("\n", $tt));
	}
	
	$tt=array();
	$f=array();
	$f[]="unbound_munin_by_flags-day.png";
	$f[]="unbound_munin_by_flags-month.png";
	$f[]="unbound_munin_by_flags-week.png";
	$f[]="unbound_munin_by_flags-year.png";
	$f[]="unbound_munin_by_opcode-day.png";
	$f[]="unbound_munin_by_opcode-month.png";
	$f[]="unbound_munin_by_opcode-week.png";
	$f[]="unbound_munin_by_opcode-year.png";
	$f[]="unbound_munin_by_rcode-day.png";
	$f[]="unbound_munin_by_rcode-month.png";
	$f[]="unbound_munin_by_rcode-week.png";
	$f[]="unbound_munin_by_rcode-year.png";

	$f[]="unbound_munin_histogram-day.png";
	$f[]="unbound_munin_histogram-month.png";
	$f[]="unbound_munin_histogram-week.png";
	$f[]="unbound_munin_histogram-year.png";


	$f[]="unbound_munin_queue-day.png";
	$f[]="unbound_munin_queue-month.png";
	$f[]="unbound_munin_queue-week.png";
	$f[]="unbound_munin_queue-year.png";
	
	foreach ($f as $image){if(is_file("$path/$image")){$tt[]="<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";}}
	if(count($tt)>0){
		$OUTPUT=true;
		echo $tpl->_ENGINE_parse_body("<H2>{other}</H2>".@implode("\n", $tt));
	}
	
	if(!$OUTPUT){
		
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	
	
}
