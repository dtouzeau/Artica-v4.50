<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_POST["delete"])){delete_rule_confirm();exit;}

if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-tab"])){rule_tab();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_POST["DELETE_ROUTE_FROM"])){routes_delete();exit;}
page();

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $id=intval($_GET["ruleid-js"]);
    $function=$_GET["function"];
    $title="{new_profile}";
    if($id>0){
        $title="{profile} #$id";
    }
    $uid="";
    if(isset($_GET["uid"])){
        $uid="&uid={$_GET["uid"]}";
    }

	return $tpl->js_dialog2($title,"$page?rule-tab=$id&function=$function$uid");
}

function rule_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-tab"]);
    $function=$_GET["function"];
    $uid="";
    if(isset($_GET["uid"])){
        $uid="&uid={$_GET["uid"]}";
    }

    if($ID==0) {
        $array["{new_rule}"] = "$page?rule-popup=0&function=$function";
        echo $tpl->tabs_default($array);
        return true;
    }
    $icofw=ico_firewall;
    $icoauth=ico_group;
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM profiles WHERE ID=$ID");
    $array[sprintf("<i class='%s'></i> %s",ico_params,$ligne["rulename"])] = "$page?rule-popup=$ID&function=$function$uid";

    $array["<i class='$icoauth'></i> {authenticate}"] = "fw.openvpn.ad.php?profile=$ID&function=$function$uid";

    $array["<i class='fa fa-truck'></i> {additional_routes}"] = "fw.openvpn.routes.php?profile=$ID&function=$function$uid";
    $array["<i class='$icofw'></i> {firewall}"] = "fw.openvpn.firewall.php?profile=$ID&function=$function$uid";


    echo $tpl->tabs_default($array);
    return true;
}


function delete_rule_js():bool{
    $tpl=new template_admin();
    $ID=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM profiles WHERE ID=$ID");
    $rulename=$ligne["rulename"];
    return $tpl->js_confirm_delete($rulename,"delete",$ID,"$('#$md').remove()");
}
function delete_rule_confirm():bool{
    $ID=intval($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM profiles WHERE ID=$ID");
    $rulename=$ligne["rulename"];
    $q->QUERY_SQL("DELETE FROM profiles WHERE ID=$ID");
    $q->QUERY_SQL("DELETE FROM routes WHERE profileid=$ID");
    $q->QUERY_SQL("DELETE FROM firewall WHERE profileid=$ID");
    $q->QUERY_SQL("DELETE FROM auths WHERE profileid=$ID");
    $q->QUERY_SQL("UPDATE openvpn_clients SET profile=0 WHERE profile=$ID");
    return admin_tracks("Deleted OpenVPN profile $rulename");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $html=$tpl->page_header("{APP_OPENVPN} v$OpenVPNVersion {profiles}",
        "fas fa-scroll","{APP_OPENVPN_PROFILES}","$page?main-start=yes",
        "openvpn-profiles","progress-openvpn-restart",false,"table-connections");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function rule_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $uid="";
    if(isset($_GET["uid"])){
        $uid=";Loadjs('fw.openvpn.clients.php?uid-row={$_GET["uid"]}')";
    }

    //OpenVPNClientTOClient: client-to-client
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $id=intval($_GET["rule-popup"]);
    $ligne["clienttoclient"]=0;
    $function=$_GET["function"];
    $btn="{add}";
    $jsclose="dialogInstance2.close();";
    if($id>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM profiles WHERE ID=$id");
        $btn="{apply}";
    }
    if(strlen($function)>3){
        $function="$function();";
    }


    $form[]=$tpl->field_hidden("ID",$id);
    $form[]=$tpl->field_text("rulename", "{name}", $ligne["rulename"],true);
	$form[]=$tpl->field_checkbox("clienttoclient", "{OpenVPNClientTOClient}", $ligne["clienttoclient"]);
	$html=$tpl->form_outside("",  $form,"",$btn,"$function$jsclose$uid","AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $main["rulename"]=$_POST["rulename"];
    $main["clienttoclient"]=$_POST["clienttoclient"];
    $ID=$_POST["ID"];
    foreach ($main as $key=>$val){
        $f_add[]=$key;
        $d_add[]="'$val'";
        $e_add[]="$key='$val'";

    }
    if($ID==0){
        $sql=sprintf("INSERT INTO profiles (%s) VALUES (%s)",implode(",",$f_add),implode(",",$d_add));
    }else{
        $sql=sprintf("UPDATE profiles SET %s WHERE ID=%s",@implode(",",$e_add),$ID);
    }
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }


    return admin_tracks("Updating or adding VPN profile #$ID {$main["rulename"]}");

}
function routes_delete(){
	$vpn=new openvpn();
	unset($vpn->routes[$_POST["DELETE_ROUTE_FROM"]]);
	$vpn->Save();

}
function main_start():bool{



    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&main=yes");
    return true;
}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
    $uid="";
    if(isset($_GET["uid"])){
        $uid="&uid={$_GET["uid"]}";
    }
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS profiles (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        rulename TEXT NOT NULL DEFAULT 'New rule',
		clienttoclient INTEGER NOT NULL DEFAULT 0,
        clienttoserver INTEGER NOT NULL DEFAULT 0
        )");
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{profiles}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM profiles ORDER BY rulename LIMIT 250");

	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    	$md=md5(serialize($ligne));
        $rulename=$ligne["rulename"];
        $ID=intval($ligne["ID"]);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsVPNManager");


        $rulename=$tpl->td_href($rulename,"","Loadjs('$page?ruleid-js=$ID&function=$function$uid')");
        $explain=td_explain($ligne);

		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap><i class='fas fa-scroll'></i></td>";
        $html[]="<td width=1% nowrap>$ID</td>";
		$html[]="<td width=1% nowrap>$rulename</td>";
		$html[]="<td width=99%><div id='openvpn-profile-$ID'>$explain</div></td>";
		$html[]="<td width=1% nowrap>$delete</td>";
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


    $topbuttons[] = array("Loadjs('$page?ruleid-js=&function=$function$uid');",ico_plus,"{new_profile}");

    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $TINY_ARRAY["TITLE"]="{APP_OPENVPN} v$OpenVPNVersion {profiles}";
    $TINY_ARRAY["ICO"]="fas fa-scroll";
    $TINY_ARRAY["EXPL"]="{APP_OPENVPN_PROFILES}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
    
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false 	}, 	\"sorting\": { 	\"enabled\": true 	} } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function td_explain($ligne):string{
    $f=array();
    $clienttoclient=$ligne["clienttoclient"];
    if($clienttoclient==1){
        $f[]="{OpenVPNClientTOClient}";
    }
    $ID=intval($ligne["ID"]);

    $r=array();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $results=$q->QUERY_SQL("SELECT * FROM routes WHERE profileid=$ID ORDER BY rulename LIMIT 250");
    foreach ($results as $index=>$ligne) {
        $ipaddr = $ligne["ipaddr"];
        $netmask = $ligne["netmask"];
        $r[]="$ipaddr/$netmask";
    }

    if(count($r)>0){
        $f[]="{route}: ".implode(", ",$r);
    }


    $t=array();
    $results=$q->QUERY_SQL("SELECT rulename FROM firewall WHERE profileid=$ID ORDER BY ID LIMIT 250");
    foreach ($results as $index=>$ligne) {
        $rulename = $ligne["rulename"];
        $t[]="$rulename";
    }

    if(count($t)>0){
        $f[]="{firewall}: ".implode(", ",$t);
    }
    $a=array();
    $results=$q->QUERY_SQL("SELECT rulename FROM auths WHERE profileid=$ID ORDER BY ID LIMIT 250");
    foreach ($results as $index=>$ligne) {
        $rulename = $ligne["rulename"];
        $a[]="$rulename";
    }
    if(count($a)>0){
        $f[]="{authentication}: ".implode(", ",$a);
    }



    return @implode("<br> ", $f);
}