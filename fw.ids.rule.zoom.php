<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["signature"])){rule_settings_save();exit;}

rule_js();


function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$signature=intval($_GET["signature"]);
	$tpl->js_dialog("{IDS}: {signature} $signature","$page?rule-popup=$signature");
}

function rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$signature=intval($_GET["rule-popup"]);
	

	$array["{signature}"]="$page?rule-settings=$signature";
	//$array["{firewall_services}"]="fw.rules.services.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}";
	
	echo $tpl->tabs_default($array);

}


function rule_settings(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$signature=$_GET["rule-settings"];
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$signature'"));
	$title="{signature} {ID} <strong>$signature</strong>";
	$description=$ligne["description"];
	
	$form[]=$tpl->field_hidden("signature", $signature);
	$form[]=$tpl->field_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	$form[]=$tpl->field_checkbox("firewall", "{firewall}", $ligne["firewall"],false,"{suricata_firewall}");
	
	
	
	
	$html[]=$tpl->form_outside($title, @implode("\n", $form),$description,"{save}",null,"AsFirewallManager");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}

function rule_settings_save(){
	$q=new postgres_sql();
	$sock=new sockets();
	$sig=intval($_POST["signature"]);
	if($sig==0){echo "No signature ID\n";return;}
	$q->suricata_tables();
	$q->QUERY_SQL("UPDATE suricata_sig SET enabled='{$_POST["enabled"]}',firewall='{$_POST["firewall"]}' 
	WHERE signature='$sig'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($_POST["enabled"]==0){
		$q->QUERY_SQL("DELETE FROM suricata_events WHERE signature='$sig'");
		if(!$q->ok){echo $q->mysql_error;return;}
        $sock->REST_API("/suricata/sid/disable/$sig");
	}else{
        $sock->REST_API("/suricata/sid/enable/$sig");
		if($_POST["firewall"]==1){
			$sock->getFrameWork("suricata.php?firewall-sid=yes&sig=$sig");
		}
	}
	$sock->getFrameWork("suricata.php?restart-tail=yes");
	
}