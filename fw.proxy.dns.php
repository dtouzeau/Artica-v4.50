<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ID"])){dnsrules_save();exit;}
if(isset($_GET["search-form"])){search_squid();exit;}
if(isset($_GET["search-proxy"])){search_proxy();exit();}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["dns-params"])){dns_params();exit;}
if(isset($_GET["dns-rules"])){dnsrules_start();exit;}
if(isset($_GET["dns-rules-list"])){dnsrules_table();exit;}
if(isset($_GET["dns-rules-id"])){dnsrules_js();exit;}
if(isset($_GET["dns-rules-popup"])){dnsrules_popup();exit;}
if(isset($_GET["dns-rules-delete"])){dnsrules_delete_js();exit;}
if(isset($_POST["dns-rules-delete"])){dnsrules_delete_confirm();exit;}
if(isset($_GET["dns-rules-enabled"])){dnsrules_enable();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["dns-graph-queries"])){statistics_chart_dns();exit;}
if(isset($_GET["dns-graph-queries-table"])){statistics_chart_dns_table();exit;}
page();

function dns_params(){
    $html[]="<div id='dns-used-proxy' style='margin-top:20px'></div>";
    $html[]="<script>LoadAjax('dns-used-proxy','fw.proxy.dns.servers.php');</script>";
    echo @implode("\n",$html);
}

function tabs(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));

    $array["{cache_parameters}"]="fw.proxy.general.php?dns=yes&tiny=yes";

    if($DoNotUseLocalDNSCache==1) {
        $array["{APP_PROXY}: {dns_settings}"] = "$page?dns-params=yes";
        $array["DNS: {rules}"] = "$page?dns-rules=yes";
    }
    $array["{DNS_RECORDS}"] = "$page?search-form=yes";

    echo $tpl->tabs_default($array);
}
function delete(){
    $hostname=$_GET["delete"];
    $sock=new sockets();
    $sock->getFrameWork("squid2.php?ipdns-delete=$hostname");
    $md=$_GET["md"];
    echo "$('#$md').remove();\n";
}
function dnsrules_enable(){
    $ID=intval($_GET["dns-rules-enabled"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_dns_rules WHERE ID=$ID");
    $enabled=intval($ligne["enabled"]);
    if($enabled==0){
        admin_tracks("Set Proxy DNS rule $ID to enabled");
        $q->QUERY_SQL("UPDATE squid_dns_rules SET enabled=1 WHERE ID=$ID");
        return true;
    }
    admin_tracks("Set Proxy DNS rule $ID to disabled");
    $q->QUERY_SQL("UPDATE squid_dns_rules SET enabled=0 WHERE ID=$ID");
    return true;

}
function dnsrules_delete_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["dns-rules-delete"]);
    $md=$_GET["md"];
    $tpl->js_confirm_delete("{rule} #$ID","dns-rules-delete",$ID,"$('#$md').remove();");
}
function dnsrules_delete_confirm(){
    $ID=intval($_POST["dns-rules-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM squid_dns_rules WHERE ID=$ID");
    if($q->ok){
        echo $q->mysql_error;
        return false;
    }
    admin_tracks("Removed Proxy DNS rule $ID");
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{dns_settings}","fas fa-database","{dns_settings_proxy_explain}",
        "$page?tabs=yes","proxy-dns-cache","proxydns-progress");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
}
function dnsrules_popup(){
    $ID=intval($_GET["dns-rules-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $jsafter="dialogInstance1.close();LoadAjax('dns-lb-proxy-rules','$page?dns-rules-list=yes');";
    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM squid_dns_rules WHERE ID=$ID");
        $title="{rule} $ID";
        $btname="{apply}";

    }else{
        $ligne["enabled"]=1;
        $title="{new_rule}";
        $btname="{add}";
        $jsafter="dialogInstance1.close();LoadAjax('dns-lb-proxy-rules','$page?dns-rules-list=yes');";
    }
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],"sitename,dnsservers");
    $form[]=$tpl->field_textarea("sitename","{domains}",$ligne["sitename"],"100%","100px");
    $form[]=$tpl->field_textarea("dnsservers","{dns_servers}",$ligne["dnsservers"],"100%","75px");
    echo $tpl->form_outside($title,$form,null,$btname,$jsafter);


}
function dnsrules_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ackdoms=str_replace("\n",", ",$_POST['sitename']);
    $ackdns=str_replace("\n",", ",$_POST['dnsservers']);
    if($_POST["ID"]==0){
        $q->QUERY_SQL("INSERT INTO squid_dns_rules (sitename,dnsservers,enabled)
        VALUES ('{$_POST['sitename']}','{$_POST['dnsservers']}','{$_POST['enabled']}')");
        $atrck="Create a new Proxy DNS rule for $ackdoms using DNS servers $ackdns";
    }else{
        $q->QUERY_SQL("UPDATE squid_dns_rules SET sitename='{$_POST['sitename']}',dnsservers='{$_POST['dnsservers']}',enabled='{$_POST['dnsservers']}' WHERE ID=$ID)");
        $atrck="Update Proxy DNS rule $ID for $ackdoms using DNS servers $ackdns";
    }
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks($atrck);
    return true;
}

function dnsrules_js(){
    $ID=intval($_GET["dns-rules-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();

    if($ID==0) {
        $tpl->js_dialog1("{new_rule}","$page?dns-rules-popup=$ID");
        return true;
    }
    $tpl->js_dialog1("{rule}: $ID","$page?dns-rules-popup=$ID");
    return true;
}

function dnsrules_start(){
    return true;
}
function dnsrules_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="CREATE TABLE IF NOT EXISTS `squid_dns_rules` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`sitename` TEXT NOT NULL ,
				`dnsservers` TEXT NOT NULL ,
				`enabled` INTEGER NOT NULL 
				) ";

    $q->QUERY_SQL($sql);

    $sql="SELECT * FROM squid_dns_rules ORDER BY ID DESC";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{source}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{dns_servers}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' >{enabled}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        $md=md5(serialize($ligne));
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $sitename=$ligne["sitename"];
        $sitename=str_replace("\n",", ",$sitename );
        $ID=$ligne["ID"];
        $destination=$ligne["dnsservers"];
        $destination=str_replace("\n",", ",$destination );

        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?dns-rules-enabled=$ID')",null,"AsSquidAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?dns-rules-delete=$ID&md=$md')","AsSquidAdministrator");

        $sitename=$tpl->td_href("$sitename",null,"Loadjs('$page?dns-rules-id=$ID');");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:70%' class='left'><strong>$sitename</strong></td>";
        $html[]="<td style='width:30%' class='left' nowrap>$destination</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$enabled</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$delete</td>";
        $html[]="</tr>";


    }

    $jsafter=$tpl->framework_buildjs(
        "squid2.php?proxy-lb-reload=yes",
        "proxydns.progress",
        "proxydns.log",
        "proxydns-progress");


    $btns[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[] = "<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?dns-rules-id=0');\">
	<i class='fa fa-plus'></i> {new_rule} </label>";
    $btns[] = "<label class=\"btn btn btn-primary\" OnClick=\"$jsafter\">
	<i class='fa fa-plus'></i> {apply config} </label>";


    $btns[] = "</div>";


    $TINY_ARRAY["TITLE"]="{dns_forwarding_rules}";
    $TINY_ARRAY["ICO"]="far fa-exchange";
    $TINY_ARRAY["EXPL"]="{forward_rules_dns_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);

    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><script>NoSpinner();\n";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
    $html[]=$headsjs;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}


function search_squid(){
    $page=CurrentPageName();
     if(!isset($_SESSION["SQUID_RECORDS_SEARCH"])){$_SESSION["SQUID_RECORDS_SEARCH"]=null;}

$t=time();
$html[]="
<div class=\"row\" style='margin-top:10px'>
    <div class='ibox-content'>
        <div class=\"input-group\">
            <input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SQUID_RECORDS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
            <span class=\"input-group-btn\">
	       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
	      	</span>
        </div>
    </div>
</div>
<div class='row'><div id='table-squid-records-restart'></div>
    <div class='ibox-content'>
        <div id='table-squid-records-loader'></div>
    </div>
</div>
<script>
";

$html[]="function Search$t(e){";
$html[]="\tif(!checkEnter(e) ){return;}";
$html[]="\tss$t();";
$html[]="}

    function ss$t(){
        var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
        LoadAjax('table-squid-records-loader','$page?search-proxy='+ss+'&function=ss$t');
    }

   ss$t();


</script>";

$tpl=new template_admin();
echo $tpl->_ENGINE_parse_body($html);

}
function search_proxy(){
    $t=time();

    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsrestart=$tpl->framework_buildjs(
        "/squidclient/ipcache",
        "squid.ipcache.progress",
        "squid.ipcache.log",
        "table-squid-records-restart",
        "{$_GET["function"]}();document.getElementById('table-squid-records-restart').innerHTML='';"

    );

    $btns[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fas fa-sync'></i> {refresh} </label>
			</div>");

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=false>{TTL}</th>";
    $html[]="</tr>";


    $TRCLASS=null;
    $search="";

    if(isset($_GET["search-proxy"])) {
        $search = $_GET["search-proxy"];
        $search = str_replace( ".", "\.",$search);
        $search = str_replace( "*", ".*?",$search);
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientIPCache"));
    $Entries=0;
    $Requests=0;
    $Misses=0;
    $Hits=0;

    if($json) {
        $Entries=$json->Entries;
        $Requests=$json->Requests;
        $Misses=$json->Misses;
        $Hits=$json->Hits;

    $c=0;
    foreach ($json->Records as $hostname=>$classes) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        if(strlen($hostname)<3){
            continue;
        }
        $isok=1;
        $status="text-default";
        $flags = "<span class='label label-primary'>Positive</span>";
        $Ips=array();
        $zIps=array();
        foreach ($classes as $class) {
            $zIps[$class->IpAddr]=$class->IpAddr;
            if($class->Status){
                $status="text-success";
            }
            if($class->TTL==0){
                $flags = "<span class='label label-default'>{expired}</span>";
                $status="text-default";
            }else{
                $flags = "<span class='label label-primary'>Positive {$class->TTL}</span>";
            }
        }
        foreach ($zIps as $ip=>$none) {
            $Ips[]=$ip;
        }

        if(strlen($search)>1){
            if(!preg_match("#$search#is","$hostname".json_encode($class))){
                continue;
            }
        }

        $c++;
        if($c>250){
            break;
        }
        $md = md5(json_encode($classes));
        $ipaddrs=@implode(", ", $Ips);
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td nowrap><i class='fas fa-server'></i>&nbsp;$hostname</td>";
        $html[] = "<td style='width:1%;' nowrap><strong class='$status'>$ipaddrs</strong></td>";
        $html[] = "<td style='width:1%;' nowrap>$flags</td>";
        $html[] = "</tr>";

    }
    }
    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='5'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";


    $exp2=null;
    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
    if($DoNotUseLocalDNSCache==0){
        $exp2="<br><strong>{proxy_use_unbound_text}</strong>";
    }
    $zDNS=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/dns.conf"));
    foreach ($f as $line){
        if(!preg_match("#^dns_nameservers (.+)#",$line,$re)){continue;}
        $nameservers=explode(" ",$re[1]);
        foreach ($nameservers as $dns){
            $dns=trim($dns);
            if($dns==null){continue;}
            $zDNS[]=$dns;
        }

    }

    if(count($zDNS)>0){
        $exp2="<br><strong>{proxy_use_its_own_dns}: ".@implode(",&nbsp;",$zDNS)."</strong>";
    }
    $sub="";
    $addTitle="{cached_items}";
    if($Entries>0){
        $addTitle=$tpl->FormatNumber($Entries)." {records}";
    }
    if($Requests>0){
        $Hits=$tpl->FormatNumber($Hits);
        $Misses=$tpl->FormatNumber($Misses);
        $sub="<br>".$tpl->FormatNumber($Requests)." {requests}, {$Hits} {cached}, $Misses {not_cached}";
    }

    $TINY_ARRAY["TITLE"]="{APP_PROXY} $addTitle DNS &laquo;$search&raquo;";
    $TINY_ARRAY["ICO"]="fa-solid fa-cabinet-filing";
    $TINY_ARRAY["EXPL"]="{dns_cache_proxy_find}$exp2$sub";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);

    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } )
});
setTimeout('FootableRemoveEmpty()', 2000);
$headsjs
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    die();
}
function statistics(){
    $js4="Loadjs('fw.rrd.php?img=squiddnsq')";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $RRD_SQUID_IDNS_QUERIES=$tpl->FormatNumber(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_IDNS_QUERIES")));
    $html[]="<table style='margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:70%'>
        <H1>{DNS_QUERIES} $RRD_SQUID_IDNS_QUERIES</H1>
<div id='dns-graph-queries'></div></td>";
    $html[]="<td style='width:30%;vertical-align: top'><div id='dns-graph-queries-table' style='margin-left:20px'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><center style='margin-top:30px'>
    <a href='#' OnClick=\"javascript:$js4\">
    <img src='img/squid/squiddnsq-hourly.flat.png'></a></center></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?dns-graph-queries=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function statistics_chart_dns(){
    $page=CurrentPageName();

    $PieData=array();
    $tpl=new template_admin();
    $DNS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_IDNS_STATS")));
    foreach ($DNS["DETAILS"] as $des=>$rqs){
        $PieData[$des]=$rqs;
    }
    $highcharts=new highcharts();
    $highcharts->container="dns-graph-queries";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{DNS_QUERIES}";
    $highcharts->Title=null;
    echo $highcharts->BuildChart();
    echo "LoadAjax('dns-graph-queries-table','$page?dns-graph-queries-table=yes');";
    return true;
}
function statistics_chart_dns_table(){
    $tpl=new template_admin();
    $html[]="<table style='margin-top:60px'>";
    $DNS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RRD_SQUID_IDNS_STATS")));
    foreach ($DNS["DETAILS"] as $des=>$rqs){
        $PieData[$des]=$rqs;
        $html[]="<tr>";
        $html[]="<td style='width:70%;text-align:right' nowrap>$des:</td>";
        $html[]="<td style='width:30%;padding-left:10px'><strong>".$tpl->FormatNumber($rqs)."</strong></td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
