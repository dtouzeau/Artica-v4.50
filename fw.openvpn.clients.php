<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ConnectSupport"])){support_save();exit;}

if(isset($_GET["uid-row"])){uid_row();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["connection_name"])){buildconfig();exit;}
if(isset($_POST["delete-script"])){delete_script();exit;}
if(isset($_GET["rebuild"])){rebuild();exit;}
if(isset($_GET["support-js"])){support_js();exit;}
if(isset($_GET["support-popup"])){support_popup();exit;}

page();
function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$_GET["uid"]=urlencode($_GET["uid"]);
    $function=$_GET["function"];
	return $tpl->js_dialog("{BUILD_OPENVPN_CLIENT_CONFIG}","$page?rule-popup=yes&uid={$_GET["uid"]}&function=$function");
}

function delete_rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $uid=base64_decode($_GET["delete-js"]);
	$delete=$tpl->javascript_parse_text("{delete} $uid");
	
	
echo "	
	var xOpenVPNRoutesDelete$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		 $('#{$_GET["id"]}').remove();
	}
	
	function OpenVPNRoutesDelete$t(){
		if(!confirm('$delete ?')){return;}
		var XHR = new XHRConnection();
		
		XHR.appendData('delete-script','{$_GET["delete-js"]}');
		XHR.sendAndLoad('$page', 'POST',xOpenVPNRoutesDelete$t);
	}
	
	OpenVPNRoutesDelete$t();
	";
	return true;
}

function support_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $_GET["uid"]=urlencode($_GET["uid"]);
    $function=$_GET["function"];
    return $tpl->js_dialog("{create_a_support_connection}","$page?support-popup=yes&function=$function");
}
function uid_row(){
    $uid=$_GET["uid-row"];
    $uidmd5=md5($uid);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM openvpn_clients WHERE uid='$uid'");
    $fixip=base64_encode(td_fixip($ligne));
    $ipaddr=base64_encode($ligne["vpnip"]);
    $status=base64_encode($tpl->_ENGINE_parse_body(td_status($ligne)));
    $auth=base64_encode($tpl->_ENGINE_parse_body(td_auth($ligne)));
    $profile=base64_encode($tpl->_ENGINE_parse_body(td_profile($ligne)));



    $f[]="if(document.getElementById('auth-$uidmd5')){";
    $f[]="\tdocument.getElementById('auth-$uidmd5').innerHTML=base64_decode('$auth');";
    $f[]="}";
    $f[]="if(document.getElementById('fixip-$uidmd5')){";
    $f[]="\tdocument.getElementById('fixip-$uidmd5').innerHTML=base64_decode('$fixip');";
    $f[]="}";
    $f[]="if(document.getElementById('status-$uidmd5')){";
    $f[]="\tdocument.getElementById('status-$uidmd5').innerHTML=base64_decode('$status');";
    $f[]="}";
    $f[]="if(document.getElementById('ipaddr-$uidmd5')){";
    $f[]="\tdocument.getElementById('ipaddr-$uidmd5').innerHTML=base64_decode('$ipaddr');";
    $f[]="}";
    $f[]="if(document.getElementById('profile-$uidmd5')){";
    $f[]="\tdocument.getElementById('profile-$uidmd5').innerHTML=base64_decode('$profile');";
    $f[]="}";

    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}


function support_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $BootstrapDialog="BootstrapDialog1.close();";
    $Life[1800]="30 {minutes}";
    $Life[3600]="1 {hour}";
    $Life[7200]="2 {hours}";
    $Life[21600]="6 {hours}";
    $Life[43200]="12 {hours}";
    $Life[86400]="1 {day}";
    $Life[172800]="2 {days}";
    $Life[259200]="3 {days}";
    $Life[604800]="1 {week}";
    $Life[1814400]="3 {week}";
    $Life[2592000]="1 {month}";
    $Life[5184000]="2 {months}";

    $form[]=$tpl->field_hidden("ConnectSupport",1);
    $form[]=$tpl->field_text("connection_name", "{organizationName}", "",true);
    $form[]=$tpl->field_array_hash($Life,"lifetime", "nonull:{client_lifetime}", 21600);

    $html=$tpl->form_outside(null,$form,"{create_a_support_connection_explain}",
        "{generate_parameters}","$function();$BootstrapDialog","AsVPNManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function support_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $uid=FixConnectionName($_POST["connection_name"]);
    $add["uid"]=$uid;
    $add["ComputerOS"]="linux";
    $add["lifetime"]=time()+intval($_POST["lifetime"]);
    $add["support"]=1;
    $add["username"]=generateRandomSerial();
    $add["password"]=generateRandomSerial();

    foreach ($add as $key=>$val){
        $f_add[]="`$key`";
        $f_vals[]="'$val'";

    }

    $sql=sprintf("INSERT OR IGNORE INTO openvpn_clients (%s) VALUES (%s)",@implode(",",$f_add),@implode(",",$f_vals));
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error("SQL:$q->mysql_error<br>$sql");
        return false;
    }

    $sock=new sockets();
    $data=$sock->REST_API("/openvpn/client/build/$uid");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error("API:$json->Error");
        return false;
    }
    return admin_tracks("Create a new OpenVPN Support client connection for $uid");

}
function generateRandomSerial($numGroups = 4):string {
    $serial = '';
    for ($i = 0; $i < $numGroups; $i++) {
        $group = '';
        for ($j = 0; $j < 4; $j++) {
            if (rand(0, 1)) {
                $group .= rand(0, 9);
            } else {
                $group .= chr(rand(65, 90));
            }
        }
        $serial .= $group;
        if ($i < $numGroups - 1) {
            $serial .= '-';
        }
    }

    return $serial;
}



function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");

    $html=$tpl->page_header("{members} v$OpenVPNVersion",
        "fas fa-users-crown","{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}","$page?main-start=yes",
        "openvpn-configurators","progress-vpnclientsscr-restart",false,"table-loader");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rebuild(){
    $tpl=new template_admin();
    $uid=$_GET["rebuild"];
    $function=$_GET["function"];

    $sock=new sockets();
    $data=$sock->REST_API("/openvpn/client/build/$uid");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$function()";
    return admin_tracks("Rebuild a new OpenVPN client connection $uid");
}



function rule_popup():bool{
	$tpl=new template_admin();
    $function=$_GET["function"];
	$os=GetOS();
	$os[null]="{select}";
	$BootstrapDialog="BootstrapDialog1.close();";



	$form[]=$tpl->field_text("connection_name", "{connection_name}", $_GET["uid"]);
	$form[]=$tpl->field_array_hash($os,"ComputerOS","nonull:{ComputerOS}",null,false);
	$html=$tpl->form_outside("{connection}",$form,"{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}",
	"{generate_parameters}","$function();$BootstrapDialog","AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function routes_add(){
	$vpn=new openvpn();
	$vpn->routes[$_POST["ROUTE_FROM"]]=$_POST["ROUTE_MASK"];
	$vpn->Save();
}
function routes_delete(){
	$vpn=new openvpn();
	unset($vpn->routes[$_POST["DELETE_ROUTE_FROM"]]);
	$vpn->Save();

}

function GetOS():array{
	$os["windowsXP"]="Windows XP";
	$os["windows2003"]="Windows 2003/7";
	$os["linux"]="Linux/Synology";
    $os["artica"]="Artica Client";
	$os["mac"]="OS X 10.4, 10.5, 10.6";
	$os["Windows7"]="Windows 7,8,10";
	$os["Android_IOS"]="Android / IOS";
	return $os;
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
	$delete=$tpl->javascript_parse_text("{delete}");
	$ComputerOS=$tpl->javascript_parse_text("{ComputerOS}");
	$userid=$tpl->javascript_parse_text("{connections}");
	$download=$tpl->javascript_parse_text("{download2}");
	$run=$tpl->javascript_parse_text("{rebuild}");
	$software=$tpl->javascript_parse_text("{software}");
    $t=time();
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$userid}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>TTL</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$ComputerOS</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$software</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$download</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' class='center'>{$run}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' class='center'>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT *  FROM openvpn_clients ORDER BY uid,ComputerOS";

    $search=$_GET["search"];
    if(strlen($search)>2){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT *  FROM openvpn_clients WHERE ( (uid LIKE '$search') OR ( ComputerOS LIKE '$search') ) ORDER BY uid,ComputerOS";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error_html(true,$sql);
	}
	$ois[""]="fas fa-question";
	$ois["windowsXP"]="fab fa-windows";
	$ois["windows2003"]="fab fa-windows";
	$ois["linux"]="fab fa-linux";
    $ois["artica"]="fab fa-linux";
	$ois["mac"]="fab fa-apple";
	$ois["Windows7"]="fab fa-windows";
	$ois["Android_IOS"]="fas fa-mobile-alt";
    $downico="<i class='fa fa-download'></i>&nbsp;";
	$ClientConnect="$downico<a href='http://articatech.net/download/openvpn-connect-3.4.3.3337_signed.msi'>";
	
	$oisLink["windowsXP"]="$downico<a href='http://www.articatech.net/download/openvpn-install-2.3.11-I001-i686.exe'>32Bits</a>&nbsp;|&nbsp;<a href='http://www.articatech.net/download/openvpn-install-2.3.11-I001-x86_64.exe'>64Bits</a>";
	$oisLink["windows2003"]="$downico<a href='http://www.articatech.net/download/openvpn-install-2.3.11-I001-i686.exe'>32Bits</a>&nbsp;|&nbsp;<a href='http://www.articatech.net/download/openvpn-install-2.3.11-I001-x86_64.exe'>64Bits</a>";
	$oisLink["Windows7"]="$downico<a href='http://articatech.net/download/openvpn-install-2.3.13-I601-x86_64.exe'>Win7 64Bits</a><br>{$ClientConnect}Win10/11 64bits</a>";
	$oisLink["mac"]="<a href='http://articatech.net/download/Tunnelblick_3.7.0_build_4790.dmg'>OS X 10.4+</a>";
	$oisLink["linux"]="In System";
    $oisLink["artica"]="In System";
	$os=GetOS();

    $width1="style='vertical-align:middle;width:1%' class='center' nowrap";
    $widthL="style='vertical-align:middle;width:1%' class='left' nowrap";
    $TRCLASS=null;
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
		$zipsize=intval($ligne["zipsize"]);
		$userid=$ligne["uid"];
        $useridEnc=urlencode($userid);
        $rebuildjs="Loadjs('$page?rebuild=$useridEnc&function=$function');";
		$userid_encoded=urlencode($userid);
        $support=intval($ligne["support"]);
        $ipaddr=$ligne["vpnip"];
        $Ico=ico_user;
        $TextColor=null;
        $jsDownload="document.location.href='$page?download=yes&uid=$userid&t=$t'";

        $ico_down=$tpl->icon_download($jsDownload,"AsVPNManager");
        $IcoOs=$ois[$ligne["ComputerOS"]];
        $fixip=td_fixip($ligne);
        $OsText=$os[$ligne["ComputerOS"]];
        $OsLink=$oisLink[$ligne["ComputerOS"]];
        $profile=$ligne["profile"];
        $zAuth=array();
        $profile_text=td_profile($ligne);

        $userid_label=sprintf("%s<span id='fixip-$userid'>%s</span>",$userid,$fixip);

        $useridjs=$tpl->td_href($userid_label,null,"Loadjs('fw.openvpn.clients.config.php?uid=$userid_encoded&function=$function');");


        $run=$tpl->icon_run($rebuildjs,"AsVPNManager");

        if(IsExpired($ligne)){
            $status="<span class='label label-danger'>{expired}</span>";
            $TextColor=" text-danger";
            $run="&nbsp;";
            $ico_down="&nbsp;";
            $useridjs=$userid;
        }
        if($zipsize==0){
            $ico_down="&nbsp;";
        }

		$numenc=urlencode(base64_encode($ligne["uid"]));
        $DeleteIco=$tpl->icon_delete("Loadjs('$page?delete-js=$numenc&id=$md')","AsVPNManager");
        $LifeTime=td_lifetime($ligne);

        if($support==1){
            $Ico=ico_support;
            $AuthTxt=null;
        }
        $status=td_status($ligne);
        $AuthTxt=td_auth($ligne);
        $useridmd5=md5($userid);
        $Span="<span class='$TextColor'>";
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $width1><span id='status-$useridmd5'>$status</span></td>";
		$html[]="<td><i class='$Ico$TextColor'></i>&nbsp;$Span$useridjs</a><span id='auth-$useridmd5'>$AuthTxt</span><span id='profile-$useridmd5'>$profile_text</span></td>";
        $html[]="<td $widthL><span id='ipaddr-$useridmd5'>$ipaddr</span></td>";
        $html[]="<td $widthL>$LifeTime</td>";
		$html[]="<td $widthL><i class='fa $IcoOs$TextColor'></i>&nbsp;$Span$OsText</i></td>";
		$html[]="<td $widthL>$Span$OsLink</a></span></td>";
		$html[]="<td $width1>$ico_down</td>";
		$html[]="<td $width1>$run</td>";
		$html[]="<td $width1>$DeleteIco</td>";
        $html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='9'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";



    $topbuttons[] = array("Loadjs('$page?ruleid-js=&function=$function');",ico_plus,"{create_a_connection}");
    $topbuttons[] = array("Loadjs('$page?support-js=&function=$function');",ico_support,"{create_a_support_connection}");
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $TINY_ARRAY["TITLE"]="{members} v$OpenVPNVersion";
    $TINY_ARRAY["ICO"]="fas fa-users-crown";
    $TINY_ARRAY["EXPL"]="{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true } } ); });
	$jstiny
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function td_auth($ligne):string{
    $LocalAuth=intval($ligne["LocalAuth"]);
    if($LocalAuth==1){
        $zAuth[]="{UseLocalDatabase}";
    }
    if(count($zAuth)==0){
        $profile=$ligne["profile"];
        if($profile>0) {
            $q = new lib_sqlite("/home/artica/SQLITE/openvpn.db");
            $results = $q->QUERY_SQL("SELECT rulename FROM auths WHERE profileid=$profile ORDER BY ID LIMIT 250");
            $a=array();
            foreach ($results as $index => $ligne) {
                $rulename = $ligne["rulename"];
                $a[] = "$rulename";
            }
        }
        if(count($a)>0){
            $AuthTxt=sprintf("<br><i>{authentication}: %s</i>",@implode(",",$a));
            return $AuthTxt;
        }


        $AuthTxt="<br><i><span class='text-danger'>{no_auth_defined}</span></i>";
    }else{
        $AuthTxt=sprintf("<br><i>{authentication}: %s</i>",@implode(",",$zAuth));
    }
    return $AuthTxt;

}
function td_status($ligne):string{

    if(IsExpired($ligne)) {
        return "<span class='label label-danger'>{expired}</span>";
    }


    if($ligne["status"]==0){
        return "<span class='label label-default'>{disconnected}</span>";
    }
    if($ligne["status"]==1){
        return "<span class='label label-primary'>{connected}</span>";
    }

    return "<span class='label label-default'>{unknown}</span>";
}

function td_profile($ligne):string{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $profile=$ligne["profile"];
    $userid=$ligne["uid"];
    if($profile==0){
        return sprintf("<br><i>{profile}: %s</i>","{none}");
    }

    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM profiles WHERE ID=$profile");
    $prfilename=$ligne["rulename"];

    $prfilename=$tpl->td_href($prfilename,"","Loadjs('fw.openvpn.profiles.php?ruleid-js=$profile&uid=$userid')","AsVPNManager");

    return sprintf("<br><i>{profile}: %s</i>",$prfilename);


}

function td_fixip($ligne){
    $fixip=$ligne["fixip"];

    if($fixip=="0.0.0.0"){
        return "";
    }
    if(strlen($fixip)<3){
        return "";
    }

    $ipclass=new IP();
    if(!$ipclass->isValid($fixip)){
        return "";
    }

    return " ($fixip)";
}


function IsExpired($ligne){
    $lifetime=$ligne["lifetime"];
    if($lifetime==0){return false;}
    if($lifetime>time()){return false;}
    return true;
}
function td_lifetime($ligne){

    $Clock=ico_clock;
    $lifetime=$ligne["lifetime"];
    $lifetime_text="{unlimited}";

    if($lifetime==0){
        return "<span class='$Clock'></span>&nbsp;$lifetime_text";
    }
    if(time()>$lifetime) {
        return "<span class='$Clock text-danger'></span>&nbsp;<span class='text-danger'>{expired}</span>";

    }

    $lifetime_text=distanceOfTimeInWords(time(),$lifetime);
    return "<span class='$Clock'></span>&nbsp;$lifetime_text</span>";


}

function delete_script()
{
    $uid = base64_decode($_POST["delete-script"]);
    $sql = "DELETE FROM vpnclient WHERE connexion_name='$uid'";
    $q = new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error;
        return;
    }
    $sql = "DELETE FROM openvpn_clients WHERE uid='$uid'";
    $q->QUERY_SQL($sql);
    $sql = "DELETE FROM memberlinks WHERE connection='$uid'";
    $q->QUERY_SQL($sql);
}


function FixConnectionName($connection_name):string{
    if($connection_name==null){$connection_name=time();}
    $connection_name=replace_accents($connection_name);
    $connection_name=str_replace("/", "-", $connection_name);
    $connection_name=str_replace('\\', "-", $connection_name);
    $connection_name=str_replace("&","",$connection_name);
    $connection_name=str_replace(",","",$connection_name);
    $connection_name=str_replace(";","",$connection_name);
    $connection_name=str_replace("%","",$connection_name);
    $connection_name=str_replace("*","",$connection_name);
    $connection_name=str_replace("ø","",$connection_name);
    $connection_name=str_replace("$","",$connection_name);
    $connection_name=str_replace("/","",$connection_name);
    $connection_name=str_replace("\\","",$connection_name);
    $connection_name=str_replace("?","",$connection_name);
    $connection_name=str_replace("µ","",$connection_name);
    $connection_name=str_replace("£","",$connection_name);
    $connection_name=str_replace(")","",$connection_name);
    $connection_name=str_replace("(","",$connection_name);
    $connection_name=str_replace("[","",$connection_name);
    $connection_name=str_replace("]","",$connection_name);
    $connection_name=str_replace("#","",$connection_name);
    $connection_name=str_replace("'","",$connection_name);
    $connection_name=str_replace("\"","",$connection_name);
    $connection_name=str_replace("+","_",$connection_name);
    $connection_name=str_replace(" ",".",$connection_name);
    return $connection_name;

}

function buildconfig():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $connection_name=FixConnectionName($_POST["connection_name"]);

	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

    $sql=sprintf("INSERT OR IGNORE INTO `openvpn_clients` (uid,ComputerOS) VALUES ('%s','%s')",$connection_name,$_POST["ComputerOS"]);

    if($_POST["ComputerOS"]=="artica"){
        $username=generateRandomSerial();
        $password=generateRandomSerial();
        $sql=sprintf("INSERT OR IGNORE INTO `openvpn_clients` (uid,ComputerOS,username,password,LocalAuth) VALUES ('%s','%s','%s','%s',1)",$connection_name,"artica",$username,$password);
    }
    $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}

	$sock=new sockets();
    $data=$sock->REST_API("/openvpn/client/build/$connection_name");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    return admin_tracks("Created a new OpenVPN client connection $connection_name");

}
function download(){
	$uid=$_GET["uid"];
	header("Content-Type:  application/zip");
	header("Content-Disposition: attachment; filename=$uid.zip");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

	
	//$ligne=$q->mysqli_fetch_array("SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid='$uid'");
	//$ligne=$q->QUERY_SQL("SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid='$uid'");
	//$ligne=mysqli_fetch_array($res);
	$ligne=$q->mysqli_fetch_array("SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid='$uid'");
@file_put_contents("/tmp/test.zip",base64_decode($ligne["zipcontent"]));
shell_exec("/usr/bin/unzip /tmp/test.zip");


	if(!$q->ok){exit;}
	header("Content-Length: ".$ligne["zipsize"]);
	ob_clean();
	flush();
	echo base64_decode($ligne["zipcontent"]);
}