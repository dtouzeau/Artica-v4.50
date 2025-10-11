<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table_start();exit;}
if(isset($_GET["table-main"])){table_main();exit;}
if(isset($_GET["dnscache-edit-watchdog-js"])){dnscache_edit_watchdog_js();exit;}
if(isset($_GET["dnscache-edit-watchdog-popup"])){dnscache_edit_watchdog_popup();exit;}
if(isset($_POST["DnsProxyCacheWatchdog"])){dnscache_edit_watchdog_save();exit;}
if(isset($_GET["watchdog-status"])){watchdog_status();exit;}
if(isset($_GET["watch-js"])){watchdog_js();exit;}
if(isset($_GET["watch-popup"])){watch_popup();exit;}
if(isset($_POST["DnsDistEnableWatchdog"])){watch_save();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["search"])){events_search();exit;}
if(isset($_GET["history"])){history_table();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{watchdog}",ico_watchdog,"{dns_watchdog_howto}",
        "$page?tabs=yes","dns-watchdog","progress-unbound-restart",false,"table-loader-watchdog");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}


	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function events_start():bool
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{history}"]="$page?history=yes";
    $array["{events}"]="$page?events-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function watchdog_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{watchdog}","$page?watch-popup=yes");
    return true;
}
function watch_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DnsDistDisableWatchdog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistDisableWatchdog"));
    if($DnsDistDisableWatchdog==0) {$DnsDistEnableWatchdog=1;}else{$DnsDistEnableWatchdog=0;}
    $DnsDistWatchdogHosts=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistWatchdogHosts"));

    $DnsDistWatchdogOutInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistWatchdogOutInterface"));

    if($DnsDistWatchdogHosts==null){$DnsDistWatchdogHosts="play.google.com,www.google.com,ogs.google.com,www.ibm.com,www.firefox.com,www.microsoft.com,www.defense.gov,www.nyc.gov,ec.europa.eu,www.icj-cij.org,safebrowsing.googleapis.com";}

    $DnsProxyCacheWatchdogInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsProxyCacheWatchdogInt"));

    $DnsDistWatchdogMaxTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistDisableWatchdog"));
    if($DnsProxyCacheWatchdogInt==0){$DnsProxyCacheWatchdogInt=1;}
    if($DnsDistWatchdogMaxTimeOut==0){$DnsDistWatchdogMaxTimeOut=3;}



    $jsafter[]="LoadAjaxSilent('dnswatch-main-status','$page?table-main=yes');";
    $jsafter[]="dialogInstance2.close()";

    $form[]=$tpl->field_checkbox("DnsDistEnableWatchdog","{ENABLE_VIRTUALBOX_WATCHDOG}",$DnsDistEnableWatchdog);
    $form[]=$tpl->field_text("DnsDistWatchdogHosts","{remote_hosts}",$DnsDistWatchdogHosts);
    $form[]=$tpl->field_numeric("DnsDistWatchdogMaxTimeOut", "{timeout} ({seconds})", $DnsDistWatchdogMaxTimeOut,true);

    $form[]=$tpl->field_interfaces("DnsDistWatchdogOutInterface", "{outgoing_interface}", $DnsDistWatchdogOutInterface);

    $check[1]="1 {minute}";
    $check[3]="3 {minutes}";
    $check[5]="5 {minutes}";
    $check[10]="10 {minutes}";
    $check[30]="30 {minutes}";

    $form[]=$tpl->field_array_hash($check,"DnsProxyCacheWatchdogInt","{check_resolution_each}",$DnsProxyCacheWatchdogInt);
    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsDnsAdministrator");
    return true;

}
function watch_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DnsDistWatchdogHosts=$_POST["DnsDistWatchdogHosts"];
    if($_POST["DnsDistEnableWatchdog"]==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsDistDisableWatchdog",0);
        admin_tracks("Enable Watchdog for DNS Firewall ($DnsDistWatchdogHosts)");
    }else{
        admin_tracks("Disable Watchdog for DNS Firewall");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsDistDisableWatchdog",1);
    }
    unset($_POST["DnsDistEnableWatchdog"]);
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/reload");
    return true;
}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='dnswatch-main-status'></div>
    <script>LoadAjaxSilent('dnswatch-main-status','$page?table-main=yes');</script>";
    return true;
}

function table_main(){

	$tpl=new template_admin();
	$page=CurrentPageName();
    $DnsDistDisableWatchdog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistDisableWatchdog"));

    $tpl->table_form_field_js("Loadjs('$page?watch-js=yes')");

    $DnsDistWatchdogHosts=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistWatchdogHosts"));
    if($DnsDistWatchdogHosts==null){$DnsDistWatchdogHosts="play.google.com,www.google.com,ogs.google.com,www.ibm.com,www.firefox.com,www.microsoft.com,www.defense.gov,www.nyc.gov,ec.europa.eu,www.icj-cij.org,safebrowsing.googleapis.com";}
    $DnsDistWatchdogHosts=str_replace(",", ", ",$DnsDistWatchdogHosts);

    if($DnsDistDisableWatchdog==0) {

        $tpl->table_form_field_bool("{ENABLE_VIRTUALBOX_WATCHDOG}",1, ico_watchdog);

    }else{
        $tpl->table_form_field_bool("{ENABLE_VIRTUALBOX_WATCHDOG}",0,ico_watchdog);
    }

     $tpl->table_form_field_text("{remote_hosts}", "<span style='text-transform: initial'> $DnsDistWatchdogHosts</span>", ico_servcloud);

    $DnsProxyCacheWatchdogInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsProxyCacheWatchdogInt"));
    $DnsDistWatchdogMaxTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistWatchdogMaxTimeOut"));

    if($DnsDistWatchdogMaxTimeOut==0){
        $DnsDistWatchdogMaxTimeOut=3;
    }


    if($DnsProxyCacheWatchdogInt==0){$DnsProxyCacheWatchdogInt=1;}
    $tpl->table_form_field_text("{check_resolution_each}","$DnsProxyCacheWatchdogInt {minutes}",ico_clock);

    $tpl->table_form_field_text("{timeout}","$DnsDistWatchdogMaxTimeOut {seconds}",ico_timeout);

    $array["hourly"]="{today} {this_hour}";
    $array["day"]="{today}";
    $array["yesterday"]="{yesterday}";
    $array["week"]="{this_week}";
    $array["month"]="{month}";
    $array["year"]="{year}";
	$main[]=$tpl->table_form_compile();
    $t=time();
    foreach ($array as $period=>$none) {
        if (is_file("img/squid/dns_failures-$period.flat.png")) {
            $main[] = "<div style='margin:5px'><img src='img/squid/dns_failures-$period.flat.png?t=$t'></div>";

        }
    }



    $TINY_ARRAY["TITLE"]="{watchdog}";
    $TINY_ARRAY["ICO"]=ico_watchdog;
    $TINY_ARRAY["EXPL"]="{dns_watchdog_howto}";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html="<table style='width:100%'>
	<tr>
	    <td	style='vertical-align:top;width:30%'><div id='watchdog-status'></div></td>
		<td	style='vertical-align:top;width:70%'>".@implode("",$main)."</td>
	</tr>
	</table>
	<script>
	$jstiny
	LoadAjaxSilent('watchdog-status','$page?watchdog-status=yes');
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function dnscache_edit_watchdog_popup():bool{
    $tpl=new template_admin();


    $DnsDistWatchdogHosts=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistWatchdogHosts"));
    $defhosts=array("play.google.com", "www.google.com", "ogs.google.com", "www.ibm.com", "www.firefox.com", "www.microsoft.com", "www.defense.gov", "www.nyc.gov",
        "ec.europa.eu", "www.icj-cij.org", "safebrowsing.googleapis.com");


    if($DnsDistWatchdogMaxTimeOut==0){
        $DnsDistWatchdogMaxTimeOut=3;
    }

    if(strlen($DnsDistWatchdogHosts)<5){
        $DnsDistWatchdogHosts=@implode(",",$defhosts);
    }

    if($DnsDistDisableWatchdog==1){
        $DnsProxyCacheWatchdog=0;
    }else{
        $DnsProxyCacheWatchdog=1;
    }

    $form[]=$tpl->field_checkbox("DnsProxyCacheWatchdog", "{enable}", $DnsProxyCacheWatchdog,true);
    $form[]=$tpl->field_numeric("DnsDistWatchdogMaxTimeOut", "{timeout} ({seconds})", $DnsDistWatchdogMaxTimeOut,true);
    $form[]=$tpl->field_text("DnsDistWatchdogHosts", "{remote_hosts}",$DnsDistWatchdogHosts);

    $check[1]="1 {minute}";
    $check[3]="3 {minutes}";
    $check[5]="5 {minutes}";
    $check[10]="10 {minutes}";
    $check[30]="30 {minutes}";

    $form[]=$tpl->field_array_hash($check,"DnsProxyCacheWatchdogInt","{check_resolution_each}",$DnsProxyCacheWatchdogInt);

    $function=$_GET["function"];
    $function_js="$function()";
    echo $tpl->form_outside(null,$form,"{dnscache_watchdog_explain}","{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
    return true;
}
function dnscache_edit_watchdog_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if($_POST["DnsProxyCacheWatchdog"]==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsDistDisableWatchdog",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsDistDisableWatchdog",1);
    }
    unset($_POST["DnsProxyCacheWatchdog"]);
    $tpl->SAVE_POSTs();
    admin_tracks_post("Saving DNS watchdog Daemon settings");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reconfigure");
    return true;
}
function dnscache_edit_options_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $resolv=new resolv_conf();
    $resolv->MainArray["TIMEOUT"]=$_POST["TIMEOUT"];
    $MB=$_POST["DnsProxyCache"];
    $KB=$MB*1024;
    $BYTES=$KB*1024;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$BYTES);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSCacheListenInterface",$_POST["DNSCacheListenInterface"]);
    admin_tracks_post("Save global DNS parameters");
    return true;
}
function watchdog_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $button["name"] = "{parameters}";
    $button["js"] = "Loadjs('$page?watch-js=yes')";;
    $button["ico"]=ico_params;

    $DnsDistDisableWatchdog = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistDisableWatchdog"));
    if ($DnsDistDisableWatchdog == 1) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("grey",ico_watchdog,"{disabled}","{watchdog}",$button));
        return true;
    }
    echo $tpl->_ENGINE_parse_body($tpl->widget_h("green",ico_watchdog,"{enabled}","{watchdog}",$button));
    return true;
}

function events_search():bool{
    clean_xss_deep();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);

    $sock=new sockets();
    if(strlen($MAIN["TERM"])==0){$MAIN["TERM"]="NONE";}
    $data=$sock->REST_API("/dnswatchdog/accesses/{$MAIN["MAX"]}/{$MAIN["TERM"]}");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    foreach ($json->Results as $line){
        $line=trim($line);

        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#", $line,$re)){
            //echo "<strong style='color:red'>$line</strong><br>";
            continue;}

        $xtime=strtotime($re[1] ." ".$re[2]." ".$re[3]);
        $FTime=date("Y-m-d H:i:s",$xtime);
        $curDate=date("Y-m-d");
        $FTime=trim(str_replace($curDate, "", $FTime));
        $pid=$re[4];
        $line=$re[5];

        $line=str_replace("[INFO]:","<span class='label label-default'>INFO</span>",$line);
        $line=str_replace("[WARNING]:","<span class='label label-warning'>WARNING</span>",$line);
        $line=str_replace("[ERROR]:","<span class='label label-danger'>ERROR</span>",$line);



        if(preg_match("#(fatal|Err)#i", $line)){
            $line="<span class='text-danger'>$line</span>";
        }




        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";

    }

    if($_GET["search"]==null){$_GET["search"]="*";}



    $TINY_ARRAY["TITLE"]="{watchdog} {events} &laquo;{$_GET["search"]}&raquo;";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{dns_watchdog_howto}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function history_table(){
    //_, err = db.Exec(`CREATE TABLE IF NOT EXISTS dnswatchdog ( ID INTEGER PRIMARY KEY AUTOINCREMENT,zDate INTEGER,dnsserver TEXT NULL,error TEXT,queryserv string,notified INTEGER DEFAULT 0)`)

    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table id='table-history-dns-watchdog' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:10px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{dns_server}</th>";
    $html[]="<th data-sortable=false>{host}</th>";
    $html[]="<th data-sortable=false>{error}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $q = new lib_sqlite("/home/artica/SQLITE/dns_watchdog.db");
    $results=$q->QUERY_SQL("SELECT * FROM dnswatchdog ORDER BY ID DESC LIMIT 500");

    foreach ($results as $index=>$ligne) {
        if($oldTime>0){
            $ilya=" (".distanceOfTimeInWords($oldTime,$ligne["zDate"]).")";
        }else{
            $ilya=" (".distanceOfTimeInWords($ligne["zDate"],time()).")";
        }
        $enabled_text="&nbsp;";
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $text_class = null;
        $dnsserver = $ligne["dnsserver"];
        $queryserv = $ligne["queryserv"];
        $error=$ligne["error"];
        $zDate=$tpl->time_to_date($ligne["zDate"],true);
        $oldTime=$ligne["zDate"];
        $ID = $ligne["ID"];



        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%' class='left' nowrap>$zDate$ilya</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$dnsserver</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$queryserv</td>";
        $html[]="<td style='width:99%'><strong>$error</strong></td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><script>NoSpinner();\n";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-history-dns-watchdog').footable( { \"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}