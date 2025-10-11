<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_POST["delete"])){delete_rule_confirm();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["ruleparams"])){auths_save();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}
//page();
main_start();

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $ID=intval($_GET["ruleid-js"]);
	$tpl->js_dialog("{new_rule}","$page?rule-popup=$ID&profile=$profile&function=$function");
}

function main_start():bool{
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    echo "<div id='adauth-profile-$profile'></div>
        <script>LoadAjax('adauth-profile-$profile','$page?main=yes&profile=$profile&function=$function')</script>";

    return true;

}
function delete_rule_js():bool{
    $tpl=new template_admin();
    $ID=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM auths WHERE ID=$ID");
    $rulename=$ligne["rulename"];
    return $tpl->js_confirm_delete($rulename,"delete",$ID,"$('#$md').remove()");
}
function delete_rule_confirm():bool{
    $tpl=new template_admin();
    $ID=$_POST["delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM auths WHERE ID=$ID");
    $title=$ligne["rulename"];
    $ipaddr=$ligne["ipaddr"]."/".$ligne["netmask"];
    $q->QUERY_SQL("DELETE FROM auths WHERE ID=$ID");
    return admin_tracks("Deleted route profile $title $ipaddr");
}

function rule_new():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $title="{new_rule}";
    $btn="{create}";
    $fs[]="LoadAjax('adauth-profile-$profile','$page?main=yes&profile=$profile&function=$function')";
    $fs[]="BootstrapDialog1.close()";
    $fs[]="$function()";

    $form[]=$tpl->field_hidden("newrule",0);
    $form[]=$tpl->field_hidden("profile",$profile);
    $form[]=$tpl->field_text("rulename","{rulename}","New authentication rule");
    $form[]=$tpl->field_array_hash(HashAuths(),"ztype","nonull:{type}","");
    $html=$tpl->form_outside($title, $form,"",$btn,implode(";",$fs),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function new_rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Main["profileid"]=intval($_POST["profile"]);
    $Main["rulename"]=$_POST["rulename"];
    $Main["authtype"]=$_POST["ztype"];


    foreach ($Main as $key=>$val){
        $ff[]=$key;
        $fd[]="'$val'";
    }

    $sql=sprintf("INSERT INTO auths (%s) VALUES (%s)",implode(",",$ff),implode(",",$fd));
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("Create new OpenVPN authenticate rule");

}
function rule_activedirectory():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["rule-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM auths WHERE ID=$ID");
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $DISABLE=false;
    if($EnableActiveDirectoryFeature==0){
        $DISABLE=true;
    }

    $List=HasAdConns();
    $fs[]="LoadAjax('adauth-profile-$profile','$page?main=yes&profile=$profile&function=$function')";
    $fs[]="BootstrapDialog1.close()";
    $fs[]="$function()";

    $form[]=$tpl->field_hidden("ruleparams",$ID);
    $form[]=$tpl->field_array_hash($List,"connectionid","{connection}",$ligne["connectionid"]);
    $form[]=$tpl->field_activedirectorygrp("groupfilter","{group}",$ligne["groupfilter"],false,null,false,true);
    $html=$tpl->form_outside("{activedirectory_connection}", $form,"","{apply}",implode(";",$fs),"AsVPNManager",true,$DISABLE);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function HasAdConns():array{
    $List=array();
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==1){
        $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        if(!is_array($array)){$array=array();}
        if(!isset($array["ADUserCanConnect"])){$array["ADUserCanConnect"]=0;}
        if($array["LDAP_SERVER"]==null){
            if($array["fullhosname"]<>null){
                $array["LDAP_SERVER"]=$array["fullhosname"];
            }
        }
        $List[0]=$array["LDAP_SERVER"]." ({default}) ";
    }
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    foreach ($ActiveDirectoryConnections as $index=>$ligne){
        if ($index==0){continue;}
        if(!isset($ligne["LDAP_PORT"])){$ligne["LDAP_PORT"]=389;}
        if(!isset($ligne["LDAP_SSL"])){$ligne["LDAP_SSL"]=0;}
        if(!isset($ligne["WINDOWS_SERVER_ADMIN"])){
            if(isset($ligne["LDAP_DN"])){
                $ligne["WINDOWS_SERVER_ADMIN"]=$ligne["LDAP_DN"];
            }
        }

        if(!isset($ligne["LDAP_SERVER"])) {
            if ($ligne["ADNETIPADDR"] <> null) {
                $ligne["LDAP_SERVER"] = $ligne["ADNETIPADDR"];
            }
        }
        $List[$index]=$array["LDAP_SERVER"];
    }

    return $List;
}

function auths_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ruleparams"]);
    $Main["connectionid"]=intval($_POST["connectionid"]);
    $Main["groupfilter"]=$_POST["groupfilter"];

    foreach ($Main as $key=>$val){
        $fe[]="$key='$val'";
    }

    $sql=sprintf("UPDATE auths SET %s WHERE ID=%s",implode(",",$fe),$ID);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("OpenVPN Authenticate parameters ID=$ID");
}

function rule_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $ID=intval($_GET["rule-popup"]);
    if($ID==0){
        return rule_new();
    }


    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM auths WHERE ID=$ID");
    $authtype=$ligne["authtype"];
    if($authtype=="ad"){
        return rule_activedirectory();
    }

    $title=$ligne["rulename"];
    $btn="{apply}";


    $fs[]="LoadAjax('adauth-profile-$profile','$page?main=yes&profile=$profile&function=$function')";
    $fs[]="BootstrapDialog1.close()";
    $fs[]="$function()";

    $form[]=$tpl->field_hidden("newrule",0);
    $form[]=$tpl->field_hidden("profile",$profile);
    $form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);
    $form[]=$tpl->field_array_hash(HashAuths(),"ztype",null);
	$html=$tpl->form_outside($title, $form,"",$btn,implode(";",$fs),"AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function HashAuths():array{
    $hash["ad"]="{ActiveDirectory}";
    return $hash;

}



function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $profile=intval($_GET["profile"]);
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?ruleid-js=0&profile=$profile&function=$function');", ico_plus, "{new_rule}");
	
	$html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
	$html[]="<table id='table-openvpn-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
    $html[]="<th></th>";
    $html[]="<th>{description}</th>";
	$html[]="<th>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS auths (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        profileid INTEGER NOT NULL DEFAULT 0,
        rulename TEXT NOT NULL DEFAULT 'New Auth',
		authtype TEXT NOT NULL DEFAULT 'ad',
        connectionid INTEGER NOT NULL DEFAULT 0,
        groupfilter TEXT NOT NULL DEFAULT ''
        )");

    $results=$q->QUERY_SQL("SELECT * FROM auths WHERE profileid=$profile ORDER BY rulename LIMIT 250");
    $HashAuths=HashAuths();
    $HasAdConns=HasAdConns();

    foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $authtype=$ligne["authtype"];
        $rulename=$ligne["rulename"];
        $groupfilter=$ligne["groupfilter"];
        $connectionid=$ligne["connectionid"];
        $Conn=null;
        if($authtype=="ad"){
            $Conn="<br><i>{$HasAdConns[$connectionid]}</i>";
        }
        if(strlen($groupfilter)>1){
            $Conn="$Conn<br><i>$groupfilter</i>";
        }
        $rulename=$tpl->td_href($rulename,"","Loadjs('$page?ruleid-js=$ID&profile=$profile&function=$function');");

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width='1%' nowrap><i class='fa fa-truck'></i></td>";
		$html[]="<td>$rulename ($HashAuths[$authtype])$Conn</td>";

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