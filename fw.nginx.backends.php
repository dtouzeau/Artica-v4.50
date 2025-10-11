<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$GLOBALS["HASHLB"][0]="Round Robin";
$GLOBALS["HASHLB"][1]="{least-connections}";
$GLOBALS["HASHLB"][2]="{strict-hashed-ip}";
$GLOBALS["HASHLB"][3]="Cookie";
$GLOBALS["HASHLB"][4]="{lax-hashed-ip}";
if(isset($_GET["td"])){td_row();exit;}
if(isset($_GET["backend-move"])){move();exit;}
if(isset($_GET["backend-enable"])){id_enable();exit;}
if(isset($_POST["lb_method"])){load_balancing_save();exit;}
if(isset($_POST["BackendSave"])){id_save();exit;}
if(isset($_POST["serviceid"])){options_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["BackEndsKeepAlive"])){backends_keepalive_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["ssl-switch"])){ssl_switch();exit;}
if(isset($_GET["insecure-switch"])){unsecure_switch();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["backends-keepalive-js"])){backends_keepalive_js();exit;}
if(isset($_GET["backends-keepalive-popup"])){backends_keepalive_popup();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_GET["load-balancing-js"])){load_balancing_js();exit;}
if(isset($_GET["load-balancing-popup"])){load_balancing_popup();exit;}
if(isset($_GET["td-stats"])){td_stats();exit;}

table_start();


function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='backends-reverse-$ID'></div>
	<script>LoadAjax('backends-reverse-$ID','$page?table=$ID');</script>";
    return true;
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["delete"];
	$md5=$_GET["md5"];
	$q=new lib_sqlite(NginxGetDB());
	$ligne=$q->mysqli_fetch_array("SELECT `hostname`,`port` FROM backends WHERE ID=$ID");
	$tpl->js_confirm_delete("{$ligne["hostname"]}:{$ligne["port"]}", "delete", "$ID","$('#$md5').remove();NgixSitesReload()");
}
function move(){
    $tpl=new template_admin();
    $ID=intval($_GET["backend-move"]);
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT hostname,zOrder,serviceid FROM backends WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    $serviceid=intval($ligne["serviceid"]);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["zOrder"]};\n";}
    $xORDER_ORG=intval($ligne["zOrder"]);
    $xORDER=$xORDER_ORG;


    if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
    if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
    if($xORDER<0){$xORDER=0;}
    $sql="UPDATE backends SET zOrder=$xORDER WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    if($GLOBALS["VERBOSE"]){echo "$sql\n";}

    if($_GET["acl-rule-dir"]==1){
        $xORDER2=$xORDER+1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE backends SET zOrder=$xORDER2 WHERE ID<>$ID AND zOrder=$xORDER AND serviceid=$serviceid";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }
    if($_GET["acl-rule-dir"]==0){
        $xORDER2=$xORDER-1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE backends SET zOrder=$xORDER2 WHERE ID<>$ID AND zOrder=$xORDER AND serviceid=$serviceid";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
    }

    $c=0;
    $sql="SELECT ID FROM backends ORDER BY zOrder AND serviceid=$serviceid";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE backends SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "UPDATE backends SET zOrder=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }


}
function  delete():bool{
	$ID=intval($_POST["delete"]);
	$q=new lib_sqlite(NginxGetDB());
	$q->QUERY_SQL("DELETE FROM backends WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Removed #$ID backend from reverse-proxy rule");
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}

function id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["id-js"];
    $md5="";
	$serviceid=0;
    $hostname="";

    if(isset($_GET["serviceid"])){
        $serviceid=$_GET["serviceid"];
        $hostname=get_servicename($serviceid);
    }

    if($ID>0){
        $q=new lib_sqlite(NginxGetDB());
        $ligne=$q->mysqli_fetch_array("SELECT serviceid FROM backends WHERE ID=$ID");
        $serviceid=$ligne["serviceid"];
        $hostname=get_servicename($serviceid);
    }

    if(isset($_GET["md5"])) {
        $md5 = $_GET["md5"];
    }
	$title="$hostname: {backend}: $ID";
	if($ID==0){$title="$hostname: {new_entry}";}
	return $tpl->js_dialog2($title, "$page?id-popup=$ID&serviceid=$serviceid&md5=$md5");
}
function  backends_keepalive_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["backends-keepalive-js"]);
    $title="{keep_alive}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?backends-keepalive-popup=$serviceid");
}
function options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["options-js"]);
    $title="{options}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?options-popup=$serviceid");
}
function load_balancing_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["load-balancing-js"]);
    $title="{lb_settings}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?load-balancing-popup=$serviceid");
}
function load_balancing_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["load-balancing-popup"]);
    $sock=new socksngix($serviceid);
    $lb_method=intval($sock->GET_INFO("lb_method"));
    $LBCookiesTime=intval($sock->GET_INFO("LBCookiesTime"));
    $LBZone=intval($sock->GET_INFO("LBZone"));
    if($LBCookiesTime<30){
        $LBCookiesTime=3600;
    }
    if($LBZone==0){
        $LBZone=64;
    }

    $zone["32"]="32k";
    $zone["64"]="64K";
    $zone["128"]="128K";
    $zone["256"]="256K";
    $zone["512"]="512K";
    $zone["1024"]="1024K";

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_array_hash( $GLOBALS["HASHLB"],"lb_method","nonull:{method}",$lb_method);
    $form[]=$tpl->field_array_hash( $zone,"LBZone","nonull:{zone}",$LBZone);
    $form[]=$tpl->field_numeric("LBCookiesTime","Cookie ({seconds})",$LBCookiesTime);
    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsWebAdministrator");
    return true;
}
function load_balancing_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("lb_method",$_POST["lb_method"]);
    $sock->SET_INFO("LBZone",$_POST["LBZone"]);
    $sock->SET_INFO("LBCookiesTime",$_POST["LBCookiesTime"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save Balancing method for service #$serviceid ({$_POST["lb_method"]})");

}
function backends_keepalive_popup():bool{
    $serviceid=intval($_GET["backends-keepalive-popup"]);
    $sock=new socksngix($serviceid);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $KeepAlive              = intval($sock->GET_INFO("BackEndsKeepAlive"));
    $BackEndsKeepAliveRQS              = intval($sock->GET_INFO("BackEndsKeepAliveRQS"));
    $BackEndsKeepAliveHour              = intval($sock->GET_INFO("BackEndsKeepAliveHour"));
    $BackEndsKeepAliveTimeOut              = intval($sock->GET_INFO("BackEndsKeepAliveTimeOut"));

    if($BackEndsKeepAliveTimeOut==0){
        $BackEndsKeepAliveTimeOut=60;
    }
    if($BackEndsKeepAliveHour==0){
        $BackEndsKeepAliveHour=1;
    }
    if($BackEndsKeepAliveRQS==0){
        $BackEndsKeepAliveRQS=100;
    }


    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_numeric("BackEndsKeepAlive","{connections}",$KeepAlive);
    $form[]=$tpl->field_numeric("BackEndsKeepAliveRQS","Max {requests}",$BackEndsKeepAliveRQS);
    $form[]=$tpl->field_numeric("BackEndsKeepAliveHour","TTL ({hour})",$BackEndsKeepAliveHour);
    $form[]=$tpl->field_numeric("BackEndsKeepAliveTimeOut","{timeout} ({seconds})",$BackEndsKeepAliveTimeOut);



    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->form_outside(null,$form,"{BackEndsKeepAlive}","{apply}",$jsafter,"AsWebAdministrator");
    return true;
}
function backends_keepalive_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("BackEndsKeepAlive",$_POST["BackEndsKeepAlive"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save Backends Keep alive for service #$serviceid ({$_POST["BackEndsKeepAlive"]})");

}
function id_enable():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite(NginxGetDB());
    $tpl=new template_admin();
    $ID=intval($_GET["backend-enable"]);
    $ligne=$q->mysqli_fetch_array("SELECT serviceid,hostname,enabled FROM backends WHERE ID=$ID");
    $enabled_src=intval($ligne["enabled"]);
    $hostname=$ligne["hostname"];
    $serviceid=intval($ligne["serviceid"]);
    if($enabled_src==1){
        $admint="Disabled";
        $q->QUERY_SQL("UPDATE backends SET enabled='0' WHERE ID=$ID");
    }else{
        $admint="Enabled";
        $q->QUERY_SQL("UPDATE backends SET enabled='1' WHERE ID=$ID");
    }
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    echo "Loadjs('$page?td=$ID');";
    return admin_tracks("Set $hostname reverse-proxy backend to $admint");
}
function id_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["id-popup"]);
    $serviceid=0;
    $options=array();
    $Type=0;
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
	$md5=$_GET["md5"];
	$title="{new_item}";
    $q=new lib_sqlite(NginxGetDB());
    $ligne["port"]=80;

    if($serviceid>0) {
        $ligne = $q->mysqli_fetch_array("SELECT `type` FROM nginx_services WHERE ID=$serviceid");
        $Type = intval($ligne["type"]);
    }
	
	$btname="{add}";

	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM backends WHERE ID=$ID");
		$btname="{apply}";
		$title="{$ligne["hostname"]}:{$ligne["port"]}";
		$serviceid=$ligne["serviceid"];
        $ligne2 = $q->mysqli_fetch_array("SELECT `type` FROM nginx_services WHERE ID=$serviceid");
        $Type = intval($ligne2["type"]);
        $options=unserialize(base64_decode($ligne["options"]));

	}
	$js="dialogInstance2.close();LoadAjaxSilent('backends-reverse-$serviceid','$page?table=$serviceid');NgixSitesReload()";
	


    if($ID==0){
        if($Type==2){$ligne["port"]=80;}
        if($Type==13){$ligne["port"]=443;}
        if($Type==15){$ligne["port"]=53;}
    }
    if(intval($ligne["port"])==1){
        $ligne["port"]=80;
    }

    if(!isset($ligne["ssl"])){
        $ligne["ssl"]=0;
    }
    if(!isset($ligne["proxyproto"])){
        $ligne["proxyproto"]=0;
    }
    if(!isset($ligne["weight"])){
        $ligne["weight"]=1;
    }
    if(!isset($ligne["down"])){
        $ligne["down"]= 0;
    }
    if(!isset($ligne["backup"])){
        $ligne["backup"]= 0;
    }
    $weight = intval($ligne["weight"]);
    if ($weight==0){$weight=1;}

    $down= intval($ligne["down"]);
    $fail_timeout=10;

    if (!empty($ligne["fail_timeout"])){
        $fail_timeout= intval($ligne["fail_timeout"]);
    }

    $max_fails=1;
     if (!empty($ligne["max_fails"])){
         $max_fails= intval($ligne["max_fails"]);
     }

    if($ID==0){
        if($ligne["port"]<5){
            $ligne["port"]=rand(1024,64000);
        }
    }


    $hostname=get_servicename($serviceid);
	$form[]=$tpl->field_hidden("BackendSave", $ID);
	$form[]=$tpl->field_hidden("md5", $md5);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
	$form[]=$tpl->field_text("hostname", "{hostname}/{ipaddr}", $ligne["hostname"]);
	$form[]=$tpl->field_numeric("port","{port}",$ligne["port"]);
    if($Type==2){
        $form[]=$tpl->field_checkbox("CheckSsl","{UseSSL}",$ligne["ssl"]);
        $form[]=$tpl->field_checkbox("proxyproto","{proxy_protocol}",$ligne["proxyproto"]);
    }
    $form[]=$tpl->field_numeric("weight","{weight}",$weight);
    $form[]=$tpl->field_numeric("fail_timeout","{failure} {timeout}",$fail_timeout);
    $form[]=$tpl->field_numeric("max_fails","max {failure}",$max_fails);
    $form[]=$tpl->field_checkbox("backup","{backup}",intval($ligne["backup"]));
    $form[]=$tpl->field_checkbox("down","{down}",$down);

    echo $tpl->form_outside($title." {for} &laquo;$hostname&raquo;", $form,"",$btname,"$js","AsSystemWebMaster");
    return true;
	
}

function id_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["BackendSave"];
	$serviceid=intval($_POST['serviceid']);
	$q=new lib_sqlite(NginxGetDB());
	
	if($serviceid==0){
        echo $tpl->post_error("Service ID missing or null");
        return false;
    }
	
	$hostname=trim($_POST["hostname"]);

    if(preg_match("#^http.*?:#i",$hostname)){
        $parse_url=parse_url($hostname);
        $hostname=$parse_url["host"];
    }
	$port=intval($_POST["port"]);
	$options=base64_encode(serialize($_POST));
    $get_servicename=get_servicename($serviceid);
    $Type=get_ServiceType($serviceid);
    writelogs("Saving Reverse-proxy Backend $hostname:$port for service $get_servicename",__FUNCTION__,__FILE__,__LINE__);
    $weight = intval($_POST["weight"]);
    if ($weight==0){$weight=1;}
    $backup=intval($_POST["backup"]);

    $down= intval($_POST["down"]);
    $fail_timeout=$_POST["fail_timeout"];
    $max_fails=intval($_POST["max_fails"]);
    $ssl=intval($_POST["CheckSsl"]);
    $proxyproto=intval($_POST["proxyproto"]);

    if($Type==13){
        $ssl=1;
    }
    if($Type==15){
        $ssl=0;
    }
    
    if($port==443) {
        if($ssl==0){
            echo $tpl->post_error("Port 443 But no SSL checked ?");
            return false;
        }
    }

	if($ID==0){
		$q->QUERY_SQL("INSERT OR IGNORE INTO backends(serviceid,hostname,port,options,weight,backup,fail_timeout,max_fails,down,ssl,proxyproto) 
				VALUES ($serviceid,'$hostname',$port,'$options','$weight','$backup','$fail_timeout','$max_fails','$down','$ssl','$proxyproto')");
		if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
		return admin_tracks("Saving New Reverse-proxy Backend $hostname:$port for service $get_servicename");
	}


    $sql="UPDATE backends SET hostname='$hostname',port='$port',options='$options',weight='$weight',backup='$backup',fail_timeout='$fail_timeout',max_fails='$max_fails',down='$down', ssl='$ssl',proxyproto='$proxyproto' WHERE ID=$ID";


	$q->QUERY_SQL($sql);
	if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Modify Reverse-proxy Backend $hostname:$port for service $get_servicename");

}
function get_ServiceType($ID):int{
    $ID=intval($ID);
    if($ID==0){return 0;}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT type FROM nginx_services WHERE ID=$ID");
    return intval($ligne["type"]);
}


function ssl_switch():bool{
    $page               = CurrentPageName();
    $serviceid          = intval($_GET["ssl-switch"]);
    $sock               = new socksngix($serviceid);
    $UseSSL             = $sock->GET_INFO("UseSSL");
    if($UseSSL==0){
        $sock->SET_INFO("UseSSL",1);
    }else{
        $sock->SET_INFO("UseSSL",0);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('backends-reverse-$serviceid','$page?table=$serviceid');NgixSitesReload();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;

}
function unsecure_switch():bool{
    $page               = CurrentPageName();
    $serviceid          = intval($_GET["insecure-switch"]);
    $sock               = new socksngix($serviceid);
    $DisableInsecure              = intval($sock->GET_INFO("DisableInsecure"));
    if($DisableInsecure==0){
        $sock->SET_INFO("DisableInsecure",1);
    }else{
        $sock->SET_INFO("DisableInsecure",0);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}
function backend_move(){
    $tpl=new template_admin();
    $ID=$_GET["acl-rule-move"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT zOrder FROM dnsdist_rules WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
    $xORDER_ORG=intval($ligne["zOrder"]);
    $xORDER=$xORDER_ORG;


    if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
    if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
    if($xORDER<0){$xORDER=0;}
    $sql="UPDATE dnsdist_rules SET zOrder=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    if($GLOBALS["VERBOSE"]){echo "$sql\n";}

    if($_GET["acl-rule-dir"]==1){
        $xORDER2=$xORDER+1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE dnsdist_rules SET zOrder=$xORDER2 WHERE `ID`<>'$ID' AND zOrder=$xORDER";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}

        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }
    if($_GET["acl-rule-dir"]==0){
        $xORDER2=$xORDER-1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE dnsdist_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}' AND zOrder=$xORDER";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
    }

    $c=0;
    $sql="SELECT ID FROM dnsdist_rules ORDER BY zOrder";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE dnsdist_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "UPDATE dnsdist_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }


}


function UpStreamZonesStatus():array{
   // $tpl=new template_admin();
    $sock=new sockets();
    $ARR=array();
    $data=$sock->REST_API_NGINX("/reverse-proxy/upstreamzones");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
       return $ARR;
    }
    if(!$json->Status){
        return $ARR;

    }

    foreach ($json->Maps as $index=>$array){
        $ARR["MAPS"][$array->serviceid][$array->Host]=true;

    }

    foreach ($json->Zones as $zone=>$array){
        foreach ($array as $index=>$Servers){
            $ServerName=$Servers->server;
            $requestCounter=$Servers->requestCounter;
            $ARR[$ServerName]=$requestCounter;
        }
    }

    return $ARR;
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $serviceid=intval($_GET["table"]);
    $Type=get_ServiceType($serviceid);
    $UpStreams=UpStreamZonesStatus();
    $sock               = new socksngix($serviceid);
    $UseSSL=$sock->GET_INFO("UseSSL");
    $TRCLASS=null;

    $html[]="<div id='top-buttons-backends-$serviceid' style='margin-top:20px'></div>";
    $html[]="<table id='table-backends-$serviceid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    list($td,$TRCLASS)=td_options($TRCLASS,$serviceid);

	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th colspan='2'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>URL</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $html[]=$td;
    $UpStreamsInConf=array();
    if(isset($UpStreams["MAPS"][$serviceid])) {
        $UpStreamsInConf = $UpStreams["MAPS"][$serviceid];
    }
	$q=new lib_sqlite(NginxGetDB());

    $snih2="";
    $proxy_ssl_server_name=intval($sock->GET_INFO("proxy_ssl_server_name"));
    $proxy_ssl_name =trim($sock->GET_INFO("proxy_ssl_name"));
    if($proxy_ssl_server_name==1){
        $snih2="<br>{snih2}: $proxy_ssl_name";
    }

	$results=$q->QUERY_SQL("SELECT * FROM backends WHERE serviceid='{$_GET["table"]}' ORDER BY zOrder ASC");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}

    $ForwardServersDynamics =   intval($sock->GET_INFO("ForwardServersDynamics"));
    if($ForwardServersDynamics==1) {
        $arrow="&nbsp;&nbsp;<i class='fa-solid fa-arrow-right-to-line'></i>&nbsp;&nbsp;";
        $FSDynamicsExt = intval($sock->GET_INFO("FSDynamicsExt"));
        $FSDynamicsSrc = trim($sock->GET_INFO("FSDynamicsSrc"));
        $FSDynamicsDst = trim($sock->GET_INFO("FSDynamicsDst"));

        if($FSDynamicsExt==1){
            if(preg_match("#\.(.*?)$#",$FSDynamicsSrc,$re)){
                $FSDynamicsSrc=str_replace(".".$re[1],".*",$FSDynamicsSrc);
            }else{
                $FSDynamicsSrc=$FSDynamicsSrc.".*";
            }
            if(preg_match("#\.(.*?)$#",$FSDynamicsDst,$re)){
                $FSDynamicsDst=str_replace(".".$re[1],".*",$FSDynamicsDst);
            }else{
                $FSDynamicsDst=$FSDynamicsDst.".*";
            }
        }

        $TRCLASS="footable-odd";
        $FSDynamicsSrc=$tpl->td_href($FSDynamicsSrc,null,"Loadjs('fw.nginx.sites.dynamics.php?service-id=$serviceid');");

        $proto="http";
        if($UseSSL==1){$proto="https";}
        if($UseSSL==0){$proto="http";}
        $uri_text="<strong></strong>";
        $html[]="<tr class='$TRCLASS' id='a000'>";
        $html[]="<td style='width:1%' nowrap><span class='label label-primary'>{dynamic}</span></td>";
        $html[]="<td nowrap><span style='font-size: medium'>*.$FSDynamicsSrc</span>&nbsp;$arrow&nbsp;<span style='font-size: medium'>$proto://*.$FSDynamicsDst/</span><small>$snih2</small></td>";
        $html[]="<td style='width:1%' nowrap></td>";
        $html[]="<td style='width:1%' class='center' nowrap></td>";
        $html[]="<td style='width:1%' class='center' nowrap></td>";
        $html[]="<td style='width:1%' class='center'></td>";
        $html[]="<td style='width:1%' class='center'></td>";
        $html[]="</tr>";

    }

    $protocol_port_no_sense="<br><small class='text-center'><strong><i class='".ico_emergency."'></i>&nbsp;<i>{protocol_port_no_sense}</i></strong></small>";

	foreach ($results as $md5=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $info_port=null;
		$options=array();
		$md5=md5(serialize($ligne));
		$ID=intval($ligne["ID"]);
		$port=$ligne["port"];
        $port_text=":$port";
		$hostname=$ligne["hostname"];
        $proto="http";
        $weight = intval($ligne["weight"]);
        if ($weight==0){$weight=1;}
        $ssl=intval($ligne["ssl"]);
        $fail_timeout=intval($ligne["fail_timeout"]);
        if($fail_timeout==0){$fail_timeout=10;}
        $max_fails= intval($ligne["max_fails"]);
        if($max_fails==0){$max_fails=1;}
        $proxyproto="";
        $opts=array();
        $opts[]="{weight} $weight";
        $opts[]="{timeout} $fail_timeout";
        $opts[]="Max {failure} $max_fails";
        if($ligne["proxyproto"]==1){
            $proxyproto="&nbsp;-&nbsp;Proxy";
        }

        $opts_text=@implode(", ",$opts);

        if(preg_match("#^http.*?:#i",$hostname)){
            $proto="http";
            $ssl=0;
            $parse_url=parse_url($hostname);
            $hostname=$parse_url["host"];
        }
        if(preg_match("#^https.*?:#i",$hostname)){
            $proto="https";
            $ssl=1;
            $parse_url=parse_url($hostname);
            $hostname=$parse_url["host"];
        }

        if($ssl==1){
            $proto="https";
            if($port==443){$port_text=null;}
            if($port==80){$info_port=$protocol_port_no_sense;}
        }
        if($ssl==0){
            $proto="http";
            if($port==80){$port_text=null;}
            if($port==443){$info_port=$protocol_port_no_sense;}
        }
        if($Type==5){
            $proto="tcp/udp";
        }


        $KeyUpstream="$hostname:$port";
        if(!isset($UpStreamsInConf[$KeyUpstream])){
            $MainStatus="<span class='label label-default'>{inactive2}</span>";
        }else{
            $MainStatus="<span class='label label-primary'>{active2}</span>";
        }



        $uri_text="<strong>$proto://$hostname$port_text/</strong>";
		$js="Loadjs('$page?id-js=$ID&md5=$md5')";
		if(count($options)==0){$options[]=$tpl->icon_nothing();}
        $up=$tpl->icon_up("Loadjs('$page?backend-move=$ID&acl-rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?backend-move=$ID&acl-rule-dir=0');");
        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?backend-enable=$ID&md=$md5');",null,"AsWebAdministrator");
        $sslI="";
        if($ssl==1){
            $sslI="<span class='label label-warning'>SSL$proxyproto</span>";
        }else{
            if($ligne["proxyproto"]==1){
                $sslI="<span class='label label-warning'>Proxy</span>";
            }
        }
		$html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td style='width:1%' nowrap><span id='backend-status-$ID'>$MainStatus</span></td>";
		$html[]="<td nowrap><span style='font-size: medium'>".$tpl->td_href("$hostname:$port",null,$js)." $info_port</span></td>";
        $html[]="<td style='width:1%' nowrap><span style='font-size: medium'>".$tpl->td_href($uri_text,null,$js)."<br><small>$opts_text$snih2</small></span></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$sslI</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$enable</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</td>";
        $html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5')","AsSystemWebMaster")."</td>";
		$html[]="</tr>";
	}




	$html[]="</tbody>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	LoadAjaxSilent('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function td_row(){

    $tpl=new template_admin();
    $ID=intval($_GET["td"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backends WHERE ID=$ID");
    $port=$ligne["port"];
    $hostname=$ligne["hostname"];
    $enabled=intval($ligne["enabled"]);
    $serviceid=intval($ligne["serviceid"]);


    if($enabled==0){
        $MainStatus="<span class='label label-default'>{inactive2}</span>";
    }else {
        sleep(2);
        $UpStreamZonesStatus=UpStreamZonesStatus();
        $UpStreamsInConf=$UpStreamZonesStatus["MAPS"][$serviceid];
        $KeyUpstream = "$hostname:$port";
        if (!isset($UpStreamsInConf[$KeyUpstream])) {
            $MainStatus = "<span class='label label-default'>{inactive2}</span>";
        } else {
            $MainStatus = "<span class='label label-primary'>{active2}</span>";
        }
    }


    $MainStatus=base64_encode($tpl->_ENGINE_parse_body($MainStatus));
    header("content-type: application/x-javascript");
    $f[]="if( document.getElementById('backend-status-$ID') ){";
    $f[]="document.getElementById('backend-status-$ID').innerHTML=base64_decode('$MainStatus');";
    $f[]="}";
    echo @implode("\n",$f);
}

function td_options_build($label,$text,$serviceid):string{
    $ico=ico_params;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="Loadjs('$page?options-js=$serviceid')";
    $dd=$tpl->td_href($text,null,$js);
    $label=$tpl->td_href($label,null,$js);
    $html[]="<tr>";
    $html[]="<td style='padding-top: 5px;width:1%' nowrap><i class='$ico'></i>&nbsp;</td>";
    $html[]="<td style='padding-top: 5px;width:1%' nowrap>$label:&nbsp;</td>";
    $html[]="<td style='padding-top: 5px;width:99%'>$dd</td>";
    $html[]="</tr>";
    return @implode("",$html);
}

function td_options($TRCLASS,$serviceid){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=get_ServiceType($serviceid);
    if($type==15){return "";}
    if($type==5){return "";}
    $sock               = new socksngix($serviceid);
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $NginxHTTPSubModule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"));
    $resolvers =  trim($sock->GET_INFO("resolvers"));

    $HostHeader=$sock->GET_INFO("HostHeader");
    $HostHeaderReplace=intval($sock->GET_INFO("HostHeaderReplace"));
    $ProtoReplaceContent=intval($sock->GET_INFO("ProtoReplaceContent"));

    $proxy_http_version=trim($sock->GET_INFO("proxy_http_version"));
    if($proxy_http_version==null){$proxy_http_version="1.0";}

    $js="Loadjs('$page?options-js=$serviceid')";

    $RemotePath=$sock->GET_INFO("RemotePath");
    $default_page       = trim($sock->GET_INFO("default_page"));
    $form[]=$tpl->field_text("RemotePath","{TargetRemotePath}",$sock->GET_INFO("RemotePath"));
    $form[]=$tpl->field_text("default_page","{nginx_default_page}",$default_page,false,"url:https://wiki.articatech.com/en/reverse-proxy/architecture/default-index;{nginx_default_page_explain}");

    if(strlen($resolvers)>3){
        $text[]="DNS: $resolvers";
    }

    if(strlen($HostHeader)>2){
        $text[]="{HostHeader} $HostHeader";
    }


    if($NginxHTTPSubModule==1) {
        if ($HostHeaderReplace == 1) {
            $text[] = "{HostHeaderReplace}";
        }

        if ($ProtoReplaceContent == 1) {
            $text[]="{ProtoReplaceContent}";
        }
    }

    if(strlen($RemotePath)>2){
        $text[]="{TargetRemotePath}";
    }
    if(strlen($default_page)>2){
        $text[]="{nginx_default_page}";
    }
    $text[]="{proxy_http_version} $proxy_http_version";

    $ff=@implode(", ",$text);
    $html[]="<tr class='$TRCLASS' id='config1'>";
    $html[]="<td nowrap colspan='7' wtyle='width:99%'><li class='".ico_options."'></li>&nbsp;{options}: ". $tpl->td_href($ff,null,$js)."</td>";
    $html[]="</tr>";
    return array(@implode($html),$TRCLASS);


}
function options_popup():bool{
    $serviceid=$_GET["options-popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $NginxHTTPSubModule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"));
    $sock               = new socksngix($serviceid);
    $default_page       = trim($sock->GET_INFO("default_page"));
    $resolvers =  trim($sock->GET_INFO("resolvers"));

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("HostHeader","{HostHeader}",$sock->GET_INFO("HostHeader"));

    $proxy_http_version=trim($sock->GET_INFO("proxy_http_version"));
    if($proxy_http_version==null){$proxy_http_version="1.0";}

    $zproxy_http_version["1.0"]="1.0: {default} ";
    $zproxy_http_version["1.1"]="1.1: KeepAlive {or} NTLM";


    $form[]=$tpl->field_array_hash($zproxy_http_version,"proxy_http_version","{proxy_http_version}",$proxy_http_version);


    if($NginxHTTPSubModule==0){$tpl->field_hidden("HostHeaderReplace",0);}
    if($NginxHTTPSubModule==1) {
        $form[] = $tpl->field_checkbox("HostHeaderReplace", "{HostHeaderReplace}", $sock->GET_INFO("HostHeaderReplace"));
        $form[] = $tpl->field_checkbox("ProtoReplaceContent","{ProtoReplaceContent}", $sock->GET_INFO("ProtoReplaceContent"));
    }
    $form[]=$tpl->field_text("RemotePath","{TargetRemotePath}",$sock->GET_INFO("RemotePath"));
    $form[]=$tpl->field_text("default_page","{nginx_default_page}",$default_page,false,"url:https://wiki.articatech.com/en/reverse-proxy/architecture/default-index;{nginx_default_page_explain}");
    $form[]=$tpl->field_text("resolvers","{nic_static_dns}",$resolvers);

    $form[]=$tpl->field_section("SSL");
    if($NginxHTTPSubModule==1) {
        $form[] = $tpl->field_checkbox("ProtoReplaceContent", "{ProtoReplaceContent}", $sock->GET_INFO("ProtoReplaceContent"));
    }
    $proxy_ssl_server_name=intval($sock->GET_INFO("proxy_ssl_server_name"));
    $proxy_ssl_name =trim($sock->GET_INFO("proxy_ssl_name"));
    $form[]=$tpl->field_checkbox("proxy_ssl_server_name","{snih2}",$proxy_ssl_server_name,"proxy_ssl_name");
    $form[]=$tpl->field_text("proxy_ssl_name","{domain}",$proxy_ssl_name,false,"{proxy_ssl_server_name_explain}");

    $js="LoadAjax('backends-reverse-$serviceid','$page?table=$serviceid')";
    echo $tpl->form_outside(null, $form,"","{apply}","$js","AsSystemWebMaster");
    return true;
}
function options_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    unset($_POST["serviceid"]);
    $sock=new socksngix($serviceid);
    foreach ($_POST as $key=>$val){
        $sock->SET_INFO($key,$val);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks_post("Saving backends options for Reverse-Proxy ".get_servicename($serviceid));

}

function top_buttons():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["top-buttons"];
    $page=CurrentPageName();
    $sock                   = new socksngix($serviceid);
    $ForwardServersDynamics = intval($sock->GET_INFO("ForwardServersDynamics"));
    $UseSSL                 = $sock->GET_INFO("UseSSL");
    $KeepAlive              = intval($sock->GET_INFO("BackEndsKeepAlive"));
    $FORCESSL               = false;
    $q=new lib_sqlite(NginxGetDB());
    $zline=$q->mysqli_fetch_array("SELECT type FROM nginx_services WHERE ID=$serviceid");
    $Type=$zline["type"];
    if($Type==13){
        $sock->SET_INFO("UseSSL",1);
        $FORCESSL=true;
    }

    if($ForwardServersDynamics==0) {
        $topbuttons[] = array("Loadjs('$page?id-js=0&serviceid=$serviceid&md5=');", ico_plus, "{new_backend}");
    }
    if($Type<>5) {
        if ($FORCESSL) {
            $topbuttons[] = array("blur()", ico_certificate, "{remote_server_use_ssl} ON");
        }
    }

    if($Type<>5) {
        $topbuttons[] = top_button_insecure($serviceid);
    }
    if($KeepAlive==0){
        $topbuttons[]=array("Loadjs('$page?backends-keepalive-js=$serviceid&md5=');", ico_timeout,"{keep_alive} OFF");
    }else{
        $topbuttons[]=array("Loadjs('$page?backends-keepalive-js=$serviceid&md5=');", ico_timeout,"{keep_alive} ON");
    }
    $lb_method=intval($sock->GET_INFO("lb_method"));
    $LBCookiesTime=intval($sock->GET_INFO("LBCookiesTime"));
    if($LBCookiesTime<30){
        $LBCookiesTime=3600;
    }
    if($lb_method==3){
        $topbuttons[]=array("Loadjs('$page?load-balancing-js=$serviceid&md5=');", ico_params,
            "Cookie ({$LBCookiesTime}s)");
    }else{
        $topbuttons[]=array("Loadjs('$page?load-balancing-js=$serviceid&md5=');", ico_params,$GLOBALS["HASHLB"][$lb_method]);
    }



    echo $tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    return true;
}

function top_button_insecure($serviceid):array{
    $page=CurrentPageName();
    $sock                   = new socksngix($serviceid);
    $DisableInsecure              = intval($sock->GET_INFO("DisableInsecure"));
    if($DisableInsecure==0){
        return array("Loadjs('$page?insecure-switch=$serviceid&md5=');", ico_proto, "{insecure} ON");
    }

    return array("Loadjs('$page?insecure-switch=$serviceid&md5=');", ico_proto, "{insecure} OFF");
}
