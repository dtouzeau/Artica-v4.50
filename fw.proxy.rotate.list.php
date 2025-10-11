<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["proxy-search-graph"])){graph1();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["page2"])){page2();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["launch"])){launch();exit;}
if(isset($_GET["proxies-list"])){proxies_list();exit;}
if(isset($_GET["fiche-proxy-js"])){proxy_fiche_js();exit;}
if(isset($_GET["fiche-proxy"])){proxy_fiche();exit;}
if(isset($_GET["delete-proxy-js"])){proxy_delete_js();exit;}
if(isset($_POST["proxy-aclid"])){proxy_fiche_save();exit;}
if(isset($_POST["delete_rule"])){rule_delete();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["report"])){report();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_POST["run"])){run_launch();exit;}
if(isset($_GET["proxy-search-status"])){proxy_search_status();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{legal_logs} {storage}",ico_database,"{legal_logs_explain}",
        "$page?page2=yes","proxy-legal-list","progress-logrotate-restart",false,"table-loader-proxy-list");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{legal_logs}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function page2(){
    $tpl=new template_admin();
	$page=CurrentPageName();
    $html[]="<input type=hidden name=cleartimeout id='cleartimeout' value='0'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align:top'>";
    $html[]="<div id='proxy-search-graph' style='width:250px'></div>";
    $html[]="<div id='proxy-search-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]=$tpl->search_block($page,"","","","&table=yes");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("proxy-search-status",$page,"proxy-search-status=yes");
    $html[]="Loadjs('$page?proxy-search-graph=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}




function download(){
    $aclid=intval($_GET["download"]);
    $content_type="text/plain";
    $mainpath="/home/artica/squidsearchs/$aclid.log";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"search-$aclid.log\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($mainpath);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($mainpath);

}

function proxy_search_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html="";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function graph1(){
    $BackupMaxDaysDirSize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDirSize");
    if(strpos($BackupMaxDaysDirSize,":")==0) {
        return false;
    }
    $tb=explode(":",$BackupMaxDaysDirSize);+
    $DirSize=$tb[0];
    $PartSize=$tb[1];

    $tpl=new templates();
    $Free=$PartSize-$DirSize;
    $PartitionText=FormatBytes($Free);

    $MAIN["Partition"]=$Free;
    $MAIN["Directory"]=$DirSize;

    $PieData=$MAIN;
    $highcharts=new Chartjs();
    $highcharts->container="proxy-search-graph";
    $highcharts->PieDatas=$PieData;
    $highcharts->DataToSize=true;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{directory_size}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{directory_size} ".FormatBytes($DirSize)."/$PartitionText");
    echo $highcharts->Doughnut2rows();
}

function report(){
    $zmd5=$_GET["report"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $ligne=$q->mysqli_fetch_array("SELECT report FROM proxy_reports WHERE zmd5='$zmd5'");
    $report=base64_decode($ligne["report"]);
    echo $report;

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $t=time();
    $function=$_GET["function"];

    $launch_scan=$tpl->framework_buildjs("/logrotate/scan/storage",
        "logrotate.scan", "logrotate.log","progress-logrotate-restart","$function()");

    $launch_rescan=$tpl->framework_buildjs("/logrotate/scan/rebuild",
        "logrotate.scan", "logrotate.log","progress-logrotate-restart","$function()");


    $topbuttons[] = array($launch_scan,ico_refresh,"{synchronize}");
    $topbuttons[] = array($launch_rescan,ico_retweet,"{rescan_objects}");


    $TINY_ARRAY["TITLE"]="{legal_logs}: {storage}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="proxy-legal-list";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=false style='width:1%'>{date}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{to_date}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{type}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{file}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{size}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";



    $sql="SELECT * FROM proxy_time ORDER BY zdate DESC LIMIT 150";
    if (strlen($_GET["search"])>1){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM proxy_time WHERE sourcefile LIKE '$search' ORDER BY zDateTo DESC LIMIT 150";
    }

	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
            $zmd5=$ligne["zmd5"];
            $zmd5_report="";
            $ligne2=$q->mysqli_fetch_array("SELECT zmd5 FROM proxy_reports WHERE zmd5='$zmd5'");
            if(isset($ligne2["zmd5"])) { $zmd5_report = $ligne2["zmd5"]; }
            $icon_graph="";
            $md=$zmd5;
            $zdate=$ligne["zdate"];
            $zDateTo=$ligne["zDateTo"];
            $Date=$tpl->time_to_date($zdate,true);
            $zDateToStr=$tpl->time_to_date($zDateTo,true);
            $zType=$ligne["ztype"];
            $FileName=basename($ligne["sourcefile"]);
            if($zType==0){
                $TypeText="<i class='".ico_servcloud."'></i>&nbsp;Proxy";
            }
            if($zType==1){
                $TypeText="<i class='".ico_firewall."'></i>&nbsp;FortiGate";
            }
            if($zType==2){
                $TypeText="<i class='".ico_earth."'></i>&nbsp;Reverse-Proxy";
            }
            $size=FormatBytes($ligne["filesize"]/1024);
            if(strlen($zmd5_report)>5){
                $icon_graph="<i class='".ico_chart_line."' style='color:#18a689'></i>&nbsp;";
                $FileName=$tpl->td_href($FileName,"","s_PopUp('$page?report=$zmd5_report','1024','900');");
            }
			$html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td style='width:1%' nowrap>$Date</td>";
            $html[]="<td style='width:1%' nowrap>$zDateToStr</td>";
            $html[]="<td style='width:1%' nowrap>$TypeText</td>";
            $html[]="<td style='width:99%'>$icon_graph$FileName</td>";
            $html[]="<td style='width:1%' nowrap>$size</td>";
			$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
    </script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid,$tablelink="parents_sqacllinks",$returndef=true):string{

    $tt         = array();
	$q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy     = new mysql_squid_builder(true);

	$sql="SELECT
	$tablelink.gpid,
	$tablelink.zmd5,
	$tablelink.negation,
	$tablelink.zOrder,
	webfilters_sqgroups.GroupType,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID
	FROM $tablelink,webfilters_sqgroups
	WHERE $tablelink.gpid=webfilters_sqgroups.ID
	AND $tablelink.aclid=$aclid
	ORDER BY $tablelink.zorder";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return "";}

	foreach($results as $index=>$ligne) {
		$gpid=$ligne["gpid"];
		$js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
		$neg_text="{is}";
		if($ligne["negation"]==1){$neg_text="{is_not}";}
		$GroupName=$ligne["GroupName"];
		$tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-weight:bold'>$GroupName</a> <small>(".$qProxy->acl_GroupType[$ligne["GroupType"]].")</small>";
	}

	if(count($tt)>0){
		return @implode("<br>{and} ", $tt);

	}

    if(!$returndef){return "";}
	return "{all}";



}