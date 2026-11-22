<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["server-add"])){server_add();exit;}
if(isset($_GET["popup"])){popup_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["haip"])){server_save();exit;}
if(isset($_GET["server-check"])){server_check();exit;}
if(isset($_GET["server-delete"])){server_delete();exit;}
if(isset($_POST["server-delete"])){server_delete_real();exit;}
if(isset($_GET["xauthuser-toggle"])){xauthuser_toggle();exit;}

js();

function js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_dialog5("{follow_x_forwarded_for}","$page?popup=yes");
}
function popup_start(){
    $page   = CurrentPageName();
    $html   = "<div id='follow_x_forwarded_for-table' style='margin-top: 10px'></div><script>LoadAjax('follow_x_forwarded_for-table','$page?table=yes');</script>";
    echo $html;
}
// Relais de confiance (table squid_balancers) : lecture et ecriture par articarest
// (/proxy/balancers), jamais en SQL direct. Appliquer = recharger Squid : un
// changement de follow_x_forwarded_for ne demande pas de redemarrage (l'ancien
// /proxy/general/nohup/restart coupait toutes les connexions a chaque clic).
// /proxy/balancers/apply = reconstruction puis UN rechargement a la fin.
function balancers_list(&$error):array{
    $error="";
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/balancers"),true);
    if(!is_array($json) || empty($json["Status"])){
        $error=is_array($json) && isset($json["Error"]) ? (string)$json["Error"] : "REST API";
        return array();
    }
    return isset($json["Data"]) && is_array($json["Data"]) ? $json["Data"] : array();
}
function balancers_result($resp):string{
    $json=json_decode((string)$resp,true);
    if(!is_array($json)){return "REST API";}
    if(empty($json["Status"])){return isset($json["Error"]) ? (string)$json["Error"] : "REST API";}
    return "";
}
function balancers_apply(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/balancers/apply");
}
function server_delete(){
    $id=intval($_GET["server-delete"]);
    $md=preg_replace("#[^a-z0-9]#","",(string)$_GET["md"]);
    $tpl    = new template_admin();
    $ipsrc  = "#$id";
    $err    = "";
    foreach (balancers_list($err) as $row){
        if(intval($row["id"])==$id){$ipsrc=(string)$row["ipsrc"];}
    }
    $tpl->js_confirm_delete($ipsrc,"server-delete",$id,"$('#$md').remove()");

}
function server_delete_real(){
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo "{ERROR_NO_PRIVS2}";return;}
    $id=intval($_POST["server-delete"]);
    $err=balancers_result($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/proxy/balancers/$id"));
    if($err<>""){echo $err;return;}
    balancers_apply();
}


function server_check(){
    $id     = intval($_GET["server-check"]);
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSquidAdministrator){$tpl->js_no_privileges();return;}
    $err=balancers_result($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/proxy/balancers/$id/toggle",array()));
    if($err<>""){$tpl->js_mysql_alert($err);return;}
    balancers_apply();
}

function server_add(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_prompt("{new_server}","{server_ip_address}","fas fa-server",$page,"haip","LoadAjax('follow_x_forwarded_for-table','$page?table=yes');",null,"");

}

function server_save(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $users      = new usersMenus();
    if(!$users->AsSquidAdministrator){echo "{ERROR_NO_PRIVS2}";return;}
    $IPClass    = new IP();
    $haip       = trim((string)$_POST["haip"]);

    if(!$IPClass->isIPAddressOrRange($haip)){
        echo htmlspecialchars($haip)." Wrong format";
        return;
    }
    $err=balancers_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proxy/balancers",array("ipsrc"=>$haip)));
    if($err<>""){echo $err;return;}
    balancers_apply();

}


// Etat de l'option X-Authenticated-User (articarest GET /proxy/balancers/xauthuser).
// Une lecture ratee rend ok=false : la page le dit, elle n'affiche pas "desactive".
function xauthuser_state():array{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/balancers/xauthuser"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["Data"]) || !is_array($json["Data"])){
        $err=is_array($json) && isset($json["Error"]) ? (string)$json["Error"] : "REST API";
        return array("ok"=>false,"enabled"=>false,"supported"=>false,"error"=>$err);
    }
    return array("ok"=>true,
        "enabled"=>!empty($json["Data"]["enabled"]),
        "supported"=>!empty($json["Data"]["supported"]),
        "error"=>"");
}

function xauthuser_toggle():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $users  = new usersMenus();
    if(!$users->AsSquidAdministrator){return $tpl->js_no_privileges();}
    $st=xauthuser_state();
    if(!$st["ok"]){return $tpl->js_error($st["error"]);}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/proxy/balancers/xauthuser",array("enabled"=>!$st["enabled"])),true);
    if(!is_array($json) || empty($json["Status"])){
        $err=is_array($json) && isset($json["Error"]) ? (string)$json["Error"] : "REST API";
        return $tpl->js_error($err);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('follow_x_forwarded_for-table','$page?table=yes');";
    return true;
}

function table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();

    // X-Authenticated-User : le nom d'utilisateur transmis par ces relais
    // (VirtualBrowser...) remplace l'absence d'authentification sur le proxy.
    $xbtn="";
    $xexplain="";
    $xst=xauthuser_state();
    if(!$xst["ok"]){
        $xexplain=$tpl->div_error("{xauthuser_header}: ".htmlspecialchars($xst["error"]));
    }elseif($xst["supported"]){
        $xico   = $xst["enabled"] ? "fas fa-toggle-on" : "fas fa-toggle-off";
        $xstate = $xst["enabled"] ? "{enabled}" : "{disabled}";
        $xbtn   = $tpl->button_label_table("{xauthuser_header}: $xstate", "Loadjs('$page?xauthuser-toggle=yes')", $xico,"AsSquidAdministrator");
        $xexplain=$tpl->div_explain("{xauthuser_header}||{xauthuser_header_explain}");
    }

    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{new_server}", "Loadjs('$page?server-add=yes')", "fas fa-plus-circle","AsSquidAdministrator").$xbtn."</div>");
    if($xexplain<>""){$html[]=$tpl->_ENGINE_parse_body($xexplain);}

    $html[]=$tpl->_ENGINE_parse_body("
	<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{servers}</th>";
    $html[]="<th class='awc-nowrap'>{enabled}</th>";
    $html[]="<th>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='generalfollo-rows'>";


    $berr="";
    $results=balancers_list($berr);
    if($berr<>""){echo $tpl->FATAL_ERROR_SHOW_128($berr);return;}


//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $TRCLASS=null;
        $ID         = intval($ligne["id"]);
        $md         = md5(serialize($ligne));
        $ipsrc      = htmlspecialchars((string)$ligne["ipsrc"]);
        $enabled    = intval($ligne["enabled"]);
        $delete     = $tpl->icon_delete("Loadjs('$page?server-delete=$ID&md=$md')","AsSquidAdministrator");
        $check      = $tpl->icon_check($enabled,"Loadjs('$page?server-check=$ID')",null,"AsSquidAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='90%' nowrap><i class='fas fa-server'></i>&nbsp;<strong>$ipsrc</strong></td>";
        $html[]="<td width='1%' nowrap>$check</td>";
        $html[]="<td class=\"center\" style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='generalfollo-pager'></span></div></div></div>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"generalfollo-rows","generalfollo-pager");




}

