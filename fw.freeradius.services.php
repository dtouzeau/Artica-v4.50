<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
$GLOBALS["CONNECTIONS_TYPE"]["ldap"]="{ldap}";
$GLOBALS["CONNECTIONS_TYPE"]["mysql_local"]="{local_mysql}";

if($EnableActiveDirectoryFeature==1) {
    $GLOBALS["CONNECTIONS_TYPE"]["ad"] = "{ActiveDirectory}";
}


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["connection-js"])){connection_js();exit;}
if(isset($_GET["connection-popup"])){connection_popup();exit;}
if(isset($_GET["connection-popup1"])){connection_popup1();exit;}
if(isset($_GET["connection-popup2"])){connection_popup2();exit;}

if(isset($_POST["ID"])){connection_save();exit;}
if(isset($_POST["connection-type"])){connection_type_save();exit;}
if(isset($_GET["FreeRadiusEnableLocalLdap"])){FreeRadiusEnableLocalLdap();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
page();


function page(){
    $page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_FREERADIUS} &nbsp;&raquo;&nbsp; {databases}",
        "fa fas fa-database",
        "{APP_FREERADIUS_DATABASES_EXPLAIN}",
        "$page?table=yes",
        "radius-services","progress-freeradius-clients-restart",false,"table-freeradius-db-services");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_FREERADIUS} {databases}",$html);
        echo $tpl->build_firewall();
        return;
    }



    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function FreeRadiusEnableLocalLdap(){
	$sock=new sockets();
	$FreeRadiusEnableLocalLdap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusEnableLocalLdap"));
	if($FreeRadiusEnableLocalLdap==1){
		$sock->SET_INFO("FreeRadiusEnableLocalLdap", 0);
	}else{
		$sock->SET_INFO("FreeRadiusEnableLocalLdap", 1);
	}
}


function connection_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["connection-js"]);
	if($ID==0){$title="{new_connection}";}
	$tpl->js_dialog1($title, "$page?connection-popup=$ID");
	
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["delete-js"];
	$q=new mysql();
	$sql="SELECT connectionname from freeradius_db WHERE ID='$ID'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$connectionname=$ligne["connectionname"];
	$tpl->js_confirm_delete($connectionname, "delete", $ID,"$('#{$_GET["md"]}').remove()");
}

function delete_perform(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM freeradius_db WHERE ID='{$_POST["delete"]}'",'artica_backup');
	if(!$q->ok){echo $q->mysql_error;}
}


function enable_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$q=new mysql();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT enabled FROM freeradius_db WHERE ID='{$_GET["enable-js"]}'","artica_backup"));
	if(intval($ligne["enabled"])==1){
		$q->QUERY_SQL("UPDATE freeradius_db SET enabled=0 WHERE ID='{$_GET["enable-js"]}'","artica_backup");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
	}else{
		$q->QUERY_SQL("UPDATE freeradius_db SET enabled=1 WHERE ID='{$_GET["enable-js"]}'","artica_backup");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}		
	}
}

function connection_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["connection-popup"]);
	echo "<div id='connection-settings'></div><script>LoadAjax('connection-settings','$page?connection-popup1=$ID');</script>";
}
	

function connection_popup1(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["connection-popup1"]);
	
	
	if($ID==0){
		$title="{new_connection}";
		$form[]=$tpl->field_array_hash($GLOBALS["CONNECTIONS_TYPE"], "connection-type", "{type}", $_SESSION["connection-type"]);
		echo $tpl->form_outside($title, @implode("\n", $form),null,"{next}","LoadAjax('connection-settings','$page?connection-popup2=$ID');","AsSystemAdministrator");
		return;
	}
	
	echo "<script>LoadAjax('connection-settings','$page?connection-popup2=$ID');</script>";
	
}

function connection_type_save(){
	
	$_SESSION["radius"]["connection-type"]=$_POST["connection-type"];
}

function connection_popup2(){
	
	$ID=intval($_GET["connection-popup2"]);
	if($ID==0){
		$connection_type=$_SESSION["radius"]["connection-type"];
	}else{
		$q=new mysql();
		$sql="SELECT connectiontype from freeradius_db WHERE ID='$ID'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$connection_type=$ligne["connectiontype"];
	}
	
	if($connection_type=="ldap"){connection_form_ldap($ID);exit;}
	if($connection_type=="ad"){connection_form_ad($ID);exit;}
	if($connection_type=="mysql_local"){connection_form_mysql_local($ID);exit;}
	
	
	
}
function connection_form_mysql_local($connection_id){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$btname="{add}";
	$array=array();
	
	$js[]="LoadAjax('table-freeradius-db-services','$page?table=yes');";
	if($connection_id>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM freeradius_db WHERE ID=$connection_id","artica_backup"));
		$array=unserialize(base64_decode($ligne["params"]));
	}else{
		$ligne["connectionname"]="{new_connection} Local MySQL";
		$js[]="dialogInstance1.close();";
	}
	
	$form[]=$tpl->field_hidden("ID", $connection_id);
	$form[]=$tpl->field_text("connectionname", "{name}", $ligne["connectionname"]);
	$form[]=$tpl->field_info("connectiontype", "{type}", "mysql_local");
	
	echo $tpl->form_outside($ligne["connectionname"]." ($connection_id)", @implode("\n", $form),"{radius_local_mysqldb_explain}",
			$btname,@implode(";", $js),"AsSystemAdministrator");
	
}


function connection_form_ldap($connection_id){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$btname="{add}";
	$array=array();
	
	$js[]="LoadAjax('table-freeradius-db-services','$page?table=yes');";
	if($connection_id>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM freeradius_db WHERE ID=$connection_id","artica_backup"));
		$array=unserialize(base64_decode($ligne["params"]));
	}else{
		$ligne["connectionname"]="{new_connection} LDAP";
		$js[]="dialogInstance1.close();";
	}



	if($array["LDAP_FILTER"]==null){$array["LDAP_FILTER"]="(uid=%{%{Stripped-User-Name}:-%{User-Name}})";}
	if($array["PASSWORD_ATTRIBUTE"]==null){$array["PASSWORD_ATTRIBUTE"]="userPassword";}
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	$form[]=$tpl->field_hidden("ID", $connection_id);
	$form[]=$tpl->field_text("connectionname", "{name}", $ligne["connectionname"]);
	$form[]=$tpl->field_info("connectiontype", "{type}", "ldap");
	$form[]=$tpl->field_text("LDAP_SERVER", "{hostname}", $array["LDAP_SERVER"]);
	$form[]=$tpl->field_numeric("LDAP_PORT", "{ldap_port}", $array["LDAP_PORT"]);
	$form[]=$tpl->field_text("LDAP_SUFFIX", "{ldap_suffix}", $array["LDAP_SUFFIX"]);
	$form[]=$tpl->field_text("ACCESS_ATTRIBUTE", "{access_attr} (yes/no)", $array["ACCESS_ATTRIBUTE"]);
	$form[]=$tpl->field_text("PASSWORD_ATTRIBUTE", "{password_attribute}", $array["PASSWORD_ATTRIBUTE"]);
	$form[]=$tpl->field_text("LDAP_FILTER", "{ldap_filter}", $array["LDAP_FILTER"]);
	$form[]=$tpl->field_text("LDAP_DN", "{bind_dn}", $array["LDAP_DN"]);
	$form[]=$tpl->field_password("LDAP_PASSWORD", "{password}", $array["LDAP_PASSWORD"]);
	
	echo $tpl->form_outside($ligne["connectionname"]." ($connection_id)", @implode("\n", $form),"{ldap_cleartext_warn}",
			$btname,@implode(";", $js),"AsSystemAdministrator");

}

function connection_form_ad($connection_id){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$btname="{add}";
	$array=array();
	
	$js[]="LoadAjax('table-freeradius-db-services','$page?table=yes');";
	if($connection_id>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM freeradius_db WHERE ID=$connection_id","artica_backup"));
		$array=unserialize(base64_decode($ligne["params"]));
	}else{
		$ligne["connectionname"]="{new_connection} Active Directory";
		$js[]="dialogInstance1.close();";
	}

	if($array["LDAP_DN"]==null){$array["LDAP_DN"]="user@domain.tld";}

	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	$form[]=$tpl->field_hidden("ID", $connection_id);
	$form[]=$tpl->field_text("connectionname", "{name}", $ligne["connectionname"]);
	$form[]=$tpl->field_info("connectiontype", "{type}", "ad");
	$form[]=$tpl->field_text("LDAP_SERVER", "{hostname}", $array["LDAP_SERVER"]);
	$form[]=$tpl->field_numeric("LDAP_PORT", "{ldap_port}", $array["LDAP_PORT"]);
	$form[]=$tpl->field_text("LDAP_SUFFIX", "{ldap_suffix}", $array["LDAP_SUFFIX"]);
	$form[]=$tpl->field_text("ADGROUP", "{group2}", $array["ADGROUP"]);
	$form[]=$tpl->field_text("LDAP_DN", "{username}", $array["LDAP_DN"]);
	$form[]=$tpl->field_password("LDAP_PASSWORD", "{password}", $array["LDAP_PASSWORD"]);
	
	echo $tpl->form_outside($ligne["connectionname"]." ($connection_id)", @implode("\n", $form),"",
			$btname,@implode(";", $js),"AsSystemAdministrator");	
	
	
}

function connection_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["ID"];
	
	if($_POST["connectionname"]==null){$_POST["connectionname"]=time();}
	$params=base64_encode(serialize($_POST));
	
	
	if($ID==0){
		$sql="INSERT IGNORE INTO freeradius_db
		(`connectionname`,`connectiontype` ,`params`,`enabled`)
		VALUES('{$_POST["connectionname"]}','{$_POST["connectiontype"]}','$params',1)";

	}else{
	$sql="UPDATE freeradius_db SET `connectionname`='{$_POST["connectionname"]}',
			`connectiontype`='{$_POST["connectiontype"]}',`params`='$params'
			WHERE ID=$ID
			";
	}

	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

	

function client_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new mysql();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM freeradius_clients WHERE ipaddr='{$_GET["ipaddr"]}'","artica_backup"));

	if($ligne["ipaddr"]==null){
		$sql="INSERT IGNORE INTO freeradius_clients
		(`shortname`,`ipaddr` ,`nastype`,`secret`,`enabled`)
		VALUES('{$_POST["shortname"]}','{$_POST["ipaddr"]}','{$_POST["nastype"]}','{$_POST["secret"]}',1)";

	}else{
	$sql="UPDATE freeradius_clients SET `shortname`='{$_POST["shortname"]}',`nastype`='{$_POST["nastype"]}',`secret`='{$_POST["secret"]}' WHERE `ipaddr`='{$_POST["ipaddr"]}'";
	}

	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function table(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	$FreeRadiusEnableLocalLdap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusEnableLocalLdap"));
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if($EnableOpenLDAP==0){$FreeRadiusEnableLocalLdap==0;}
	
	$CONNECTIONS_TYPE=$GLOBALS["CONNECTIONS_TYPE"];

	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/freeradius.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/freeradius.restart.log";
	$ARRAY["CMD"]="freeradius.php?reload=yes";
	$ARRAY["TITLE"]="{reconfigure_service} {APP_FREERADIUS}";
	
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-freeradius-clients-restart')";
	
	$bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?connection-js=0');\"><i class='fa fa-plus'></i> {new_connection} </label>";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_settings} </label>";
    $bts[]="</div>";
	$html[]="<table id='tableau-freeradius-db-services' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{address}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT *  FROM `freeradius_db`";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$TRCLASS=null;$ligne=null;

    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));

    if($EnableActiveDirectoryFeature==1){
        $ACTIVE_DIRECTORY_LDAP_CONNECTIONS=$tpl->ACTIVE_DIRECTORY_LDAP_CONNECTIONS();
        foreach ($ACTIVE_DIRECTORY_LDAP_CONNECTIONS as $index=>$ligne){
            $html[]="<tr class='$TRCLASS' id='0none'>";
            $html[]="<td><strong>{$ligne["LDAP_SERVER"]}:{$ligne["LDAP_PORT"]} / {$ligne["LDAP_SUFFIX"]}</strong></td>";
            $html[]="<td style='width:1%' nowrap>Active Directory</a></td>";
            $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_check(1,null)."</center></td>";
            $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_nothing()."</center></td>";
            $html[]="</tr>";
        }

    }

    if($EnableOpenLDAP==1) {
        // ------------------------------ LOCAL LDAP -------------------------------------------
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $html[] = "<tr class='$TRCLASS' id='0none'>";
        $html[] = "<td><strong>{local_ldap_service} (127.0.0.1:389)</strong></td>";
        $html[] = "<td style='width:1%' nowrap>{$CONNECTIONS_TYPE["ldap"]}</a></td>";
        $html[] = "<td style='width:1%' class='center' nowrap>" . $tpl->icon_check($FreeRadiusEnableLocalLdap, "Loadjs('$page?FreeRadiusEnableLocalLdap=yes')", null, "AsSystemAdministrator") . "</center></td>";
        $html[] = "<td style='width:1%' class='center' nowrap>" . $tpl->icon_nothing() . "</center></td>";
        $html[] = "</tr>";
        // --------------------------------------------------------------------------------------
    }


	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}	
		$ID=intval($ligne["ID"]);
		$md=md5(serialize($ligne));
		$servicename=$ligne["connectionname"];
		$connectiontype=$ligne["connectiontype"];
		$ID=$ligne["ID"];
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>". $tpl->td_href("$servicename",null,"Loadjs('$page?connection-js=$ID')")."</strong></td>";
		$html[]="<td style='width:1%' nowrap>{$CONNECTIONS_TYPE[$connectiontype]}</a></td>";
		
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsSystemAdministrator")."</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsSystemAdministrator")."</center></td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{APP_FREERADIUS} &nbsp;&raquo;&nbsp; {databases}";
    $TINY_ARRAY["ICO"]="fa fas fa-database";
    $TINY_ARRAY["EXPL"]="{APP_FREERADIUS_DATABASES_EXPLAIN}";
    $TINY_ARRAY["URL"]=null;
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#tableau-freeradius-db-services').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}