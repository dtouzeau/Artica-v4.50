<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Contrôle de droits côté serveur : form_outside(…,"AsFirewallManager") ne le fait que dans le navigateur.
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Réinjectés tels quels dans des URL et du JavaScript (Loadjs, <script>) :
// normalisés une fois ici.
$FW_OBJECTS_TABLES=array("firewallfilter_sqacllinks"=>true,"qos_sqacllinks"=>true);
if(isset($_GET["table-link"]) && !isset($FW_OBJECTS_TABLES[$_GET["table-link"]])){$_GET["table-link"]="firewallfilter_sqacllinks";}
if(isset($_GET["refresh-function"])){$_GET["refresh-function"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["refresh-function"]);}
if(isset($_GET["ruleid"])){$_GET["ruleid"]=intval($_GET["ruleid"]);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["popup-new"])){popup_new();exit;}
if(isset($_GET["js-new"])){js_new();exit;}
if(isset($_POST["object-new"])){create_object();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{firewall_objects} ({link_object})", "$page?popup=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}",650);
}
function js_new(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{firewall_objects} ({new_object})", "$page?popup-new=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}",650);
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$ID=intval($_GET["ruleid"]);
	$direction=intval($_GET["direction"]);
	$btname="{link_object}";
	$title="{link_object}";

	$mqb=new mysql_squid_builder();
	$results=acls_api_get("/proxy/acls/sqlite/groups/all");
	$c=0;
	if(is_array($results)){
		foreach ($results as $ligne){
			if(!isset($mqb->acl_GroupType_iptables[$ligne["groupType"]])){continue;}
			$c++;
			$MAIN[$ligne["id"]]=$tpl->_ENGINE_parse_body("{$ligne["groupName"]}: {{$ligne["groupType"]}}");
		}
	}

	if($c==0){
		echo "<script>dialogInstance6.close();Loadjs('$page?js-new=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}')</script>";
		return;
	}

	// Pentest 2026-11-04 : refresh-function etait recopie sans encodage dans la page (XSS).
	$backjs=jsafter_sanitize(base64_decode($_GET["refresh-function"]));
	$tpl->field_hidden("object-link", $ID);
	if(isset($_GET["direction"])){ $tpl->field_hidden("direction", $direction);}
	$form[]=$tpl->field_array_hash($MAIN,"gpid","{object}",null,true);
	$tpl->form_add_button("{new_object}","Loadjs('$page?js-new=yes&table-link={$_GET["table-link"]}&refresh-function={$_GET["refresh-function"]}&ruleid={$_GET["ruleid"]}')");
	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs,"AsFirewallManager");
}

function popup_new(){
	$page=CurrentPageName();
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$title="{new_object}";
	$btname="{add}";
	// Pentest 2026-11-04 : refresh-function etait recopie sans encodage dans la page (XSS).
	$backjs="dialogInstance6.close();".jsafter_sanitize(base64_decode($_GET["refresh-function"]));

	$tpl->field_hidden("object-new", "yes");
	if(isset($_GET["direction"])){ $tpl->field_hidden("direction", $direction);}
	if(isset($_GET["table-link"])){ $tpl->field_hidden("table", $_GET["table-link"]);}
	if(isset($_GET["ruleid"])){ $tpl->field_hidden("ruleid", $_GET["ruleid"]);}
	$form[]=$tpl->field_text("GroupName","{groupname}","{new_group}");
	$form[]=$tpl->field_array_hash($q->acl_GroupType_iptables,"GroupType","{type}",null,true);
	$tpl->form_add_button("{cancel}","dialogInstance6.close()");
	$html=$tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs,"AsFirewallManager");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
	echo $html;

}

function create_object(){
	$direction=0;
	$table="firewallfilter_sqacllinks";
	$GroupName=url_decode_special_tool($_POST["GroupName"]);
	$GroupName=utf8_decode($GroupName);
	$GroupName=mysql_escape_string2($GroupName);
	$GroupType=$_POST["GroupType"];
    // Seules les deux tables de liaison des pages appelantes (règles du
    // pare-feu, règles QoS) : le nom partait tel quel vers l'API des ACL.
    if(isset($_POST["table"]) && isset($GLOBALS["FW_OBJECTS_TABLES"][$_POST["table"]])){$table=$_POST["table"];}
    if(isset($_POST["direction"])){$direction=intval($_POST["direction"]);}

	list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/group",[
		"name"   => $GroupName,
		"type"   => $GroupType,
		"params" => "",
	]);
	if(!$ok){echo (new template_admin())->post_error("$data Line:".__LINE__);return;}
	$gpid=intval($data["id"] ?? 0);
	if($gpid==0){echo (new template_admin())->post_error("no id returned Line:".__LINE__);return;}

	$aclid=intval($_POST["ruleid"]);

	writelogs("$GroupName: last GPID=$gpid",__FUNCTION__,__FILE__,__LINE__);

	list($ok2,$data2)=acls_api_send("POST","/proxy/acls/sqlite/link",[
		"table"     => $table,
		"aclid"     => $aclid,
		"gpid"      => $gpid,
		"direction" => $direction,
	]);
	if(!$ok2){echo (new template_admin())->post_error("$data2 Line:".__LINE__);return;}
}

