<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["upload-js"])){upload_js();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["file-uploaded"])){uploaded_js();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_confirm();exit;}
if(isset($_GET["cnx"])){rule_js();exit;}
if(isset($_GET["cnx-popup"])){rule_popup();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["zmd5"])){rule_save();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["stop"])){stop();exit;}
if(isset($_GET["td-row"])){td_row($_GET["td-row"]);exit;}
if(isset($_GET["js-tiny"])){js_tiny();exit;}
page();

function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $cnx=$_GET["cnx"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM vpn_client WHERE zmd5='$cnx'");
    $rulename=$ligne["rulename"];
    $function=$_GET["function"];
    return $tpl->js_dialog2($rulename,"$page?cnx-popup=$cnx&function=$function");
}

function delete_js():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $md5=$_GET["delete"];
    $md=$_GET["md"];
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM vpn_client WHERE zmd5='$md5'");
    $rulename=$ligne["rulename"];
    return $tpl->js_confirm_delete($rulename,"delete",$md5,"$('#$md').remove()");
}
function delete_confirm():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $md5=$_POST["delete"];
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM vpn_client WHERE zmd5='$md5'");
    $rulename=$ligne["rulename"];
    $q->QUERY_SQL("DELETE FROM vpn_client WHERE zmd5='$md5'");
    $sock=new sockets();
    $sock->REST_API("/openvpn/client/uninstall/$md5");
    return admin_tracks("Delete OpenVPN Client connection $rulename");
}
function enable():bool{
    $page=CurrentPageName();
    $zmd5=$_GET["enable"];
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $sock=new sockets();
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enable FROM vpn_client WHERE zmd5='$zmd5'");
    $enable=intval($ligne["enable"]);
    $rulename=$ligne["rulename"];
    header("content-type: application/x-javascript");
    if($enable==0){
        $q->QUERY_SQL("UPDATE vpn_client SET enable=1 WHERE zmd5='$zmd5'");
        $sock->REST_API("/openvpn/client/enable/$zmd5");
        echo "Loadjs('$page?td-row=$zmd5')";
        return admin_tracks("Enable OpenVPN Client connection $rulename");
    }

    $q->QUERY_SQL("UPDATE vpn_client SET enable=0 WHERE zmd5='$zmd5'");
    $sock->REST_API("/openvpn/client/disable/$zmd5");
    echo "Loadjs('$page?td-row=$zmd5')";
    return admin_tracks("Disable OpenVPN Client connection $rulename");
}
function start():bool{
    $zmd5=$_GET["start"];
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $sock=new sockets();
    $state=base64_encode($tpl->_ENGINE_parse_body("<span class='label label-warning'>{starting}</span>"));
    $sock->REST_API("/openvpn/client/start/$zmd5");
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enable FROM vpn_client WHERE zmd5='$zmd5'");
    $rulename=$ligne["rulename"];
    $f[]="if (document.getElementById('status-$zmd5') ){";
    $f[]="\tdocument.getElementById('status-$zmd5').innerHTML=base64_decode('$state');";
    $f[]="}";
    $f[]="setTimeout(function(){ Loadjs('$page?td-row=$zmd5'); }, 2000);";
    echo @implode("\n",$f);
    return admin_tracks("Starting OpenVPN Client connection $rulename");
}

function stop():bool{
    $zmd5=$_GET["stop"];
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $sock=new sockets();
    $state=base64_encode($tpl->_ENGINE_parse_body("<span class='label label-warning'>{stopping}</span>"));
    $sock->REST_API("/openvpn/client/stop/$zmd5");
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enable FROM vpn_client WHERE zmd5='$zmd5'");
    $rulename=$ligne["rulename"];
    $f[]="if (document.getElementById('status-$zmd5') ){";
    $f[]="\tdocument.getElementById('status-$zmd5').innerHTML=base64_decode('$state');";
    $f[]="}";
    $f[]="setTimeout(function(){ Loadjs('$page?td-row=$zmd5'); }, 2000);";
    echo @implode("\n",$f);
    return admin_tracks("Stopping OpenVPN Client connection $rulename");
}
function rule_popup():bool{
    $tpl=new template_admin();
    $disableform=false;
    $function=$_GET["function"];
    $cnx=$_GET["cnx-popup"];
    $Auth=false;
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,username,password,details FROM vpn_client WHERE zmd5='$cnx'");

    $info=unserialize(base64_decode($ligne["details"]));
    $server="{$info["proto"]}://{$info["server"]}:{$info["port"]}";
    if($info["auth"]=="yes"){
        $Auth=true;
    }

    if(preg_match("#^[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+$#",$ligne["username"])){
        $disableform=true;
    }
    if($ligne["debugmode"]==0){
        $ligne["debugmode"]=2;
    }
    $verb[2]="{normal}";
    for($i=3;$i<128;$i++){
        $verb[$i]=$i;
    }

    $form[]=$tpl->field_hidden("zmd5",$cnx);
    $form[]=$tpl->field_text("rulename", "{connection_name}", $ligne["rulename"]);
    $form[] = $tpl->field_text("username", "{username}", $ligne["username"]);
    $form[] = $tpl->field_password("password", "{password}", $ligne["password"]);
    $form[] = $tpl->field_array_hash($verb,"debugmode","{debug}",$ligne["debugmode"]);

    $html=$tpl->form_outside($server, $form,null,"{apply}","$function();dialogInstance2.close();","AsVPNManager",false,$disableform);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

    if(!$q->FIELD_EXISTS("vpn_client","debugmode")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `debugmode` INTEGER NOT NULL DEFAULT 0");
    }

    $zmd5=$_POST["zmd5"];
    $upd["rulename"]=$_POST["rulename"];
    $upd["debugmode"]=$_POST["debugmode"];
    if(isset($_POST["username"])){
        $upd["username"]=$_POST["username"];
        $upd["password"]=$_POST["password"];
    }

    foreach ($upd as $key=>$val){
        $qq[]="`$key`='$val'";
    }
    $q->QUERY_SQL("UPDATE vpn_client SET ".@implode(",",$qq)." WHERE zmd5='$zmd5'");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $sock=new sockets();
    $sock->REST_API("/openvpn/client/install/$zmd5");
    return admin_tracks("Updating VPN Client Connection {$_POST["rulename"]} ($zmd5)");
}

function td_row($zmd5):bool{


    list($StatusTool,$error)=td_status($zmd5);
    $tpl=new template_admin();
    $StatusTool=base64_encode($tpl->_ENGINE_parse_body($StatusTool));
    $error=base64_encode($tpl->_ENGINE_parse_body($error));
    header("content-type: application/x-javascript");
    $f[]="if (document.getElementById('status-$zmd5') ){";
    $f[]="\tdocument.getElementById('status-$zmd5').innerHTML=base64_decode('$StatusTool');";
    $f[]="}";
    $f[]="if (document.getElementById('error-$zmd5') ){";
    $f[]="\tdocument.getElementById('error-$zmd5').innerHTML=base64_decode('$error');";
    $f[]="}";

    echo @implode("\n",$f);
    return true;


}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");

    $html=$tpl->page_header("{vpn_client} v$OpenVPNVersion",
        "fa fa-compress","{vpn_client_explain}","$page?main-start=yes",
        "vpn-client","progress-vpnclient-restart",false,"table-vpnclient");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function main_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&main=yes");
    return true;
}
function main():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    if(!$q->TABLE_EXISTS("vpn_client")){
        $sql="CREATE TABLE IF NOT EXISTS `vpn_client` (
		`zmd5` TEXT NOT NULL PRIMARY KEY,
		`rulename` TEXT NOT NULL DEFAULT '',
		`zipcontent` TEXT NOT NULL DEFAULT '',
		 `details` TEXT NOT NULL DEFAULT '',
		 `error` TEXT NOT NULL DEFAULT '',
		`enable`INTEGER NOT NULL DEFAULT 1,
		`username` TEXT NOT NULL DEFAULT '',
		`password` TEXT NOT NULL DEFAULT '',
        `lifetime` INTEGER NOT NULL DEFAULT 0,
        `starttime` INTEGER NOT NULL DEFAULT 0,
        `stoptime` INTEGER NOT NULL DEFAULT 0,
        `support` INTEGER NOT NULL DEFAULT 0,
        `asgateway` INTEGER NOT NULL DEFAULT 0,
        `interface` TEXT NOT NULL DEFAULT '',
        `ipaddr` TEXT NOT NULL DEFAULT '',
        `netmask` TEXT NOT NULL DEFAULT '',
        `network` TEXT NOT NULL DEFAULT '',
        `uptime` TEXT NOT NULL DEFAULT '',
		`status` INT NOT NULL DEFAULT 0                            
		) ";
        $q->QUERY_SQL($sql);
    }
    if(!$q->FIELD_EXISTS("vpn_client","status")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `status` INT NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("vpn_client","lifetime")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `lifetime` INT NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("vpn_client","starttime")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `starttime` INT NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("vpn_client","stoptime")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `stoptime` INT NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("vpn_client","uptime")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `uptime` TEXT NOT NULL DEFAULT ''");
    }
    if(!$q->FIELD_EXISTS("vpn_client","support")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `support` INT NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("vpn_client","asgateway")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `asgateway` INT NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("vpn_client","netmask")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `netmask` TEXT NOT NULL DEFAULT ''");
    }
    if(!$q->FIELD_EXISTS("vpn_client","ipaddr")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `ipaddr` TEXT NOT NULL DEFAULT ''");
    }
    if(!$q->FIELD_EXISTS("vpn_client","interface")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `interface` TEXT NOT NULL DEFAULT ''");
    }
    if(!$q->FIELD_EXISTS("vpn_client","network")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `network` TEXT NOT NULL DEFAULT ''");
    }
    $EnableArticaAsGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{connection}</a></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{nic}</a></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</a></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{server}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{username}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>LOG</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{start}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{stop}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sql="SELECT *  FROM vpn_client ORDER BY rulename";

    $search=$_GET["search"];
    if(strlen($search)>2){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT *  FROM openvpn_clients WHERE ( (rulename LIKE '$search') OR ( details LIKE '$search') ) ORDER BY rulename";
    }
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error_html(true,$sql);
    }
    $TRCLASS=null;

    $serv=ico_servcloud;
    $userico=ico_user;
    $nicido=ico_nic;
    foreach($results as $index=>$ligne) {
        $plug=ico_plug;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=$ligne["zmd5"];
        $StatusTool=null;
        $error=null;
        $Auth=false;
        $expire="";
        $md=md5(serialize($ligne));
        $rulename=$ligne["rulename"];
        $username=$ligne["username"];
        $enable=$ligne["enable"];
        $lifetime=$ligne["lifetime"];
        $gatateway_text="<span class='label label-default'>{inactive}</span>";
        $interface=$ligne["interface"];
        $ipaddr=$ligne["ipaddr"];
        $asgateway=$ligne["asgateway"];
        $support=$ligne["support"];
        $Network="&nbsp;";
        if($EnableArticaAsGateway==1){
            $asgateway=1;
        }



        if($asgateway==1){
            $gatateway_text="<span class='label label-primary'>{active2}</span>";
        }

        $delete=$tpl->icon_delete("Loadjs('$page?delete=$zmd5&md=$md')","AsVPNManager");
        $info=unserialize(base64_decode($ligne["details"]));
        $server="{$info["proto"]}://{$info["server"]}:{$info["port"]}";
        if($info["auth"]=="yes"){
            $Auth=true;
        }
        if(preg_match("#^[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+$#",$ligne["username"])){
            $plug=ico_support;
        }
        if($lifetime>0){
            $expire=sprintf("<strong><i>{expire_in} %s</i></strong>", distanceOfTimeInWords(time(),$lifetime,true));

        }
        if( strlen($interface)>2){
            $Network="<i class='$nicido'></i>&nbsp;$interface: $ipaddr";
        }


        $start=$tpl->icon_run("Loadjs('$page?start=$zmd5')");
        $stop=$tpl->icon_stop("Loadjs('$page?stop=$zmd5')");
        list($StatusTool,$error)=td_status($zmd5);

        $IconEnable=$tpl->icon_check($enable,"Loadjs('$page?enable=$zmd5')",null,"AsVPNManager");
        if($support==1){
            $gatateway_text="&nbsp;";
        }
        $server=$tpl->td_href($server,null,"Loadjs('$page?cnx=$zmd5&function=$function')");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $rulename=$tpl->td_href($rulename,null,"Loadjs('$page?cnx=$zmd5&function=$function')");
        $html[]="<td width='1%' nowrap><span id='status-$zmd5'>$StatusTool</span></td>";
        $html[]="<td><i class='$plug'></i>&nbsp;$rulename$expire<span id='error-$zmd5'>$error<span></td>";
        $html[]="<td width='1%' nowrap>$Network</td>";
        $html[]="<td width='1%' nowrap>$gatateway_text</td>";
        $html[]="<td><i class='$serv'></i>&nbsp;$server</td>";
        $userclass="";
        if(strlen($username)<3){
            $userclass="text-danger";
            $username="<span class='text-danger'>{missing}</span>";
        }
        $logico=$tpl->icon_loupe(true,"Loadjs('fw.openvpn.events.php?client-js=$zmd5')");

        $html[] = "<td><i class='$userico $userclass'></i>&nbsp;$username</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$logico</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$start</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$stop</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$IconEnable</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='11'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";





    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
	$html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true } } ); });";
	$html[]="Loadjs('$page?js-tiny=yes&function=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function js_tiny(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $users=new usersMenus();
    $topbuttons=array();
    if($users->AsVPNManager) {
        $topbuttons[] = array("Loadjs('$page?upload-js=yes&function=$function')", ico_plus, "{create_a_connection}");
    }

    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/service/version");
    $TINY_ARRAY["EXPL"]="{vpn_client_explain}";


    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $TINY_ARRAY["DANGER"]=true;
    }
    if(!$json->Status){
        $TINY_ARRAY["EXPL"]="{vpn_client_explain}<br><strong>$json->Error</strong>";


    }
    $topbuttons[] = array("Loadjs('fw.system.upgrade-software.php?product=APP_OPENVPN')", ico_cd, "{upgrade}");
    $TINY_ARRAY["TITLE"]="{vpn_client} v$OpenVPNVersion";
    $TINY_ARRAY["ICO"]="fa fa-compress";

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}


function upload_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog6("{upload_connection}", "$page?upload-popup=yes&function=$function",550);
}
function upload_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $btn=$tpl->button_upload("{upload_connection}",$page,null,"&function=$function");
    $html="<div class='alert alert-success'>{upload_connection_vpn_client}</div>
			<div class='center' style='margin:30px'>$btn</div>
			";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function td_status($zmd5):array{

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT status,error,enable,uptime FROM vpn_client WHERE zmd5='$zmd5'");
    $error=$ligne["error"];
    $enable=intval($ligne["enable"]);
    $uptime=$ligne["uptime"];
    $StatusTool="";
    $stateZ=td_state($zmd5);

    $status=intval($ligne["status"]);
    if($status==110){
        $StatusTool="<span class='label label-danger'>{error}</span>";
        $error="<br><span class='text-danger'>$error</span>";
    }
    if($status==100){

        $StatusTool=td_state($zmd5);

        if (strlen($uptime)>0) {
            $error = "<br>{running_since}: $uptime";
        }
    }
    if($status==200){
        $StatusTool="<span class='label label-danger'>{stopped}</span>";
        $error="";
    }
    if($status==500){
        $StatusTool="<span class='label label-danger'>{stopped}</span>";
        $error="<br><span class='text-danger'>$error</span>";
    }
    if($status==0){
        $StatusTool="<span class='label label-default'>{uninstalled}</span>";
        $error="<br><span class='text-danger'>$error</span>";
    }
    if($enable==0){
        $StatusTool="<span class='label label-default'>{disabled}</span>";
    }
    $tpl=new template_admin();
    $StatusTool=$tpl->_ENGINE_parse_body($StatusTool);
    return array($StatusTool,$error);

}

function td_state($zmd5){
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("openvpn/client/state/$zmd5");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return "<span class='label label-danger'>Err.10</span>";
    }

    $state=$json->Info;
    if ($state->Connected=="CONNECTED"){
        return $tpl->_ENGINE_parse_body("<span class='label label-primary'>{connected}</span>");
    }
    return $tpl->_ENGINE_parse_body("<span class='label label-default'>{$state->Connected}</span>");
}

function uploaded_js():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];

    $sock=new sockets();
    $data = $sock->REST_API("/openvpn/client/config/upload/$filename");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->js_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    echo "$function();\n";
    return true;
}