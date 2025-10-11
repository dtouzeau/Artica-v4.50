<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$logfile=urlencode($_GET["logfile"]);
	$title=urlencode($_GET["title"]);
	$tpl->js_dialog($_GET["title"], "$page?popup=$logfile&title=$title");
	
}

function popup(){
	$tpl=new template_admin();
	
	if(is_file(PROGRESS_DIR."/squid-config-failed.tar.gz")){
        $form[]=$tpl->_ENGINE_parse_body("<div style='text-align:right;margin:10px'>".$tpl->button_autnonome("{support_package}","document.location.href='ressources/logs/web/squid-config-failed.tar.gz'","fas fa-ambulance",null,0,"btn-warning")."</div>");

    }
    $path=canonicalize($_GET["popup"]);
	if(!preg_match("#^\/usr\/share\/artica-postfix#",$path)){die(" Directory Traversal not permitted");}
	$form[]="<textarea style='width:100%;height:700px;overflow:auto'>".@file_get_contents($_GET["popup"])."</textarea>";
	
	echo @implode("\n",$form);
}

function canonicalize($address)
{
    $address = explode('/', $address);
    $keys = array_keys($address, '..');

    foreach($keys AS $keypos => $key)
    {
        array_splice($address, $key - ($keypos * 2 + 1), 2);
    }

    $address = implode('/', $address);
    $address = str_replace('./', '', $address);
    return $address;
}