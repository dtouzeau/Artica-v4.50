<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_POST["delete"])){delete_rule_confirm();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["route"])){route_save();exit;}
if(isset($_POST["DELETE_ROUTE_FROM"])){routes_delete();exit;}
//page();
main_start();

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
	$tpl->js_dialog("{new_route}","$page?rule-popup=0&profile=$profile&function=$function");
}

function main_start():bool{
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    echo "<div id='routes-profile-$profile'></div>
        <script>LoadAjax('routes-profile-$profile','$page?main=yes&profile=$profile&function=$function')</script>";

    return true;

}
function delete_rule_js():bool{
    $tpl=new template_admin();
    $ID=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM routes WHERE ID=$ID");
    $rulename=$ligne["rulename"];
    return $tpl->js_confirm_delete($rulename,"delete",$ID,"$('#$md').remove()");
}
function delete_rule_confirm():bool{
    $tpl=new template_admin();
    $ID=$_POST["delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM routes WHERE ID=$ID");
    $title=$ligne["rulename"];
    $ipaddr=$ligne["ipaddr"]."/".$ligne["netmask"];
    $q->QUERY_SQL("DELETE FROM routes WHERE ID=$ID");
    return admin_tracks("Deleted route profile $title $ipaddr");
}

function rule_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $ID=intval($_GET["rule-popup"]);
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $title="{new_route}";
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM routes WHERE ID=$ID");
    $btn="{add}";
    if($ID>0){
        $title=$ligne["rulename"];
        $btn="{apply}";
    }

    $fs[]="LoadAjax('routes-profile-$profile','$page?main=yes&profile=$profile&function=$function')";
    $fs[]="BootstrapDialog1.close()";
    $fs[]="$function()";

    $form[]=$tpl->field_hidden("route",$ID);
    $form[]=$tpl->field_hidden("profile",$profile);
    $form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);
	$form[]=$tpl->field_ipaddr("ipaddr", "{from_ip_address}", $ligne["ipaddr"]);
	$form[]=$tpl->field_ipaddr("netmask", "{netmask}",$ligne["netmask"]);
	$html=$tpl->form_outside($title, $form,"{routes_explain}",$btn,implode(";",$fs),"AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function route_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["route"]);
    $Main["profileid"]=intval($_POST["profile"]);
    $Main["rulename"]=$_POST["rulename"];
    $Main["ipaddr"]=$_POST["ipaddr"];
    $Main["netmask"]=$_POST["netmask"];

    foreach ($Main as $key=>$val){
        $ff[]=$key;
        $fd[]="'$val'";
        $fe[]="$key='$val'";
    }
    if($ID==0){
        $sql=sprintf("INSERT INTO routes (%s) VALUES (%s)",implode(",",$ff),implode(",",$fd));
    }else{
        $sql=sprintf("UPDATE routes SET %s WHERE ID=%s",implode(",",$fe),$ID);
    }
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("OpenVPN Route ID=$ID");
}
function routes_delete(){
	$vpn=new openvpn();
	unset($vpn->routes[$_POST["DELETE_ROUTE_FROM"]]);
	$vpn->Save();

}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?ruleid-js=0&profile=$profile&function=$function');", ico_plus, "{new_route}");
	
	$html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
	$html[]="<table id='table-openvpn-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
    $html[]="<th></th>";
    $html[]="<th>{description}</th>";
	$html[]="<th>{from_ip_address}</th>";
	$html[]="<th>{netmask}</center></th>";
	$html[]="<th>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS routes (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        profileid INTEGER NOT NULL DEFAULT 0,
        rulename TEXT NOT NULL DEFAULT 'New route',
		ipaddr TEXT NOT NULL DEFAULT '0.0.0.0',
        netmask TEXT NOT NULL DEFAULT '0.0.0.0'
        )");

    $results=$q->QUERY_SQL("SELECT * FROM routes WHERE profileid=$profile ORDER BY rulename LIMIT 250");



    foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $ipaddr=$ligne["ipaddr"];
        $netmask=$ligne["netmask"];
        $rulename=$ligne["rulename"];
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width='1%' nowrap><i class='fa fa-truck'></i></td>";
		$html[]="<td>$rulename</td>";
        $html[]="<td>$ipaddr</td>";
        $html[]="<td>$netmask</td>";
		$html[]="<td width='1%' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsVPNManager")."</td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="</div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}