<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-ports"])){rule_ports();exit;}
if(isset($_POST["rule-ports"])){rule_ports_save();exit;}

if(isset($_GET["rule-sources"])){rule_sources();exit;}
if(isset($_POST["rule-sources"])){rule_sources_save();exit;}

if(isset($_GET["rule-sourcesex"])){rule_sourcesex();exit;}
if(isset($_POST["rule-sourcesex"])){rule_sourcesex_save();exit;}

if(isset($_GET["rule-dst"])){rule_dst();exit;}
if(isset($_POST["rule-dst"])){rule_dst_save();exit;}

if(isset($_GET["rule-dstex"])){rule_dstex();exit;}
if(isset($_POST["rule-dstex"])){rule_dstex_save();exit;}

js();

function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=$_GET["ruleid"];
	$ligne=$q->mysqli_fetch_array("SELECT servicename FROM `3proxy_services` WHERE ID=$ID");
	$title="{$ligne["servicename"]}: {transparent}";
	$tpl->js_dialog7($title,"$page?rule-tabs=$ID",950);
}

function rule_tabs(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=intval($_GET["rule-tabs"]);
	
	$array["{ports}"]="$page?rule-ports=$ID";
	$array["{sources}"]="$page?rule-sources=$ID";
	$array["{destinations}"]="$page?rule-dst=$ID";
	$array["{whitelisted_src_networks}"]="$page?rule-sourcesex=$ID";
	$array["{whitelisted_destination_networks}"]="$page?rule-dstex=$ID";
	
	echo $tpl->tabs_default($array);
	
	
	
}

function rule_ports_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["rule-ports"]);
	$f=explode("\n",$_POST["data"]);
	foreach ($f as $port){
		if(!is_numeric($port)){continue;}
		$newf[]=$port;
	}
	
	$newdata=base64_encode(@implode("\n", $newf));
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("UPDATE `3proxy_services` SET `transparentport`='$newdata' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function rule_sources_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$ID=intval($_POST["rule-sources"]);
	$f=explode("\n",$_POST["data"]);
	foreach ($f as $port){
		if(!$ipclass->isIPAddressOrRange($port)){continue;}
		$newf[]=$port;
	}
	
	$newdata=base64_encode(@implode("\n", $newf));
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("UPDATE `3proxy_services` SET `transparentin`='$newdata' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}

}
function rule_sourcesex_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$ID=intval($_POST["rule-sourcesex"]);
	$f=explode("\n",$_POST["data"]);
	foreach ($f as $port){
		if(!$ipclass->isIPAddressOrRange($port)){continue;}
			$newf[]=$port;
	}
	
	$newdata=base64_encode(@implode("\n", $newf));
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("UPDATE `3proxy_services` SET `excludetransparentin`='$newdata' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}	
	
}
function rule_dstex_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$ID=intval($_POST["rule-dstex"]);
	$f=explode("\n",$_POST["data"]);
	foreach ($f as $port){
		if(!$ipclass->isIPAddressOrRange($port)){continue;}
			$newf[]=$port;
	}

	$newdata=base64_encode(@implode("\n", $newf));
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("UPDATE `3proxy_services` SET `excludetransparentout`='$newdata' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}

}
function rule_dst_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$ID=intval($_POST["rule-dst"]);
	$f=explode("\n",$_POST["data"]);
	foreach ($f as $port){
		if(!$ipclass->isIPAddressOrRange($port)){continue;}
		$newf[]=$port;
	}

	$newdata=base64_encode(@implode("\n", $newf));
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("UPDATE `3proxy_services` SET `transparentout`='$newdata' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}

}
function rule_dst(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=intval($_GET["rule-dst"]);
	$ligne=$q->mysqli_fetch_array("SELECT servicename,transparentout FROM `3proxy_services` WHERE ID=$ID");
	$data=base64_decode($ligne["transparentout"]);
	$tpl->field_hidden("rule-dst", $ID);
	$form[]=$tpl->field_textareacode("data", null, $data);
	echo $tpl->form_outside("{$ligne["servicename"]} &raquo;&raquo; {dst}",$form,"{transparentout_explain}" ,"{apply}",
	"LoadAjax('table-3proxy-table','fw.3proxy.services.php?table=yes');","AsFirewallManager");
	
}
function rule_dstex(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=intval($_GET["rule-dstex"]);
	$ligne=$q->mysqli_fetch_array("SELECT servicename,excludetransparentout FROM `3proxy_services` WHERE ID=$ID");
	$data=base64_decode($ligne["excludetransparentout"]);
	$tpl->field_hidden("rule-dstex", $ID);
	$form[]=$tpl->field_textareacode("data", null, $data);
	echo $tpl->form_outside("{$ligne["servicename"]} &raquo;&raquo; {whitelisted_destination_networks}",$form,"{excludetransparentout_explain}" ,"{apply}",
	"LoadAjax('table-3proxy-table','fw.3proxy.services.php?table=yes');","AsFirewallManager");

}

function rule_sources(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=intval($_GET["rule-sources"]);
	$ligne=$q->mysqli_fetch_array("SELECT servicename,transparentin FROM `3proxy_services` WHERE ID=$ID");
	$data=base64_decode($ligne["transparentin"]);	
	$tpl->field_hidden("rule-sources", $ID);
	$form[]=$tpl->field_textareacode("data", null, $data);
	echo $tpl->form_outside("{$ligne["servicename"]} &raquo;&raquo; {src}",$form,"{transparentin_explain}" ,"{apply}",
	"LoadAjax('table-3proxy-table','fw.3proxy.services.php?table=yes');","AsFirewallManager");
}
function rule_sourcesex(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=intval($_GET["rule-sources"]);
	$ligne=$q->mysqli_fetch_array("SELECT servicename,excludetransparentin FROM `3proxy_services` WHERE ID=$ID");
	$data=base64_decode($ligne["excludetransparentin"]);
	$tpl->field_hidden("rule-sourcesex", $ID);
	$form[]=$tpl->field_textareacode("data", null, $data);
	echo $tpl->form_outside("{$ligne["servicename"]} &raquo;&raquo; {whitelisted_src_networks}",$form,"{excludetransparentin_explain}" ,"{apply}",
	"LoadAjax('table-3proxy-table','fw.3proxy.services.php?table=yes');","AsFirewallManager");
}


function rule_ports(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=intval($_GET["rule-ports"]);
	$ligne=$q->mysqli_fetch_array("SELECT servicename,transparentport FROM `3proxy_services` WHERE ID=$ID");
	$data=base64_decode($ligne["transparentport"]);
	
	$tpl->field_hidden("rule-ports", $ID);
	$form[]=$tpl->field_textareacode("data", null, $data);
	echo $tpl->form_outside("{$ligne["servicename"]} &raquo;&raquo; {ports}",$form,"{transparentport_explain}" ,"{apply}",
	"LoadAjax('table-3proxy-table','fw.3proxy.services.php?table=yes');","AsFirewallManager");
}