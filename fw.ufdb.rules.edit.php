<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once("ressources/class.ldap-extern.inc");

if(isset($_POST["ID"])){rule_edit_save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tab"])){tabs();exit;}

js();

function js(){
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	if($ID==0){$rulename="{default}";}
	if($ID==-1){$rulename="{new_rule}";}
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
		$rulename=$ligne["groupname"];
	}
	$tpl=new template_admin();
	$tpl->js_dialog($rulename, "$page?tab=$ID");
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["tab"];
	$array["{rule}"]="$page?popup=$ID";
	if($ID>-1){

        $array["{blacklists}"]="fw.ufdb.rules.categories.php?ID=$ID&modeblk=0";
        $array["{whitelists}"]="fw.ufdb.rules.categories.php?ID=$ID&modeblk=1";
		$array["{period}"]="fw.ufdb.rules.time.php?ID=$ID";
	}
	echo $tpl->tabs_default($array);
}

function popup(){
	
	$ID=$_GET["popup"];
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	if(!$q->FIELD_EXISTS("webfilter_rules", "UseSecurity")){$q->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `UseSecurity` smallint(1),ADD INDEX ( `UseSecurity` )");}
	$groupmode[0]="{banned}";
	$groupmode[1]="{filtered}";
	$groupmode[2]="{exception}";
	$button_name="{apply}";
	$close=null;
	if($ID<0){$button_name="{add}";}
	$sock=new sockets();
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}

	
	$ENDOFRULES[null]="{select}";
	$ENDOFRULES["any"]="{ufdb_any}";
	$ENDOFRULES["none"]="{ufdb_none}";
	$AsDefaultRule=false;	
	$FORM_EXPLAIN=null;
	
	
	if($ID>0){
		$sql="SELECT * FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	
	}else{
		if($ID==0){
			$sock=new sockets();
			$ligne=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
			$ligne["groupname"]="default";
			$DefaultPosition=$ligne["defaultPosition"];
			if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
			$AsDefaultRule=true;
		}
		
		if($ID==-1){
			$ligne["groupname"]="Rule_".time();
			$ligne["enabled"]=1;
			$close="BootstrapDialog1.close();";
		}
	}

	if(!isset($ligne["endofrule"])){$ligne["endofrule"]="any";}
    if(!isset($ligne["groupmode"])){$ligne["groupmode"]=1;}
    if(!isset($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=50;}
    if(!isset($ligne["embeddedurlweight"])){$ligne["embeddedurlweight"]=0;}
    if(!isset($ligne["GoogleSafeSearch"])){$ligne["GoogleSafeSearch"]=0;}
    if(!isset($ligne["UseExternalWebPage"])){$ligne["UseExternalWebPage"]=0;}
    if(!isset($ligne["UseSecurity"])){$ligne["UseSecurity"]=0;}

	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["searchtermlimit"])){$ligne["searchtermlimit"]=30;}
	if(!is_numeric($ligne["bypass"])){$ligne["bypass"]=0;}
	if(!is_numeric($ligne["groupmode"])){$ligne["groupmode"]=1;}
	if(!is_numeric($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=50;}
	if(!is_numeric($ligne["embeddedurlweight"])){$ligne["embeddedurlweight"]=0;}
	if(!is_numeric($ligne["GoogleSafeSearch"])){$ligne["GoogleSafeSearch"]=0;}
	if(!is_numeric($ligne["UseExternalWebPage"])){$ligne["UseExternalWebPage"]=0;}
	if(!is_numeric($ligne["UseSecurity"])){$ligne["UseSecurity"]=0;}
	
	if(!isset($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!isset($ligne["AllSystems"])){$ligne["AllSystems"]=0;}
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}

	
	
	if($ligne["AllSystems"]==1){
		$FORM_EXPLAIN="{AllSystemsDansExpl}";
	}

	if($ligne["groupmode"]==0){
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger'><strong>{navigation_banned}</strong><br>{navigation_banned_text}</div>");
	}
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$FORM_TITLE="{rule}: {$ligne["groupname"]}";
	if($AsDefaultRule){
		$arrayPos[0]="{at_the_top_rules}";
		$arrayPos[1]="{at_the_end_of_rules}";
		
		$form[]=$tpl->field_hidden("groupname", "default");
		$form[]=$tpl->field_hidden("enabled", "1");
		$form[]=$tpl->field_hidden("AllSystems", "0");
		$FORM_TITLE="{rule_name}: {default}";
		$form[]=$tpl->field_array_hash($arrayPos, "defaultPosition", "{position}", $DefaultPosition);
	}
	
	
	if(!$AsDefaultRule){
		$form[]=$tpl->field_text("groupname", "{rule_name}", $ligne["groupname"]);
		$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
		$form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);
		$form[]=$tpl->field_checkbox("AllSystems","{AllSystems}",$ligne["AllSystems"],false);
	}
	
	$form[]=$tpl->field_array_hash($groupmode, "groupmode", "{rule_behavior}", $ligne["groupmode"]);
	$form[]=$tpl->field_array_hash($ENDOFRULES, "endofrule", "{finish_rule_by}", $ligne["endofrule"]);
	
	echo $tpl->form_outside($FORM_TITLE, @implode("\n", $form),$FORM_EXPLAIN,$button_name,"LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');$close","AsDansGuardianAdministrator");

}
function rule_edit_save(){
	$ID=$_POST["ID"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
    $sock=new sockets();
    $tpl->CLEAN_POST();

	
	writelogs("Save ruleid `$ID`",__FUNCTION__,__FILE__,__LINE__);

	if($ID==0){
		writelogs("Default rule, loading DansGuardianDefaultMainRule",__FUNCTION__,__FILE__,__LINE__);
		$DEFAULTARRAY=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
	}
	unset($_POST["ID"]);


	if(strtolower($_POST["groupname"])=="default"){$_POST["groupname"]=null;}

	if($_POST["groupname"]==null){$_POST["groupname"]=time();}
	$_POST["groupname"]=strtolower(replace_accents($_POST["groupname"]));
	$_POST["groupname"]=str_replace("$", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("(", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(")", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("[", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("]", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("%", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("!", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(":", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(";", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(",", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("£", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("~", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("`", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('\\', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('/', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('+', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('=', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('*', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('&', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('"', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('{', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('}', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('|', "", $_POST["groupname"]);

	$sql="SELECT ID FROM webfilter_rules WHERE `groupname`='{$_POST["groupname"]}' AND ID != $ID";
	$results=$q->QUERY_SQL($sql);
	$mysql_num_rows=count($results);

	if($mysql_num_rows>0){
		$Groupname=$_POST["groupname"];
		$_POST["groupname"] = "$Groupname - ".(intval($mysql_num_rows)+1);

	}

	foreach ($_POST as $num=>$ligne){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".$tpl->utf8_encode($ligne)."'";
		$fieldsEDIT[]="`$num`='".$tpl->utf8_encode($ligne)."'";
		$DEFAULTARRAY[$num]=$ligne;
	}

	if($ID==0){
		$sock=new sockets();
		$sock->SaveConfigFile(base64_encode(serialize($DEFAULTARRAY)), "DansGuardianDefaultMainRule");
		return;
	}

	$sql_edit="UPDATE webfilter_rules SET ".@implode(",", $fieldsEDIT)." WHERE ID=$ID";
	$sql_add="INSERT INTO webfilter_rules (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";

	if($ID<0){$s=$sql_add;$build=true;}else{$s=$sql_edit;}
	$q->QUERY_SQL($s);

	if(!$q->ok){echo $q->mysql_error."\n$q->mysql_error\n$s\n";return;}
		$c=0;
		$sql="SELECT ID FROM webfilter_rules ORDER BY zOrder";
		$results = $q->QUERY_SQL($sql);
		
		foreach ($results as $index=>$ligne){
			$q->QUERY_SQL("UPDATE webfilter_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
			$c++;
		}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");

}