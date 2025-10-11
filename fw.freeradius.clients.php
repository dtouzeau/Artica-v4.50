<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["client-js"])){client_js();exit;}
if(isset($_GET["client-popup"])){client_popup();exit;}
if(isset($_GET["enable-js"])){client_enable_js();exit;}
if(isset($_POST["ipaddr"])){client_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
if(isset($_GET["search-section"])){search_section();exit;}
if(isset($_GET["client-import"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_POST["import"])){import_save();exit;}
page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_FREERADIUS} &nbsp;&raquo;&nbsp; {radius_clients}",
    "fa fa-desktop","{APP_FREERADIUS_CLIENTS_EXPLAIN}","$page?search-section=yes","radius-clients","progress-freeradius-clients-restart",false,"table-freeradius-clients-services");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_FREERADIUS} {databases}",$html);
        echo $tpl->build_firewall();
        return true;
    }



    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function search_section():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}
function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{new_radius_client}:{import}";
    $function=$_GET["function"];
    $tpl->js_dialog1("modal:$title", "$page?import-popup=$function");
    return true;

}
function import_popup(){
    $function=$_GET["import-popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_textarea("import","","Friendly Name IP Address Device Manufacturer NAP-Capable Status");
    $jsafter="$function();dialogInstance1.close();";
    echo $tpl->form_outside(null, @implode("\n", $form),
        "https://wiki.articatech.com/system/radius/import-clients","{import}",$jsafter,"AsSystemAdministrator");
}
function import_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import"]);
    $q=new lib_sqlite("/home/artica/SQLITE/radius.db");
    if(!$q->FIELD_EXISTS("freeradius_clients","nap_capable")){
        $q->QUERY_SQL("ALTER TABLE freeradius_clients ADD nap_capable INTEGER NOT NULL DEFAULT 0");
    }

    foreach ($tb as $line){
        if(!preg_match("#^(.+?)\s+([0-9\.\/]+)\s+(.+?)\s+(No|no|Yes|yes)\s+(Enabled|enabled|Disabled|disabled|active|inactive)#i",$line,$re)){continue;}
        $enabled=0;
        $nap=0;
        $shortname=$re[1];
        $ipaddr=$re[2];
        $nastype=$re[3];

        if(preg_match("#(Yes|yes)#",$re[5])){
            $nap=1;

        }

        if(preg_match("#(Enabled|enabled|active)#",$re[5])){
            $enabled=1;

        }

        $sql="INSERT OR IGNORE INTO freeradius_clients
		(`shortname`,`ipaddr` ,`nastype`,`secret`,`enabled`,`nap_capable`)
		VALUES('$shortname','$ipaddr','$nastype','',$enabled,$nap)";

        admin_tracks("Add new NAS/Radius Client $shortname - $ipaddr");

        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }

    }

    return true;

}

function client_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$_GET["client-js"];
	if($_GET["client-js"]==null){$title="{new_radius_client}";}
    $client=urlencode($_GET["client-js"]);
    $function=$_GET["function"];
	$tpl->js_dialog1("modal:$title", "$page?client-popup=$client&function=$function");
    return true;
	
}
function delete_js():bool{
	$tpl=new template_admin();
	$ipaddr=$_GET["delete-js"];
	$tpl->js_confirm_delete($ipaddr, "delete", $ipaddr,"$('#{$_GET["md"]}').remove()");
    return true;
}

function delete_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Remove NAS/Radius Client {$_POST["delete"]}");
	$q=new lib_sqlite("/home/artica/SQLITE/radius.db");
	$q->QUERY_SQL("DELETE FROM freeradius_clients WHERE ipaddr='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
    return true;
}


function client_enable_js():bool{
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/radius.db");
	
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM freeradius_clients WHERE ipaddr='{$_GET["enable-js"]}'");


	if(intval($ligne["enabled"])==1){
		$q->QUERY_SQL("UPDATE freeradius_clients SET enabled=0 WHERE ipaddr='{$_GET["enable-js"]}'");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
        return true;
	}
    $q->QUERY_SQL("UPDATE freeradius_clients SET enabled=1 WHERE ipaddr='{$_GET["enable-js"]}'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}

    return true;
}

function client_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$network=$_GET["client-popup"];
    $function=$_GET["function"];
	$btname="{add}";
	$FreeRadiusNasTypes=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusNasTypes"));
    $FreeRadiusNasTypes["Standard"]="Standard";
    $FreeRadiusNasTypes["RADIUS Standard"]="RADIUS Standard";
	if($network==null){
		$title="{new_radius_client}";
		$form[]=$tpl->field_text("ipaddr", "{address}", "10.0.0.0/24",true);
		$ligne["nastype"]="other";
		$ligne["shortname"]="Client-ABC";
		$ligne["secret"]="secret";
		$jsafter="$function();dialogInstance1.close();";
	}else{
		$title=$network;
		$btname="{apply}";
		$q=new lib_sqlite("/home/artica/SQLITE/radius.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM freeradius_clients WHERE ipaddr='$network'");
		$form[]=$tpl->field_info("ipaddr", "{address}", $ligne["ipaddr"]);
		$jsafter="dialogInstance1.close();";
	}
	
	
	$form[]=$tpl->field_text("shortname","{name}",$ligne["shortname"],true);
	$form[]=$tpl->field_array_hash($FreeRadiusNasTypes, "nastype", "{type}", $ligne["nastype"]);
	$form[]=$tpl->field_password2("secret", "{password}", $ligne["secret"]);
	echo $tpl->form_outside($title."&nbsp;/&nbsp;{$ligne["nastype"]}", @implode("\n", $form),"{freeradius_addrexpl}",$btname,$jsafter,"AsSystemAdministrator");
	
	
}
function client_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/radius.db");
	
	$ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM freeradius_clients WHERE ipaddr='{$_GET["ipaddr"]}'");

	if($ligne["ipaddr"]==null){
		$sql="INSERT IGNORE INTO freeradius_clients
		(`shortname`,`ipaddr` ,`nastype`,`secret`,`enabled`)
		VALUES('{$_POST["shortname"]}','{$_POST["ipaddr"]}','{$_POST["nastype"]}','{$_POST["secret"]}',1)";

	}else{
	$sql="UPDATE freeradius_clients SET `shortname`='{$_POST["shortname"]}',`nastype`='{$_POST["nastype"]}',`secret`='{$_POST["secret"]}' WHERE `ipaddr`='{$_POST["ipaddr"]}'";
	}

	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
    return true;
}

function table():bool{
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $search=$_GET["search"];
    $function=$_GET["function"];
	$page=CurrentPageName();
	$tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("freeradius.php?reload=yes",
        "freeradius.restart.progress","freeradius.restart.log","progress-freeradius-clients-restart");
	
	$btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?client-js=&function=$function');\"><i class='fa fa-plus'></i> {new_radius_client} </label>";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?client-import=yes&function=$function');\"><i class='fas fa-file-import'></i> {import} </label>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_settings} </label>";
    $btns[]="</div>";
	$html[]="<table id='tableau-freeradius-clients-services' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{address}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=falsse class='text-capitalize' data-type='text'>{test}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT *  FROM `freeradius_clients` ORDER BY shortname";
    $q=new lib_sqlite("/home/artica/SQLITE/radius.db");

    if($search<>null){
        $search="*$search*";
        $search=str_replace("**", "*", $search);
        $search=str_replace("**", "*", $search);
        $search=str_replace("*", "%", $search);

        $sql="SELECT *  FROM `freeradius_clients` WHERE ((ipaddr LIKE '$search') OR (shortname LIKE '$search') OR (nastype LIKE '$search')) ORDER BY shortname";
    }

	$TRCLASS=null;
	$FreeRadiusNasTypes=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusNasTypes"));

	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$ec=urlencode("127.0.0.1");
	$html[]="<tr class='$TRCLASS' id='0none'>";
	$html[]="<td><strong><i class='fa fa-desktop'></i>&nbsp;{default}</strong></td>";
	$html[]="<td style='width:1%' nowrap>{other}</a></td>";
	$html[]="<td style='width:1%' class='center' nowrap>". $tpl->button_autnonome("{test_auth}", "Loadjs('fw.freeradius.testauth.php?nas-name=$ec')", null,null,0,"btn-primary btn-xs")."</a></td>";
	$html[]="<td style='width:1%' class='center' nowrap><span class='fas fa-check'></span></center></td>";
	$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_nothing()."</center></td>";
	$html[]="</tr>";
	
	
	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ipaddrenc=urlencode($ligne["ipaddr"]);
		$ipaddrencc=md5($ipaddrenc);
		$servicename=$ligne["shortname"];
		$type=$FreeRadiusNasTypes[$ligne["nastype"]];
		$html[]="<tr class='$TRCLASS' id='$ipaddrencc'>";
		$html[]="<td><i class='fa fa-desktop'></i>&nbsp;<strong>". $tpl->td_href("$servicename - {$ligne["ipaddr"]}",null,"Loadjs('$page?client-js=$ipaddrenc')")."</strong></td>";
		$html[]="<td style='width:1%' nowrap>$type</a></td>";
		$html[]="<td style='width:5%' class='center' nowrap>". $tpl->button_autnonome("{test_auth}", "Loadjs('fw.freeradius.testauth.php?nas-name=$ipaddrenc')", null,null,0,"btn-primary btn-xs")."</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ipaddrenc')",null,"AsSystemAdministrator")."</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?delete-js=$ipaddrenc&md=$ipaddrencc')","AsSystemAdministrator")."</center></td>";
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



    $TINY_ARRAY["TITLE"]="{APP_FREERADIUS} &nbsp;&raquo;&nbsp; {radius_clients}";
    $TINY_ARRAY["ICO"]="fa fa-desktop";
    $TINY_ARRAY["EXPL"]="{APP_FREERADIUS_CLIENTS_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=@implode($btns);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    $headsjs
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}