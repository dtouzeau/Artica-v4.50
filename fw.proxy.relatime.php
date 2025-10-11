<?php
// Patch 125
include_once(dirname(__FILE__)."/ressources/externals/GeoIP2/vendor/autoload.php");
use GeoIp2\Database\Reader;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
include_once(dirname(__FILE__)."/ressources/RealTimeParse.php");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
$EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
$EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["ua-js"])){ua_js();exit;}
if(isset($_GET["ua-popup"])){ua_popup();exit;}
if(isset($_GET["zoom"])){zoom();exit;}
if(isset($_GET["page-requests"])){page();exit;}
if(isset($_GET["ip-js"])){ip_js();exit;}
if(isset($_GET["ip-tabs"])){ip_tabs();exit;}
if(isset($_GET["ip-popup"])){ip_popup();exit;}
if(isset($_GET["ip-popup-start"])){ip_popup_start();exit;}
if(isset($_GET["ip-view"])){ip_save();exit;}
if(isset($_GET["main-page"])){page();exit;}
if(isset($_GET["search-file-js"])){search_file_js();exit;}
if(isset($_GET["search-file-popup"])){search_file_popup();exit;}
if(isset($_GET["opts"])){search_opts_js();exit;}
if(isset($_GET["search-opts-popup"])){search_opts_popup();exit;}
if(isset($_GET["search-opts-reset"])){search_opts_reset();exit;}



if(isset($_POST["remote_addr"])){search_opts_save();exit;}
if($EnableUfdbGuard==1){if($users->AsProxyMonitor){tabs();exit;}}
if($users->AsProxyMonitor){if($EnableDNSDist==1){tabs();exit;}}
if($users->AsWebMaster) {if($EnableNginx==1){tabs();exit;}}
if($users->AsProxyMonitor) {
    page();
}

function search_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["search-file-js"]);
    return $tpl->js_dialog2("{search} N.$ID","$page?search-file-popup=$ID",2048);
}
function search_opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{options}","$page?search-opts-popup=yes&function=$function");
}
function search_opts_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];

    $form[]=$tpl->field_ipaddr("remote_addr","{src}",$_SESSION["SQUIDSEARCH"]["remote_addr"]);
    $form[]=$tpl->field_date("DateFrom","{from_date}",$_SESSION["SQUIDSEARCH"]["DateFrom"]);
    $form[]=$tpl->field_clock("TimeFrom","{from_time}",$_SESSION["SQUIDSEARCH"]["TimeFrom"]);
    $form[]=$tpl->field_clock("TimeTo","{to_time}",$_SESSION["SQUIDSEARCH"]["TimeTo"]);
    $js="dialogInstance4.close();$function()";
    $tpl->form_add_button("{reset}","Loadjs('$page?search-opts-reset=yes&function=$function')");
    echo $tpl->form_outside("{search}",$form,null,"{save}",$js);
    return true;
}
function search_opts_reset():bool{
    $function=$_GET["function"];
    unset($_SESSION["SQUIDSEARCH"]);
    header("content-type: application/x-javascript");
    echo "dialogInstance4.close();$function()";
    return true;
}
function search_opts_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["SQUIDSEARCH"]=$_POST;
    return true;
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $SHOW_REALTIME_PROXY=true;
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
    $HaClusterRemoveRealtimeLogs=0;

    if($HaClusterClient==1) {
        $HaClusterGBConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        $HaClusterRemoveRealtimeLogs = intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);
        if($HaClusterRemoveRealtimeLogs==1){$SHOW_REALTIME_PROXY=false;}
    }
    if($SquidNoAccessLogs==1){$SHOW_REALTIME_PROXY=false;}
    if($LogsWarninStop==1){$SHOW_REALTIME_PROXY=false;}
    if($SQUIDEnable==0){$SHOW_REALTIME_PROXY=false;}


    if($SHOW_REALTIME_PROXY) {
        $array["{requests}:{APP_PROXY}"] = "$page?page-requests=yes&notitle=yes";
    }
    if($EnableUfdbGuard==1) {
        $array["{requests}:{web_filtering}"] = "fw.ufdb.logs.php";
    }

    if($EnableNginx==1){
        $array["{requests}:{APP_NGINX}"] = "fw.nginx.requests.php?form=yes";
    }

    if($EnableDNSDist==1) {
        $array["{DNS_QUERIES}"] = "fw.dnsdist.logs.php";
    }
    if($UnboundEnabled==1){
        $array["{DNS_QUERIES}"] = "fw.dns.unbound.queries.php?notitle=yes";
    }

    if($SQUIDEnable==1) {
        $array["{proxy_events}"] = "fw.proxy.daemon.php";
    }
    $array["{artica_events}"]="fw.system.watchdog.php";

    echo "<div class='row white-bg dashboard-header' style='margin-top:0px'>";
    echo $tpl->tabs_default($array);
    echo "</div>";
}

function ip_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ip=$_GET["ip-js"];
    $ipencode=urlencode($ip);

    $title=$tpl->_ENGINE_parse_body("{tcp_address}::$ip::ZOOM");
    $tpl->js_dialog($title, "$page?ip-tabs=$ipencode");
}

function ip_popup_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ip=$_GET["ip-popup-start"];
    $ipencode=urlencode($ip);
    $md5=md5($ip);
    echo "<div id='ip-$md5'></div><script>LoadAjax('ip-$md5','$page?ip-popup=$ipencode');</script>";

}

function ip_save(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ip=$_GET["ip-view"];
    $md5=md5($ip);
    $ipencode=urlencode($ip);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM follow_ip WHERE ipaddr='$ip'");
    $ID=intval($ligne["ID"]);

    if($ID==0){
        $q->QUERY_SQL("INSERT INTO follow_ip (ipaddr) VALUES ('$ip')");
        if(!$q->ok){
            echo $tpl->js_mysql_alert($q->mysql_error);
            return;
        }
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM follow_ip WHERE ipaddr='$ip'");
        $ID=intval($ligne["ID"]);
        if($ID==0){
            echo $tpl->js_mysql_alert("Insertion failed");
            return;
        }

    }else{
        $q->QUERY_SQL("DELETE FROM follow_ip WHERE ID=$ID");
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="squid2.php?global-logging-center=yes";
    $ARRAY["TITLE"]="{reconfigure}";
    $ARRAY["AFTER"]="LoadAjax('ip-$md5','$page?ip-popup=$ipencode');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=ip-zoom-progress')";

    header("content-type: application/x-javascript");
    echo "$jsrestart";

}

function ip_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ip=$_GET["ip-popup"];
    $ipencode=urlencode($ip);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="CREATE TABLE IF NOT EXISTS `follow_ip` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ipaddr` TEXT UNIQUE NOT NULL)";
    $q->QUERY_SQL($sql);


    $ligne=$q->mysqli_fetch_array("SELECT ID FROM follow_ip WHERE ipaddr='$ip'");
    $ID=intval($ligne["ID"]);

    $html[]="<H1>$ip</H1>";
    $html[]="<div id='ip-zoom-progress'></div>";
    $html[]="<table class='table table-hover'><thead></thead><tbody>";
    $html[]="<tr>";
    if($ID==0) {
        $html[] = "<td><strong>{follow_this_address}</strong><br><small>{acl_follow_this_address}</small></td>";
        $html[] = "<td width=1% nowrap>" . $tpl->button_autnonome("{enable_filter}", "Loadjs('$page?ip-view=$ipencode')",
                "fas fa-eye", "AsProxyMonitor", 180, "btn-info") . "</td>";

    }else{
        $html[] = "<td><strong>{unfollow_this_address}</strong><br><small>{acl_follow_this_address}</small></td>";
        $html[] = "<td width=1% nowrap>" . $tpl->button_autnonome("{disable}", "Loadjs('$page?ip-view=$ipencode')",
                "fas fa-eye", "AsProxyMonitor", 180, "btn-danger") . "</td>";


    }

    $html[]="</tr>";
    $html[]="</tbody></table>";

    echo $tpl->_ENGINE_parse_body($html);

}

function ip_tabs():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ip=$_GET["ip-tabs"];
    $ipencode=urlencode($ip);
    $array[$ip]="$page?ip-popup-start=$ipencode";
    echo $tpl->tabs_default($array);
    return true;
}

function zoom_js(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=urlencode($_GET["data"]);
    $title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
    $tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}

function ua_js(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    $title=$tpl->_ENGINE_parse_body("{browser}");
    $tpl->js_dialog1($title, "$page?ua-popup={$_GET["ua-js"]}",550);

}

function ua_popup(){
    $ua=base64_decode($_GET["ua-popup"]);
    $html="<textarea style='width: 530px; height: 130px;font-size:16px'>$ua</textarea>";
    echo $html;
}

function zoom(){
    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=new template_admin();
    $data=unserializeb64($_GET["data"]);
    $html[]="<div class=ibox-content>";
    $html[]="<table class='table table table-bordered'>";


    VERBOSE("URL = ".$data["URL"],__LINE__);

    if(isset($data["URL"])){
        $data["URL"]=trim($data["URL"]);
        $parse=parse_url($data["URL"]);
        $jsurl="s_PopUp('{$data["URL"]}',1024,768,'');";

            if (strlen($data["URL"]) > 80) {
                $data["URL"] = $tpl->td_href(substr($data["URL"], 0, 77) . "...", $data["URL"], $jsurl);
            }

        $hostname=$parse["host"];
        if(preg_match("#^(.+?):([0-9]+)#", $hostname,$re)){$hostname=$re[1];}


        $ip=new IP();
        if($ip->isValid($hostname)){
            $hostname_r=explode(".",$hostname);
            if(count($hostname_r)>3){
                $cdir=$hostname_r[0].".".$hostname_r[1].".".$hostname_r[2];
                $data["{analyze} db-ip.com"]=$GLOBALS["TPLZ"]->td_href("$cdir.0/24","db-ip.com ($cdir.0/24)","s_PopUp('https://db-ip.com/all/$cdir',1024,1024);");
            }

        }

        if(isset($data["error"])){
            $data["error"]="<span class='text-danger font-bold'>{$data["error"]}</span>";
        }

        if(isset($data["FAMILYSITE"])){
            $data["{familysite}"]=$data["FAMILYSITE"];
            unset($data["FAMILYSITE"]);

        }

    }

    if(isset($data["acl_peer"])){
        $data["{rule} {parent}"]=$tpl->td_href(peer_rule_from_id($data["acl_peer"]),null,
        "Loadjs('fw.proxy.parents.php?ruleid-js={$data["acl_peer"]}',true);");
        unset($data["acl_peer"]);
    }

    if(isset($data["accessrule"])){
        $MAIN["ntlm_white_dstdomain"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthTo-js=yes')";
        $MAIN["ntlm_white_mac"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthFrom-js=yes')";
        $MAIN["ntlm_white_netdst"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthTo-js=yes')";
        $MAIN["ntlm_white_netsrc"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthFrom-js=yes')";

        $MAIN_TEXT["ntlm_white_dstdomain"]="NTLM {whitelist}";
        $MAIN_TEXT["ntlm_white_mac"]="NTLM {whitelist}";
        $MAIN_TEXT["ntlm_white_netdst"]="NTLM {whitelist}";
        $MAIN_TEXT["ntlm_white_netsrc"]="NTLM {whitelist}";

        $rule=trim($data["accessrule"]);
        if(isset($MAIN[$rule])){
            $data["{rule} ACL"]=$tpl->td_href($MAIN_TEXT[$rule],null,$MAIN[$rule]);
        }else{
            $data["{rule} ACL"]="{rule}:<strong>$rule</strong>";
        }
        unset($data["accessrule"]);

    }
    if(isset($data["ptime"])){
        $data["{duration}: {plugins}"]=$data["ptime"]." {seconds}";
        unset($data["ptime"]);
    }
    if(isset($data["ecapav"])){
        $inf=intval(trim($data["ecapav"]));
        $mm[0]="<span class='label label-default'>{inactive2}</span>";
        $mm[1]="<span class='label label-primary'>{active2}</span>";
        $data["Antivirus: eCAP"]=$mm[$inf];
        unset($data["ecapav"]);
    }


    $data=WebFilterPolicy($data);

    if(isset($data["bumprule"])){
        $sslrulename=get_sslrulename($data["bumprule"]);
        if($sslrulename<>null){
            $data["{activate_ssl_on_http_port}"]=$tpl->td_href($sslrulename,$sslrulename,"Loadjs('fw.proxy.ssl.rules.php?rule-id-js={$data["bumprule"]}')");
        }
        unset($data["bumprule"]);
    }

    if(isset($data["splicerule"])){
        $sslrulename=get_sslrulename($data["splicerule"]);
        if($sslrulename<>null){
            $data["{do_not_encrypt_websites}"]=$tpl->td_href($sslrulename,$sslrulename,"Loadjs('fw.proxy.ssl.rules.php?rule-id-js={$data["bumprule"]}')");
        }
        unset($data["splicerule"]);
    }

    if(isset($data["whitelistssl"])){
        $data["{ssl_whitelist}"]="{global}";
        unset($data["whitelistssl"]);
    }




    if(isset($data["DESTINATION"])){
        $dest=trim($data["DESTINATION"]);
        if(preg_match("#\/([0-9\.]+)(:|$)#i", $dest,$re)){
            $hostname_r=explode(".",$re[1]);
            if(count($hostname_r)>3){
                $cdir=$hostname_r[0].".".$hostname_r[1].".".$hostname_r[2];
                $data["{analyze} db-ip.com"]=$GLOBALS["TPLZ"]->td_href("$cdir.0/24","db-ip.com ($cdir.0/24)","s_PopUp('https://db-ip.com/all/$cdir',1024,1024);");
            }
        }
        $data["{destination}"]=$data["DESTINATION"];
        unset($data["DESTINATION"]);
    }

    if(isset($data["DURATION"])){
        $data["{duration}"]="{$data["DURATION"]}ms";
        unset($data["DURATION"]);
    }


    if(isset($data["MAC"])){
        $members[]=$data["MAC"];
        unset($data["MAC"]);
    }
    if(isset($data["CLIENT_IP"])){
        $members[]=$data["CLIENT_IP"];
        unset($data["CLIENT_IP"]);
    }
    if(isset($data["user"])){
        $members[]=$data["user"];
        unset($data["user"]);
    }

    $data["{member}"]=@implode(", ", $members);


    if(isset($data["category"])){
        $data["{category}"]=categoryCodeTocatz($data["category"]);
        unset($data["category"]);
        unset($data["CATEGORY"]);
    }

    if(isset($data["webfiltering"])){
        $rr=explode(",",$data["webfiltering"]);
        $tt[]="<li>{$rr[0]}</li>";
        if(intval($rr[1])==0){$tt[]="<li>{rule}: {default}</li>";}else{$tt[]="<li>{rule}: {$rr[1]}</li>";}
        $tt[]="<li>{category}: ".categoryCodeTocatz($rr[2])." ({$rr[2]})";

        $data["webfiltering"]="<div style='padding-left:10px'>".@implode("\n", $tt)."</div>";
    }

    if(isset($data["bandwidth"])){
        $squid_http_bandwidth_acls_id=trim($data["bandwidth"]);
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_http_bandwidth_acls WHERE ID='$squid_http_bandwidth_acls_id'");
        $rulename=$ligne["rulename"];
        $bdwjs="Loadjs('fw.proxy.bandwidth.php?rule-id-js=$squid_http_bandwidth_acls_id')";
        $data["bandwidth"]="<i class='text-warning fas fa-sort-amount-down'></i>&nbsp;".$GLOBALS["TPLZ"]->td_href($rulename,"{view}",$bdwjs);

    }

    if(isset($data["authmec"])){
        $authmec=intval(trim($data["authmec"]));
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_auth_schemes_acls WHERE ID='$authmec'");
        $bdwjs="Loadjs('fw.proxy.auth_schemes.php?rule-id-js=$authmec')";
        unset($data["authmec"]);
        $rulename=$ligne["rulename"];
        $data["{auth_mec_pref}"]="<i class='fas fa-user'></i>&nbsp;".$GLOBALS["TPLZ"]->td_href($rulename,"{view}",$bdwjs);
    }


    if(isset($data["ufdbunblock"])){
        $data["{webfunlocked}"]="{yes}";
        unset($data["ufdbunblock"]);

    }
    $tb=explode("/",$data["PROXY_CODE"]);
    $PROXY_ERROR_CODE=intval($tb[1]);
    if($PROXY_ERROR_CODE==426){
        echo $tpl->div_warning($data["PROXY_CODE"]."||{HTTP_CODE_426}");
    }


    foreach ($data as $key=>$val){
        if($key=="-"){continue;}
        if($key=="PROXY_CODE"){$key="{http_status_code}";}
        if($key=="PROTO"){$key="{protocol}";}
        if($key=="category-name"){$key="{category_name}";}
        if($key=="SIZE"){$key="{size}";}

        $html[]="<tr>
		<td class=text-capitalize style='text-align: right'>$key:</td>
		<td><strong>$val</strong></td>
		</tr>";

    }




    echo $GLOBALS["TPLZ"]->_ENGINE_parse_body(@implode("", $html)."</table></div>");

}
function WebFilterPolicy($data):array{

    if(!isset($data["webfilterpolicy"])){return $data;}
    $data_rule=trim($data["webfilterpolicy"]);
    $tpl=new template_admin();

        if(is_numeric($data_rule)){
            if($data_rule>0){
                $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
                $js="Loadjs('fw.proxy.urlrewrite.php?rule-id-js=$data_rule')";
                $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_url_rewrite_acls WHERE ID='$data_rule'");
                $data["{web_filter_policies}"]=$tpl->td_href($ligne["rulename"],null,$js);
                unset($data["webfilterpolicy"]);
                return $data;
            }
            $data["{web_filter_policies}"]="{global_whitelist}";
            unset($data["webfilterpolicy"]);
            return $data;

        }



        if($data_rule=="connect"){
            $js="Loadjs('fw.proxy.errors.page.php?SquidGuardDenyConnect-js=yes')";
            $data["{web_filter_policies}"]=$tpl->td_href("{ssl_decrypt_compatibility}",null,$js);
            unset($data["webfilterpolicy"]);
            return $data;

        }
        if($data_rule=="ftp"){
            $data["{web_filter_policies}"]="FTP Protocol";
            unset($data["webfilterpolicy"]);
            return $data;
        }

    return $data;
}
function search_file_popup(){
    $ID=$_GET["search-file-popup"];
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t=time();

    $html[]="<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PROXY_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-firehol-restart'></div>
		<div  class='ibox-content'>
			<div id='table-loader-$t'></div>
		</div>
	</div>
	<script>
		function Search$t(e){
			if(checkEnter(e) ){
			    ss$t();
			}
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-$t','$page?search='+ss+'&search-id=$ID');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}
function page(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $addPLUS    = null;
    if(!isset($_SESSION["PROXY_SEARCH"])){$_SESSION["PROXY_SEARCH"]="";}
    if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
    if($_SESSION["PROXY_SEARCH"]==null){$_SESSION["PROXY_SEARCH"]="";}

    $isSquid5                   = false;
    $SquidVersion               = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
    $MAIN_ACCESS_RULES          = array();
    if(preg_match("#^(5|6|7)\.#",$SquidVersion)){$isSquid5=true;}

    if($isSquid5) {
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $webfilters_sqacls = $q->QUERY_SQL("SELECT aclname,ID FROM webfilters_sqacls");
        foreach ($webfilters_sqacls as $index => $ligne) {
            $ID = $ligne["ID"];
            $aclname = $ligne["aclname"];
            $_SESSION["MAIN_ACCESS_RULES"][$ID] = $aclname;
        }
    }



    if(!isset($_GET["notitle"])) {

        $html=$tpl->page_header("{realtime_requests}",
            "fas fa-eye","{realtime_requests_explain}","$page?notitle=yes$addPLUS","access.log","progress-firehol-restart",
            false,"table-loader");

    }else {
        VERBOSE("NOTITLE=YES",__LINE__);
        $options["WRENCH"]="Loadjs('fw.proxy.relatime.php?opts=yes&function=%s')";
        $html[]="<div style='margin-top:10px'>";
        $html[]=$tpl->search_block($page,null,null,null,"$addPLUS&notitle=yes",$options);
        $html[]="</div>";
    }

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}
function default_access_rules($defaultrule){
    $tpl=new template_admin();
    $defaultrule=trim(strtolower($defaultrule));
    $MAIN=array();
    VERBOSE("default_access_rules: $defaultrule",__LINE__);
    $MAIN["ntlm_white_dstdomain"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthTo-js=yes')";
    $MAIN["ntlm_white_mac"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthFrom-js=yes')";
    $MAIN["ntlm_white_netdst"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthTo-js=yes')";
    $MAIN["ntlm_white_netsrc"]="Loadjs('fw.proxy.ad.white.php?SquidWhitelistAuthFrom-js=yes')";
    $MAIN["office_network"]="";
    $MAIN["ldap_auth"]="";
    $MAIN["authenticate"]="";
    $MAIN["bypassurl"]="Loadjs('fw.proxy.whitelist.php?byjs=yes');";

    $MAIN_TEXT["ntlm_white_dstdomain"]="NTLM {whitelist}";
    $MAIN_TEXT["ntlm_white_mac"]="NTLM {whitelist}";
    $MAIN_TEXT["ntlm_white_netdst"]="NTLM {whitelist}";
    $MAIN_TEXT["ntlm_white_netsrc"]="NTLM {whitelist}";

    $MAIN_TEXT["ldap_auth"]="NTLM {whitelist}";
    $MAIN_TEXT["office_network"]="{local network}";
    $MAIN_TEXT["authenticate"]="NTLM";
    $MAIN_TEXT["bypassurl"]="{global_whitelists}";

    if(isset($MAIN[$defaultrule])){
        if($MAIN[$defaultrule]<>null) {
            return $tpl->td_href($MAIN_TEXT[$defaultrule], "{view}", $MAIN[$defaultrule]);
        }else{
            return $tpl->_ENGINE_parse_body($MAIN_TEXT[$defaultrule]);
        }
    }
    return $defaultrule;
}

function search(){
    $tpl                        = new template_admin();
    $SquidNoAccessLogs          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    $time                       = null;
    $addPLUS                    = null;
    $GLOBALS["TPLZ"]            = $tpl;
    $date                       = null;
    $c                          = 0;
    $filename                   = "/usr/share/artica-postfix/ressources/logs/access.log.tmp";
    $MyPage                     =  CurrentPageName();
    $cancel                     = $tpl->_ENGINE_parse_body("{cancel}");
    $saved_in_cache             = $tpl->_ENGINE_parse_body("{saved_in_cache}");
    $logfileD                   = new logfile_daemon();
    $SquidVersion               = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
    $search_id                  = 0;
    $GLOBALS["redirect_text"]              = $tpl->_ENGINE_parse_body("{ForceRedirect}");

    if(isset($_GET["search-id"])){$search_id=intval($_GET["search-id"]);}
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){$SquidNoAccessLogs=0; }

    if($_GET["search"]==null){$_GET["search"]="50 events";}
    if($SquidNoAccessLogs==1){echo $tpl->div_error("<a href=\"/logs-rotate\" style='color:red'>{FATAL_SQUID_ACCESS_LOG}</a>");return;}
    if(!isset($_GET["SearchString"])){$_GET["SearchString"]=null;}
    if(!isset($_GET["FinderList"])){$_GET["FinderList"]=null;}

    $SIMPLERULES=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,aclname FROM webfilters_simpleacls");
    foreach ($results as $index => $ligne) {
        VERBOSE("$index]: ID: $ligne[ID] aclname: $ligne[aclname]",__LINE__);
        $ID = $ligne["ID"];
        $rulename = $ligne["aclname"];
        $SIMPLERULES[$ID]=$rulename;
    }


    if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
    $MAIN=$tpl->format_search_protocol($_GET["search"]);

    $sock=new sockets();
    $logfile="NONE";
    if($search_id>0){
        $logfile="searchid:$search_id";
    }
    if(isset($_GET["logfile"])){$logfile=$_GET["logfile"];}
    if($_GET["FinderList"]==null){$_GET["FinderList"]="NONE";}
    if($_GET["SearchString"]==null){$_GET["SearchString"]="NONE";}
    if($MAIN["TERM"]==null){$MAIN["TERM"]="NONE";}
    $DATE="NONE";
    $ipaddr="NONE";
    $ipclass=new IP();
    if(isset($_SESSION["SQUIDSEARCH"]["remote_addr"])){
        if($ipclass->isValid($_SESSION["SQUIDSEARCH"]["remote_addr"])){
            $ipaddr=$_SESSION["SQUIDSEARCH"]["remote_addr"];
        }

    }
    if(isset($_SESSION["SQUIDSEARCH"]["DateFrom"])){
        if(strlen($_SESSION["SQUIDSEARCH"]["DateFrom"])>3){
            $DATE=strtotime("{$_SESSION["SQUIDSEARCH"]["DateFrom"]} {$_SESSION["SQUIDSEARCH"]["TimeFrom"]}");
        }
        if(strlen($_SESSION["SQUIDSEARCH"]["TimeTo"])>3){
            $DATETO=strtotime("{$_SESSION["SQUIDSEARCH"]["DateFrom"]} {$_SESSION["SQUIDSEARCH"]["TimeTo"]}");
            if($DATETO>$DATE){
                $DATE="$DATE-$DATETO";
            }

        }

    }
    if($logfile=="NONE"){
        $logfileCheck="/var/log/squid/access.log";

    }

    $size=filesize($logfileCheck);
    $size=$size/1024;
    $size=$size/1024;
    $sock->REST_API_TIMEOUT=60;
    if($size>2000){
        $sock->REST_API_TIMEOUT=300;
    }

    $data=$sock->REST_API("/proxy/accesses/{$MAIN["MAX"]}/{$MAIN["TERM"]}/{$_GET["SearchString"]}/{$_GET["FinderList"]}/$DATE/$logfile/$ipaddr");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $ipaddr=$tpl->javascript_parse_text("{members}");
    $destination=$tpl->javascript_parse_text("{destinations}");
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");
    $proto=$tpl->_ENGINE_parse_body("{proto}");
    $uri=$tpl->_ENGINE_parse_body("{url}");
    $duration=$tpl->_ENGINE_parse_body("{duration}");
    $size=$tpl->_ENGINE_parse_body("{size}");
    $deny=$tpl->_ENGINE_parse_body("{deny}");
    $workgroup=$tpl->_ENGINE_parse_body("{workgroup}");
    $category=$tpl->_ENGINE_parse_body("{category}");
    $emergency_text = $tpl->_ENGINE_parse_body("{urgency_mode}");
    $theshield  = "The Shields";
    $PrivoxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyEnabled"));
    $hotspot_text=$tpl->_ENGINE_parse_body("&nbsp;<i>({hotspot})</i>");
    $SquidLogUsersAgents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogUsersAgents"));
    $webfiltering_error=$tpl->_ENGINE_parse_body("{webfiltering} {error}");

    if($PrivoxyEnabled==1){
        $PrivoxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyPort"));
        $LOCAL_SERVICES["127.0.0.1:$PrivoxyPort"]="{APP_PRIVOXY}";
    }


    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>";

    if($SquidLogUsersAgents==1) {
        $html[] = $tpl->_ENGINE_parse_body("<th>&nbsp;</th>");
    }
    $html[]="<th>$ipaddr</th>
			<th>&nbsp;</th>
            <th>$proto</th>
            <th>$category</th>
            <th>$uri</th>
            <th>INFO/LINK</th>
            <th>$destination</th>
            <th>$size</th>
            <th>$duration</th>
        </tr>
  	</thead>
	<tbody>
";
    $banico="<i class='fas fa-ban'></i>";
    $alido="<i class='far fa-check-circle'></i>";
    //<i class="fab fa-internet-explorer"></i>
    $auth_white=$tpl->_ENGINE_parse_body("<strong style='color:#19937a'>$alido&nbsp;{authentication} {whitelists}</strong>");
    $whitelist_text=$tpl->_ENGINE_parse_body("<strong style='color:#19937a'>$alido&nbsp;{whitelist}</strong>");
    $final_allow=$tpl->_ENGINE_parse_body("<strong style='color:#19937a'>$alido&nbsp;{final_allow}</strong>");
    $blacklist_text=$tpl->_ENGINE_parse_body("<strong>$banico&nbsp;{blacklists}</strong>");
    $host_forgery=$tpl->_ENGINE_parse_body("<strong>$banico&nbsp;{host_forgery}</strong>");
    $deny_remote_ports=$tpl->_ENGINE_parse_body("<strong>$banico&nbsp;{remote_port}</strong>");
    $ARRAY_TITLE_AV=$tpl->_ENGINE_parse_body("{ARRAY_TITLE_AV}");
    $default_text=$tpl->_ENGINE_parse_body("{default}");
    if($GLOBALS["VERBOSE"]) {
        echo "<code>READ: $filename</code>\n";
    }

    $bandwidth=null;
    $_SESSION["PROXY_SEARCH"]=$_GET["search"];
    $GLOBALS["Enablehacluster"]=$Enablehacluster;
    $file = fopen($filename, 'r');


    $IP=new IP();$IPClass=$IP;
    $today=date("Y-m-d");

    if(!isset($MAIN["CATEGORIES"])){$MAIN["CATEGORIES"]=null;}
    if(!isset($MAIN["DESTINATIONS"])){$MAIN["DESTINATIONS"]=null;}
    $SQUID_GOOD_CODE[200]=true;
    $SQUID_GOOD_CODE[301]=true;
    $SQUID_GOOD_CODE[302]=true;


    if(!isset($_SESSION["WEBFILTERINGS"])){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $sql="SELECT ID,groupname FROM webfilter_rules ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $_SESSION["WEBFILTERINGS"][$ligne["ID"]]=$ligne["groupname"];

        }

    }
    $zid=0;
    while (($line = fgets($file)) !== false) {
        if(strpos($line,"redis-server[")>0){
            continue;
        }
        VERBOSE("---- [<code>$line</code>] ----", __LINE__);
        $line = trim($line);
        if (strlen($line) < 3) {
            continue;
        }
        $array=ParseRealTime($line);
        if(count($array)==0){
            continue;
        }

        $ProxyName=$array["ProxyName"];
        $UA=$array["UA"];
        $icap_error=$array["icap_error"];
        $ERROR_EXT=$array["ERROR_EXT"];
        $ERROR_EXT_STR=$array["ERROR_EXT_STR"];
        $proxy_server=$array["proxy_server"];

        $xADDONS=$array["xADDONS"];
        $XMAC=$array["XMAC"];
        $SOURCE_URL=$array["SOURCE_URL"];
        $XUSER=$array["XUSER"];
        $URL=$array["URL"];
        $DESTINATION=$array["DESTINATION"];
        $PROTO=$array["PROTO"];
        $size=intval($array["size"]);
        $ArrayENC=$array["ArrayENC"];
        $ip=$array["ip"];
        $durationunit=$array["durationunit"];
        $duration=$array["duration"];
        $date=$array["date"];
        $zCode0=$array["zCode0"];
        $zCode1=$array["zCode1"];
        $outGoing=$array["IFACE"];
        $simplerule="";


        $c++;
        $color = "black";
        $codeToStringSEP = null;
        $iconK = null;
        $DestinationIPAddr = null;
        $ico_auth = null;
        $bandwidth = null;
        $FinalDestinationText="";


        if ($logfileD->CACHEDORNOT($zCode0)) {
            $color = "#009223";
        }
        $codeToString = $logfileD->codeToString($zCode1);
        $SQUID_CODE = $zCode0;
        $URLSRC="";
        $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"blur()\" ><i class='fas fa-question'></i></button>";
        $infos = null;
        $link_members = null;
        $category = null;
        $accessrule = null;
        $theshield_ico = null;
        $acl_peer_id = 0;
        $peer_rule_from_id = null;
        $DESTINATION_TEXT = null;
        $stylegreen = "style='color:rgb(26, 179, 148) !important'";
        $category_id = 0;
        $rule_text=$tpl->_ENGINE_parse_body("{rule}");


        if(strlen($outGoing)>2){
            $DESTINATION_TEXT="<small>$outGoing&nbsp;";
        }

        $zcategory = $tpl->icon_nothing();
        $MAC = null;
        $mac = null;$message = null;$user_domain = null;
        if ($PROTO == "CONNECT") {
            $color = "#BAB700";
            $PROTO = "SSL";
        }

        if (preg_match("#^(https|ftps)#", $URLSRC)) {
            $color = "#BAB700";
            $PROTO = "SSL";
        }

        if ($zCode1 > 399) {
            $color = "#D0080A";
        }
        if (intval($zCode1) == 0) {
            $color = "#D0080A";
        }
        if ($zCode1 == 307) {
            $color = "#F59C44";
        }
        $xTAGS = array();
        $hotspot = null;
        $NozCode = false;
        $first_error = null;
        VERBOSE("First Code == {$zCode0} - {$zCode1} ERROR_EXT=$ERROR_EXT");
        if ($zCode1 == 426) {
            $zCode0 = "Upgrade required";
        }
        if (preg_match("#TCP_MISS_ABORTED#", $zCode0)) {
            $zCode0 = "TCP Client aborted";
        }

        if ($zCode0 == "NONE_NONE" and $zCode1 == "409") {
            $NozCode = true;
            $zCode0 = "<a href=\"javascript:blur();\" 
            OnClick=\"javascript:s_PopUpFull('https://wiki.articatech.com/proxy-service/troubleshooting/host-forgery','1024','900');\" style='color:%color%;text-decoration:underline'>$host_forgery</a>";
        }

        if ($zCode0 == "NONE_NONE" and $zCode1 == "000") {
            $NozCode = true;
            $color = "black";
            if ($PROTO == "SSL") {
                $color = "#BAB700";
            }
            $zCode0 = "Certificate";
            VERBOSE("Certificate == $color");
        }

        if (preg_match("#TCP_TUNNEL_ABORTED#", $zCode0)) {
            $zCode0 = "Client close tunnel";
            $color = "#BAB700";
            $PROTO = "SSL";
        }

        $zCode0 = str_replace("TCP_TUNNEL", "SSL Connect", $zCode0);

        if ($zCode0 == "TAG_NONE") {
            if ($zCode1 == 503) {
                $zCode0 = "Terminated";
            }
        }
        VERBOSE("Proxy Numeric Code: $zCode1",__LINE__);
        $HTTP_SQUID_CODE=$zCode1;
        $zCode0 = $logfileD->SquidCodeToText($zCode0);
        $user="";
        if (isset($XMAC)) {
            if (preg_match("#mac=\"(.+?)\"#", $XMAC, $re)) {
                $MAC = $re[1];
                if ($MAC == "-") {
                    $MAC = null;
                }
                if ($MAC == "00:00:00:00:00:00") {
                    $MAC = null;
                }
            }
        }
        if ($MAC <> null) {
            $ArrayENC["MAC"] = $MAC;
            $mac = "$MAC";
        }

        if($ERROR_EXT=="ERR_SECURE_ACCEPT_FAIL"){
            $ERROR_EXT="";
            $color = "#D0080A";
            $zCode0="{ERR_SECURE_ACCEPT_FAIL}";
            $codeToString="";
        }


        $ArrayENC["DURATION"] = $duration;
        $ArrayENC["DESTINATION"] = $DESTINATION;
        $ArrayENC["PROTO"] = $PROTO;
        $ArrayENC["SIZE"] = FormatBytes($size / 1024);
        $ArrayENC["HTTP_CODE"] = $zCode0;
        if ($ERROR_EXT <> null && $zCode0<>"") {
            $zCode0 = "$zCode0 - $ERROR_EXT";
        }

        if(strlen($codeToString)>1) {
            $ArrayENC["HTTP_CODE"] = $zCode0 . " - " . $codeToString;
        }
        $DESTINATIONR=array();
        if(!is_null($DESTINATION)) {
            $DESTINATIONR = explode("/", $DESTINATION);
            if (isset($DESTINATIONR[1])) {
                if (trim($DESTINATIONR[1]) == "-:-") {
                    $DESTINATIONR[1] = "-";
                }
                $FinalDestinationText = trim($DESTINATIONR[1]);
            }
        }

        if ($FinalDestinationText == "-") {
            $DESTINATION_TEXT = "local";
            if (preg_match("#([0-9\.]+):([0-9]+)#", $SOURCE_URL, $rz)) {
                if (isset($LOCAL_SERVICES[$rz[1]])) {
                    $DESTINATION_TEXT = $DESTINATION_TEXT.$tpl->_ENGINE_parse_body($LOCAL_SERVICES[$rz[1]]);
                } else {
                    $DestinationIPAddr = $rz[1];
                    $ipaddrz = explode(".", $rz[1]);
                    $ipaddrzz = $ipaddrz[0] . "." . $ipaddrz[1] . "." . $ipaddrz[2];
                    $DESTINATION_TEXT = $DESTINATION_TEXT.$tpl->td_href($rz[1], "db-ip.com", "s_PopUp('https://db-ip.com/all/$ipaddrzz',1024,1024);");
                }
            }

        } else {
            if(!isset($DESTINATIONR[0])){
                $DESTINATIONR[0]=0;
            }
            if(!isset($DESTINATIONR[1])){
                $DESTINATIONR[1]="";
            }
            if ($GLOBALS["VERBOSE"]) {
                echo "<H3>" . __LINE__ . ":DESTINATION: $DESTINATIONR[1]</H3>";
            }
            if (isset($logfileD->CODE_DEST[$DESTINATIONR[0]])) {
                $DESTINATION_TEXT = $DESTINATION_TEXT.$logfileD->CODE_DEST[$DESTINATIONR[0]];
            }
            if ($DESTINATIONR[1] <> null) {
                $DestinationIPAddr = $DESTINATIONR[1];
                $ipaddrz = explode(".", $DESTINATIONR[1]);
                $ipaddrzz = $ipaddrz[0] . "." . $ipaddrz[1] . "." . $ipaddrz[2];
                $ipddrtxt = $tpl->td_href($DESTINATIONR[1], "db-ip.com", "s_PopUp('https://db-ip.com/all/$ipaddrzz',1024,1024);");
                $DESTINATION_TEXT = $DESTINATION_TEXT . " " . $ipddrtxt;
            }
            $DESTINATION_TEXT = $DESTINATION_TEXT . " ";
            if (isset($LOCAL_SERVICES[$DESTINATIONR[1]])) {
                $DESTINATION_TEXT = $DESTINATION_TEXT.$tpl->_ENGINE_parse_body($LOCAL_SERVICES[$DESTINATIONR[1]]);
            }

        }

        if(strpos("  $DESTINATION_TEXT","<small")>0){
            $DESTINATION_TEXT=$DESTINATION_TEXT."</small>";
        }

        if (preg_match("#TCP_REDIRECT#", $SQUID_CODE)) {
            $color = "#A01E1E";
        }
        if (preg_match("#TCP_DENIED#", $SQUID_CODE)) {
            $color = "#D0080A";
        }

        if (isset($xADDONS)) {
            $Mains = explode("\n", urldecode($xADDONS));
            foreach ($Mains as $tagline) {
                $tagline = trim($tagline);
                if ($tagline == null) {
                    continue;
                }
                $zz = explode(":", $tagline);
                $zkey = $zz[0];
                unset($zz[0]);
                $ArrayENC[$zkey] = @implode(":", $zz);
                VERBOSE("xTAGS: $zkey", __LINE__);
                $xTAGS[$zkey] = @implode(":", $zz);
            }

        }

        if (isset($xTAGS["srcurl"])) {
            $URL = urldecode($xTAGS["srcurl"]);
            $URL_XTAG = $URL;
        }

        if (isset($xTAGS["haclustertests"])) {
            $accessrule="<strong style='color:#18a689'><i class=\"fad fa-balance-scale\"></i>&nbsp;HaCluster Checks</strong>";
        }

        if (isset($xTAGS["authmec"])) {
            $id = trim($xTAGS["authmec"]);
            $ico_auth = $tpl->td_href("<i class='fas fa-user'></i>", null, "Loadjs('fw.proxy.auth_schemes.php?rule-id-js=$id')") . "&nbsp;";
        }
        //
        if (isset($xTAGS["accessrule"])) {

            $accessrule = trim($xTAGS["accessrule"]);
            VERBOSE("accessrule = $accessrule",__LINE__);
            if ($accessrule == "authwhite") {
                $accessrule = $auth_white;
            }
            if ($accessrule == "global_blacklist") {
                $accessrule = $blacklist_text;
            }
            if ($accessrule == "global_whitelist") {
                $accessrule = $whitelist_text;
            }
            if ($accessrule == "final_allow") {
                $accessrule = $final_allow;
            }
            if ($accessrule == "deny_remote_ports") {
                $accessrule = "$deny_remote_ports";
            }
            if ($accessrule == "theshield") {
                $accessrule = null;
            }
        }

        if (isset($xTAGS["acl_peer"])) {
            $acl_peer_id = intval(trim($xTAGS["acl_peer"]));
            $peer_rule_from_id = peer_rule_from_id($acl_peer_id);
        }
        if(isset($xTAGS["simplerule"])){
            $RuleNumber=intval($xTAGS["simplerule"]);
            VERBOSE("RuleNumber: $RuleNumber",__LINE__);
            $simplerule=$SIMPLERULES[$RuleNumber];
        }


        if (isset($xTAGS["bandwidth"])) {
            $bandwidth = "&nbsp;<i class='text-warning fas fa-sort-amount-down'></i>";
        }

        if (isset($xTAGS["category-name"])) {
            $zcategory = trim($xTAGS["category-name"]);
        }

        if (isset($xTAGS["webfiltering"])) {
            if (preg_match("#block,([0-9]+),(.+)#", $xTAGS["webfiltering"], $re)) {
                $zCode0 = "WEBFILTER";
                $zcategory = categoryCodeTocatz(intval($re[2]));
                if (!isset($_SESSION["WEBFILTERINGS"][$re[1]])) {
                    if ($re[1] == 0) {
                        $codeToString = $default_text;
                    }
                    VERBOSE("SESSION[WEBFILTERINGS] {$xTAGS["webfiltering"]} Unknown [{$re[1]}] category=$zcategory", __LINE__);
                } else {
                    $codeToString = $_SESSION["WEBFILTERINGS"][$re[1]];
                }
                $color = "#D0080A";
            }

            if (trim($xTAGS["webfiltering"]) == "block") {
                $zCode0 = "BLOCK";
                $codeToString = "";
                $color = "#D0080A";
            }
        }

        if (isset($xTAGS["srn"])) {
            $theshield_ico = null;
            $xval = trim($xTAGS["srn"]);
            $SRN["ADGUARD"] = "$theshield:AdGuard";
            $SRN["GOOGLE"] = "$theshield:GoogleSafe";
            $SRN["QUAD9"] = "$theshield:Quad9";
            $SRN["KASPERSKY"] = "$theshield:Kaspersky";
            $SRN["DNBSL"] = "$theshield:DNSBL";
            $SRN["GENERIC"] = "$theshield:Generic";
            $SRN["CLOUDFLARE"] = "$theshield:Cloudflare";
            $SRN["MALWAREURL_MALWARES"] = "$theshield:MalwareURL";
            $SRN["MALWAREURL_PHISHING"] = "$theshield:MalwareURL";
            $SRN["ARTICA"] = "$theshield:Artica";
            $SRN["EMERGENCY"] = "$theshield:$emergency_text";
            $SRN["WHITELIST"] = "$theshield:$whitelist_text";

            $SRN_NONE["PASS"] = true;
            $SRN_NONE["WHITE"] = true;
            $SRN_NONE["WHITELIST"] = true;
            $SRN_NONE["IPADRR"] = true;

            $id = microtime();
            if (isset($SRN[$xval])) {
                $theshield_ico = $tpl->td_href("<i class='text-danger fas fa-shield' id='$id'></i>", $SRN[$xval]) . "&nbsp;";
            }
            if ($xval == "EMERGENCY") {
                $theshield_ico = $tpl->td_href("<i class='text-warning fas fa-shield-alt' id='$id'></i>",
                        $SRN["EMERGENCY"]) . "&nbsp;";
            }
            if (isset($SRN_NONE[$xval])) {
                $theshield_ico = "<i class='text-primary fas fa-shield-check' id='$id'></i>&nbsp;";
            }


        }

        if (isset($xTAGS["GeoIPBlock"])) {
            $xTAGS["GeoIPBlock"] = trim($xTAGS["GeoIPBlock"]);
            $zCode0 = "BLOCK ({$xTAGS["GeoIPBlock"]})";
            $codeToString = "";
            $color = "#D0080A";
        }

        if (isset($xTAGS["RBLBLOCK"])) {
            $color = "#D0080A";
            $id = microtime();
            $theshield_ico = $tpl->td_href("<i class='text-danger fas fa-shield' id='$id'></i>", null) . "&nbsp;";
        }

        if (isset($xTAGS["rblpass"])) {
            VERBOSE("RBLPASS = TRUE", __LINE__);
            $color = "#1ab394";
            $id = microtime();
            $theshield_ico = $tpl->td_href("<i class='text-primary fas fa-shield-check' id='$id'></i>", $whitelist_text) . "&nbsp;";
        }
        if (isset($xTAGS["googlehearth"])) {
            $zCode0 = "Google Hearth - $whitelist_text";

        }

        if (isset($xTAGS["rblcache"])) {
            $zCode0 = "NO CACHE - RBL";
        }

        if (isset($xTAGS["rblcache"])) {
            if (isset($xTAGS["rblpass"])) {
                $zCode0 = "NO CACHE ALLOW - RBL";
                $color = "#1ab394";
            }
        }

        if (isset($xTAGS["itchart"])) {
            if (trim($xTAGS["itchart"]) == "ASK") {
                $zCode0 = "ITChart";
                unset($xTAGS["message"]);
            }
            if (trim($xTAGS["itchart"]) == "ERROR") {
                $hotspot = "ITChart Error";
            }
            if (trim($xTAGS["itchart"]) == "PASS") {
                $hotspot = "ITChart Pass";
            }
        }

        if (isset($xTAGS["message"])) {
            if (!isset($xTAGS["hotspot"])) {
                $message = "<div class=small>{$xTAGS["message"]}</div>";
            }
        }

        if (isset($xTAGS["first"])) {
            VERBOSE("xTAGS[first]=[{$xTAGS["first"]}]", __LINE__);
            if (trim($xTAGS["first"]) == "ERROR") {
                VERBOSE("xTAGS[errnum]={$xTAGS["errnum"]}", __LINE__);
                if (isset($xTAGS["errnum"])) {
                    $first_error = "<br><small></small><span class='text-danger'>
                    <i class=\"fa-solid fa-bug\"></i>&nbsp;$webfiltering_error {$xTAGS["errnum"]}</span></small>";
                }
            }
        }

        if (isset($xTAGS["clog"])) {
            if (preg_match("#cinfo:([0-9]+)-(.*?);#", $xTAGS["clog"], $re)) {
                $category_id = intval($re[1]);
                $zcategory = trim($re[2]);
            }
        }
        VERBOSE("Category = [$category_id] / zCategory=[$zcategory]", __LINE__);
        if (intval($category_id) == 0) {
            if (trim($zcategory) == null) {
                VERBOSE("xTAGS[category] = [{$xTAGS["category"]}]", __LINE__);
                if (isset($xTAGS["category"])) {
                    $category_id = intval($xTAGS["category"]);
                    if ($category_id > 0) {
                        $zcategory = categoryCodeTocatz($category_id);
                    }
                }
            }
        }

        if (intval($category_id) == 0) {
            VERBOSE("Category --- >  $URL", __LINE__);
            $category_id = internal_category($URL);
            if ($category_id > 0) {
                $zcategory = categoryCodeTocatz($category_id);
            }
        }

        if (isset($xTAGS["hotspot"])) {
            $hotspot = "<strong>$hotspot_text</strong>";
        }

        // ptime
        $ptime = null;
        if (isset($xTAGS["ptime"])) {
            $ptime = "/" . round($xTAGS["ptime"], 2) . "s";
            if (floatval($xTAGS["ptime"]) > 1) {
                $ptime = "/<strong class='text-warning'>" . round($xTAGS["ptime"], 2) . "s</strong>";
            }
            if (floatval($xTAGS["ptime"]) > 2) {
                $ptime = "/<strong class='text-danger'>" . round($xTAGS["ptime"], 2) . "s</strong>";
            }
        }
        $avinfo = null;
        if (isset($xTAGS["ecapav"])) {
            $ecapav = intval(trim($xTAGS["ecapav"]));
            if ($ecapav == 0) {
                $avinfo = " ($ARRAY_TITLE_AV OFF)";
            } else {
                $avinfo = " ($ARRAY_TITLE_AV ON)";
            }
        }

        $zCode0 = trim($zCode0);
        $itag = null;
        if(!is_null($XUSER)) {
            $user = trim($XUSER);
        }
        if ($user == "-") {
            $user = null;
        }

        if ($GLOBALS["VERBOSE"]) {
            $html[] = "<!-- URL: $URL [" . __LINE__ . "] -->";
        }

        $URLSRC = $URL;
        VERBOSE("->parseURL($URL,$PROTO", __LINE__);
        $MAINUTI = $logfileD->parseURL($URL, $PROTO, false);
        $ArrayENC["CLIENT_IP"] = "$ip";
        $ArrayENC["URL"] = $MAINUTI["GET_URL"];
        $ArrayENC["CATEGORY"] = $MAINUTI["CATEGORY"];
        if ($zcategory <> null) {
            $ArrayENC["CATEGORY"] = $zcategory;
        }
        $ArrayENC["FAMILYSITE"] = $MAINUTI["FAMILYSITE"];
        if ($ERROR_EXT <> null or $ERROR_EXT_STR <> null) {
            $ArrayENC["error"] = "$ERROR_EXT - $ERROR_EXT_STR";
        }

        $ArrayENC2 = urlencode(base64_encode(serialize($ArrayENC)));
        $link = $MAINUTI["LINK"];
        $URL = $MAINUTI["HOSTNAME"];
        VERBOSE("URL = [$URL] zcategory=[$zcategory]", __LINE__);
        $loupe = $tpl->icon_loupe(1, "Loadjs('$MyPage?zoom-js=yes&data=$ArrayENC2')");

        if (preg_match("#error:transaction-end-before-headers#", $SOURCE_URL)) {
            $URL = "-";
            $color = "#A01E1E";
            $codeToString = "Transaction EBH";
        }

        if (isset($_GET["fqdn"])) {
            if ($IPClass->isIPAddress($ip)) {
                if (!isset($RESOLV[$ip])) {
                    $RESOLV[$ip] = gethostbyaddr($ip);
                }
                $ip = $RESOLV[$ip];
            }

        }

        $zCode0 = str_replace("TCP_DENIED", $deny, $zCode0);
        $zCode0 = str_replace("NONE_", "", $zCode0);
        $zCode0 = str_replace("ABORTED", $cancel, $zCode0);
        $zCode0 = str_replace("TCP_REFRESH_MODIFIED", $saved_in_cache, $zCode0);


        if($zCode0=="WEBFILTER"){
            if($HTTP_SQUID_CODE==302){
                $accessrule="Redirect";
            }
        }

        if (isset($xTAGS["store-id"])) {
            $codeToString = "Hypercache - $codeToString";
        }

        if (isset($xTAGS["hypercache"])) {
            if (intval(trim($xTAGS["hypercache"])) == 1) {
                $codeToString = "Hypercache-proxy - $codeToString";
            }
        }

        if (isset($xTAGS["webfilterpolicy"])) {
            $codeToString = "WebFilter OFF - $codeToString";
        }

        VERBOSE("codeToString = [$codeToString]",__LINE__);

        if (isset($xTAGS["user"])) {
            $user = trim($xTAGS["user"]);
        }
        if ($codeToString <> null) {
            $codeToStringSEP = " - ";
        }
        if ($GLOBALS["VERBOSE"]) {
            echo "<H3>" . __LINE__ . ":MAC: [$mac]</H3>\n";
        }
        if (isset($xTAGS["ufdbunblock"])) {
            $itag = "<i class='fas fa-thumbs-up'></i>&nbsp;";
        }

// ------------------------------- SQUID 5 log access rules  

        if ($accessrule <> null) {
            if (preg_match("#Rule([0-9]+)#", $accessrule, $re)) {
                if (isset($_SESSION["MAIN_ACCESS_RULES"][$re[1]])) {
                    $aclname = $_SESSION["MAIN_ACCESS_RULES"][$re[1]];
                    $ruleid = $re[1];
                    $accessrule_link = "Loadjs('fw.proxy.acls.php?rule-id-js=$ruleid')";
                    $accessrule_style = "style='color:$color;text-decoration: underline'";
                    $accessrule = "<a href=\"javascript:blur();\" OnClick=\"$accessrule_link\" $accessrule_style>$aclname</a>";
                }
            }

            $accessrule = default_access_rules($accessrule);
            $accessrule = "&nbsp;-&nbsp;$accessrule";
        }
        if(strlen($simplerule)>2){
            $simplerule="<br>$rule_text: <strong>$simplerule</strong>";
        }

// ------------------------------- -------------------------------
        if ($mac <> null) {
            $macenc = urlencode($mac);
            if ($GLOBALS["VERBOSE"]) {
                echo "->td_href($mac,\"{computer}\"<br>\n";
            }
            $mac = "mac: " . $GLOBALS["TPLZ"]->td_href($mac, "{computer}", "Loadjs('fw.edit.computer.php?mac=$macenc&prependip=$ip&ByProxy=yes')");

        }

        if ($user <> null) {
            if (preg_match("#(.+?)\/(.+)#", $user, $re)) {
                $user = $re[2];
                $user_domain = "<div><span class=small>mac:$mac $workgroup: {$re[1]}$hotspot</span></div>";
            }
            $user = "/<strong>$user</strong>";
        }

        if ($size > 1024) {
            $size = FormatBytes($size / 1024);
        } else {
            $size = "$size Bytes";
        }
        $date = str_replace($today . " ", "", $date);
        if ($mac <> null) {
            if ($user_domain == null) {
                $user_domain = "<div><span class=small>$mac$hotspot</span></div>";
            }
        }

        if (isset($xTAGS["notracks"])) {
            $zCode0 = "NoTrack";
        }

        if ($GLOBALS["VERBOSE"]) {
            $html[] = "<!-- $URLSRC URL: $URL [" . __LINE__ . "] -->";
        }
        $URLSRCEncoded="";
        if(!is_null($URLSRC)) {
            $URLSRCEncoded = urlencode($URLSRC);
        }
        $domain_field = $tpl->td_href($URL,
            "{actions}", "Loadjs('fw.proxy.relatime.actions.php?dom=" . urlencode($URL) . "&category-id=$category_id&urlsrc=$URLSRCEncoded')");


        $html[] = "<tr>";
        $html[] = "<td><span style='color:$color'>$date{$proxy_server}</span></td>";

        if ($SquidLogUsersAgents == 1) {
            if ($UA <> null) {
                $js = "Loadjs('fw.proxy.relatime.php?ua-js=" . base64_encode($UA) . "');";
                $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" >
                <i class='fas fa-browser'></i></button>";

                if (preg_match("#Edge\/#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-edge'></i></button>";
                }
                if (preg_match("#Firefox\/#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-firefox'></i></button>";
                }

                if (preg_match("#Chrome/[0-9\.]+#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-chrome'></i></button>";
                }

                if (preg_match("#Safari/[0-9\.]+#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-chrome'></i></button>";
                }
                if (preg_match("#OPR/[0-9\.]+#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-opera'></i></button>";
                }
                if (preg_match("#Trident\/[0-9]+\.#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-internet-explorer'></i></button>";
                }
                if (preg_match("#MSIE\s+[0-9\.]+#", $UA)) {
                    $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fab fa-internet-explorer'></i></button>";
                }

            }
            if ($icon_ua == null) {
                $icon_ua = "<button type='button' class='btn btn-white btn-icon' OnClick=\"$js\" ><i class='fas fa-browser'></i></button>";
            }

            $html[] = "<td><span class='center' width='1%'>$icon_ua</span></td>";

        }

        $geo=GeoIPCountry($ip);
        $FromCountry="";
        $ToCountry = "";

        if(!is_null($geo)) {
            $FromCountry = strtolower($geo);
        }

        if ($DestinationIPAddr <> null) {
            $geo=GeoIPCountry($DestinationIPAddr);
            if(!is_null($geo)) {
                $ToCountry = strtolower($geo);
            }
        }
        if(strlen($ToCountry)>1) {
            if (is_file("img/flags/$ToCountry.png")) {
                $ToCountry = "<img src='img/flags/$ToCountry.png'>&nbsp;";
            }
        }
        if(strlen($FromCountry)>1) {
            if (is_file("img/flags/$FromCountry.png")) {
                $FromCountry = "<img src='img/flags/$FromCountry.png'>&nbsp;";
            }
        }

        $iptext = $ip;
        $resolveIP2HOST = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if ($resolveIP2HOST == 1) {
            $host = new ip2host($ip);
            $iptext = $host->output;
        }

        $ipencode = urlencode($iptext);
        $iptext = $tpl->td_href($ip, null, "Loadjs('fw.proxy.relatime.actions.php?dom=$ipencode&from=yes')");
        $link = str_replace("%color%", $color, $link);
        $domain_field = str_replace("%color%", $color, $domain_field);
        $zCode0 = str_replace("%color%", $color, $zCode0);

        VERBOSE("Final zcategory=[$zcategory] Access RULE = $accessrule", __LINE__);
        if ($NozCode) {
            $codeToStringSEP = null;
            $codeToString = null;
        }

        if (isset($xTAGS["bumprule"])) {
            $sslrulename = get_sslrulename($xTAGS["bumprule"]);
            if ($sslrulename <> null) {
                $PROTO = $tpl->td_href("<i class='text-warning fa-solid fa-binary-lock' id='ssl-$c'></i>", $sslrulename, "Loadjs('fw.proxy.ssl.rules.php?rule-id-js={$xTAGS["bumprule"]}')");
            }
        }

        if (isset($xTAGS["splicerule"])) {
            $sslrulename = get_sslrulename($xTAGS["splicerule"]);
            if ($sslrulename <> null) {
                $PROTO = $tpl->td_href("<i class='fa-solid fa-binary-circle-check' id='ssl-$c' $stylegreen></i>", $sslrulename, "Loadjs('fw.proxy.ssl.rules.php?rule-id-js={$xTAGS["splicerule"]}')");
            }
        }
        if (isset($xTAGS["whitelistssl"])) {
            $PROTO = "<i class='fa-solid fa-binary-circle-check' id='ssl-$c' $stylegreen></i>";
        }
//<i class="fa-sharp fa-solid fa-plug-circle-minus"></i>
        if (isset($xTAGS["icapwhite"])) {
            $PROTO = "$PROTO&nbsp;<i class='fa-solid fa-plug-circle-minus' style='color:$color'></i>&nbsp;<small>ICAP&nbsp;OFF</small>";
        }
        if ($icap_error <> null) {
            $PROTO = "$PROTO&nbsp;$icap_error&nbsp;<small>ICAP&nbsp;ERROR</small>";
        }

        if ($acl_peer_id > 0) {
            if (strlen($FinalDestinationText) < 3) {
                $FinalDestinationText = null;
            } else {
                $FinalDestinationText = " ($FinalDestinationText)";
            }
            $DESTINATION_TEXT = $tpl->td_href($peer_rule_from_id . $FinalDestinationText, $FinalDestinationText,
                "Loadjs('fw.proxy.parents.php?ruleid-js=$acl_peer_id',true);");

        }

        if(!is_null($message)) {
            $TicketNotYetValid = "https://wiki.articatech.com/proxy-service/troubleshooting/gss-ticket-not-yet-valid";
            $likelyOutOfDate = "https://wiki.articatech.com/proxy-service/troubleshooting/gss-ticket-out-of-date";
            $message = str_replace("Ticket not yet valid", $tpl->td_href("<b>Ticket not yet valid</b>", "", "s_PopUp('$TicketNotYetValid','1024','768')"), $message);
            $message = str_replace("likely out of date", $tpl->td_href("<b>likely out of date</b>", "", "s_PopUp('$likelyOutOfDate','1024','768')"), $message);
        }
        $formatage1=array();
        $ico=ico_computer;
        $space="";
        if(!is_null($user)) {
            if (strlen($user) > 3){
                $ico=ico_user;
            }
        }
        if(!is_null($ico_auth)){
            $formatage1[]=$ico_auth;
            $space="&nbsp;";
        }
        if(!is_null($iconK)) {
            $formatage1[] = $iconK;
        }

        if(strlen($FromCountry)>1) {
            $formatage1[] = $FromCountry;
            $space="&nbsp;";
        }

        if(!is_null($ProxyName)) {
            if (strlen($ProxyName) > 1) {
                $formatage1[] = $ProxyName;
                $space="&nbsp;";
            }
        }

        $formatage1[]="$space<i class='$ico'></i>&nbsp;";
        $formatage1[]=$iptext;
        $formatage1[]=$user;
        $formatage1[]=$link_members;
        $formatage1[]=$user_domain;
        $formatagetext=@implode("",$formatage1);

        $html[] = "<td><span style='color:$color'>$formatagetext</span></td>
				<td><span style='color:$color'>$theshield_ico$zCode0$codeToStringSEP$codeToString$avinfo$category$accessrule$simplerule</span>$message$first_error</td>
                <td><span style='color:$color'>$PROTO</span></td>  
                <td><span style='color:$color'>$zcategory</span></td>  
                <td><span style='color:$color'>$itag$domain_field$infos</span></td>                  
                <td nowrap class=center>$loupe&nbsp;&nbsp;$link</td>
                <td>$ToCountry$DESTINATION_TEXT</td>
                <td><span style='color:$color'>{$size}$bandwidth</span></td>  
                <td><span style='color:$color'>{$duration}$durationunit$ptime</span></td>  
                </tr>";


    }
    fclose($file);
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='9'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    $html[]="<div style='font-size:10px'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/access.log.cmd")."</div>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";

    $jstiny="";
    if(isset($_GET["notitle"])) {

        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/access/log/dates"));

        $date1=$tpl->time_to_date($data->TimeStart,true);
        $date2=$tpl->time_to_date($data->TimeEnd,true);



        $TINY_ARRAY["TITLE"] = "{realtime_requests}<br><small>{from} $date1&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;$date2</small>";
        $TINY_ARRAY["ICO"] = ico_eye;
        $TINY_ARRAY["EXPL"] = "{realtime_requests_explain}";
        $TINY_ARRAY["BUTTONS"] = null;
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    }

    $html[]=$jstiny;
    $html[]="</script>";
    echo @implode("\n", $html);



}

function get_sslrulename($id):string{
    if(intval($id)==0){return "";}
    if(isset($GLOBALS["get_sslrulename"][$id])){return $GLOBALS["get_sslrulename"][$id];}
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT description FROM ssl_rules WHERE ID=$id");
    if(!is_array($ligne)){return "";}
    if(!isset($ligne["description"])){return "";}
    $GLOBALS["get_sslrulename"][$id]=$ligne["description"];
    return $GLOBALS["get_sslrulename"][$id];
}

function categoryCodeTocatz($category){
    if(preg_match("#P([0-9]+)#", $category,$re)){$category=$re[1];}
    $category=intval($category);
    if($category==0){return "($category) Unknown(0)";}

    $catz=new mysql_catz(true);
    $categories_descriptions=$catz->categories_descriptions();
    if(!isset($categories_descriptions[$category]["categoryname"])){
        return "($category) <strong>Unkown</strong>";
    }

    $name=$categories_descriptions[$category]["categoryname"];
    $category_description=$categories_descriptions[$category]["category_description"];
    $js="Loadjs('fw.ufdb.categories.php?category-js=$category')";
    return $GLOBALS["TPLZ"]->td_href($name,$category_description,$js);
}

function GeoIPCountry($ipadddr):string{

    if(preg_match("#^(.+?):[0-9]+#",$ipadddr,$re)){$ipadddr=$re[1];}

    $mem=new lib_memcached();
    if(!isset($GLOBALS["PHP_GEOIP_INSTALLED"])){
        $GLOBALS["PHP_GEOIP_INSTALLED"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    }
    if(!isset($GLOBALS["EnableGeoipUpdate"])){
        $GLOBALS["EnableGeoipUpdate"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    }

    if($GLOBALS["PHP_GEOIP_INSTALLED"]==0){return "";}
    if($GLOBALS["EnableGeoipUpdate"]==0){return "";}
    if (!extension_loaded("maxminddb")) {return "";}



    $value=unserialize($mem->getKey("GEOIP:$ipadddr"));
    if(!is_array($value)){$value=array();}

    if(!isset($value["countryCode"])) {
        try {
            $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
            $record = $reader->country($ipadddr);
            $value["countryCode"] = $record->country->isoCode;
            $value["countryName"] = $record->country->name;
            $mem->saveKey("GEOIP:$ipadddr",serialize($value),16000);
        } catch (Exception $e) {

            return "";
        }
    }


    if ($value["countryCode"] == null) {return "";}
    $countryCode=$value["countryCode"];
    return $countryCode;


}

function internal_category($url):int{
    if(is_null($url)){
        return 0;
    }
    if(preg_match("#^(.+?):[0-9]+#",$url,$re)){$url=$re[1];}
    if(isset($GLOBALS["internal_category"][$url])){return $GLOBALS["internal_category"][$url];}
    if(preg_match("#(artica|articatech)\.(fr|com|net)#",$url)){
        $GLOBALS["internal_category"][$url]=126;
        return 126;}
    if(preg_match("#(google|googleapis|googleoptimize)\.(fr|com|it|es|pl|nl|de|co\.uk|br|pt)#",$url)){
        $GLOBALS["internal_category"][$url]=17;
        return 17;}
    if(!isset($GLOBALS["CATEGORIZE_INTERNAL"])){
        $f=explode("\n",@file_get_contents("ressources/databases/categories.org"));
        foreach ($f as $line){
            $line=trim($line);
            if(substr($line,0,1)=="#"){continue;}
            if(strpos($line,"/")==0){continue;}
            $t=explode("/",$line);
            if(!isset($t[2])){$t[2]=null;}

            if($t[0]=="fixed"){
                $GLOBALS["CATEGORIZE_INTERNAL"]["FIXED"][$t[1]]=$t[2];
                continue;
            }
            $GLOBALS["CATEGORIZE_INTERNAL"]["regex"][$t[1]]=$t[2];

        }
    }
    if(isset($GLOBALS["CATEGORIZE_INTERNAL"][$url])){
        $GLOBALS["internal_category"][$url]=$GLOBALS["CATEGORIZE_INTERNAL"][$url];
        return $GLOBALS["CATEGORIZE_INTERNAL"][$url];
    }
    foreach ($GLOBALS["CATEGORIZE_INTERNAL"]["regex"] as $regex=>$cat){
        if(preg_match("#$regex#i",$url)){
            $GLOBALS["internal_category"][$url]=$cat;
            return $GLOBALS["internal_category"][$url];
        }

    }


    VERBOSE($url ."(No category)",__LINE__);
    return 0;

}

function peer_rule_from_id($id){
    if(!is_numeric($id)){
        return "";
    }
    if(isset($GLOBALS["peer_rule_from_id"][$id])){
        return strval($GLOBALS["peer_rule_from_id"][$id]);
    }
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_parents_acls WHERE aclid='$id'");
    if(!$q->ok){
        writelogs($q->mysql_error,__FILE__,__FUNCTION__,__LINE__);
        return "";
    }
    if(!isset($ligne["rulename"])){
        return "";
    }
    $rulename=$ligne["rulename"];
    $GLOBALS["peer_rule_from_id"][$id]=$rulename;
    return $rulename;
}