<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");

$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["Authentication"])){authentication_save();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_GET["session-tab"])){session_tab();exit;}
if(isset($_GET["auth-popup"])){authentication_popup();exit;}
if(isset($_GET["authentication-popup"])){authentication_popup();exit;}

session_js();
function session_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	$tpl=new template_admin();
	$uid=$_GET["uid"];
	$uid_encoded=urlencode($uid);
    $function=$_GET["function"];
	$tpl->js_dialog("{settings}: $uid","$page?session-tab=$uid_encoded&function=$function");
}

function session_tab():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-popup"]);
	$uid=$_GET["session-tab"];
	$uid_encoded=urlencode($uid);
    $function=$_GET["function"];
	$array["{ipaddr}"]="$page?ipaddr-popup=$uid_encoded&function=$function";
    $array["{authentication}"]="$page?authentication-popup=$uid_encoded&function=$function";



	if($ID>0){

		$array["{firewall_services}"]="fw.rules.services.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}";
		if($ligne["isClient"]==0){$array["{inbound_object}"]="fw.rules.objects.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}";}
		$array["{outbound_object}"]="fw.rules.objects.php?rule-id=$ID&direction=1&eth={$_GET["eth"]}";
		$array["{time_restriction}"]="fw.rules.time.php?rule-id=$ID&eth={$_GET["eth"]}";

	}
	echo $tpl->tabs_default($array);
	return false;
}

function authentication_popup():bool{
    $tpl=new template_admin();
    $uid=$_GET["authentication-popup"];
    $uidEnc=urlencode($uid);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $function="Loadjs('fw.openvpn.clients.php?uid-row=$uidEnc')";
    $ligne=$q->mysqli_fetch_array("SELECT LocalAuth,username,password FROM openvpn_clients WHERE uid='$uid'");

    $form[]=$tpl->field_hidden("Authentication",$uid);
    $form[]=$tpl->field_checkbox("LocalAuth","{UseLocalDatabase}",$ligne["LocalAuth"],"username,password");
    $form[]=$tpl->field_text("username","{username}",$ligne["username"]);
    $form[]=$tpl->field_password("password","{password}",$ligne["password"]);
    $html=$tpl->form_outside(null,$form,null,"","$function","AsVPNManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function authentication_save():bool {
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if(isset($_POST["LocalAuth"])) {
        $sql = sprintf("UPDATE openvpn_clients SET LocalAuth=%s,username='%s',password='%s' WHERE uid='%s'",
            intval($_POST["LocalAuth"]), $_POST["username"], $_POST["password"], $_POST["Authentication"]);
    }


    if(isset($_POST["fixip"])){

        if($_POST["fixip"]<>"0.0.0.0") {
            $ArticaOpenVPNSettings=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaOpenVPNSettings");
            $ini = new Bs_IniHandler();
            $ini->loadString($ArticaOpenVPNSettings);
            $IP_START = $ini->_params["GLOBAL"]["IP_START"];
            //$NETMASK = $ini->_params["GLOBAL"]["NETMASK"];
            $TBR = explode(".", $IP_START);
            $TBR2 = explode(".",$_POST["fixip"]);
            $TBR2[0]=$TBR[0];
            $TBR2[1]=$TBR[1];
            $_POST["fixip"]=@implode(".",$TBR2);
        }


        $_POST["profile"]=intval($_POST["xprofile"]);
        $sql = sprintf("UPDATE openvpn_clients SET fixip='%s',profile='%s' WHERE uid='%s'",
            $_POST["fixip"], $_POST["profile"],  $_POST["Authentication"]);
    }



    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    return admin_tracks(sprintf("Update Authentication method for %s LocalAuth=%s",$_POST["Authentication"],$_POST["LocalAuth"]));
}


function ipaddr_popup(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	$p=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$tpl=new template_admin();
	$uid=$_GET["ipaddr-popup"];
    $uidEnc=urlencode($uid);
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));


    if($SQUIDEnable==1) {
        $count = $p->mysqli_fetch_array("SELECT COUNT(*) as count FROM transparent_ports WHERE enabled=1 AND TProxy=0 AND hiddenID=0");
    }

	$readonly = true;
	if ($count['count']>0){
		$readonly=false;
	}


	
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$NICS[null]="{default}";
	
	foreach ($nicZ as $yinter=>$line){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		if($znic->enabled==0){continue;}
		$NICS[$yinter]="$yinter - $znic->NICNAME";
	}
    $results=$q->QUERY_SQL("SELECT ID,rulename FROM profiles ORDER BY rulename");
    foreach ($results as $index=>$ligne){
        $profiles[$ligne["ID"]]=$ligne["rulename"];
    }

    $ligne=$q->mysqli_fetch_array("SELECT * FROM openvpn_clients WHERE uid='$uid'");
	$form[]=$tpl->field_hidden("Authentication", $uid);
    if(count($profiles)>0){
        $form[]=$tpl->field_array_hash($profiles,"xprofile","{profile}",$ligne["profile"]);
    }else{
        $form[]=$tpl->field_info("profile","{profile}",0);
    }

    $ipclass=new IP();
    if (!$ipclass->isValid($ligne["fixip"])){
        $ligne["fixip"]="0.0.0.0";
    }


	$form[]=$tpl->field_ipaddr("fixip", "{fixed_ipaddr}", $ligne["fixip"]);

    if($SQUIDEnable==1) {
        $form[] = $tpl->field_checkbox("PassTroughProxy", "{pass_trough_the_proxy}",
            intval($ligne["use_proxy"]), false, "{pass_trough_the_proxy_explain}", $readonly);
    }else{
        $form[]=$tpl->field_hidden("PassTroughProxy", 0);
    }

    $function="Loadjs('fw.openvpn.clients.php?uid-row=$uidEnc')";
	$html[]=$tpl->form_outside("", $form,"","{apply}",$function,"AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
}




