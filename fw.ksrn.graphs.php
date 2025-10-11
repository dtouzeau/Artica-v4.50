<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["KRSN_DEBUG"])){Save();exit;}
if(isset($_GET["ksrn-status"])){status();exit;}
if(isset($_GET["ksrn-lines"])){ksrn_lines();exit;}
if(isset($_GET["ksrn-pie-providers"])){pie_provider();exit;}
if(isset($_GET["ksrn-pie-catz"])){pie_categories();exit;}
if(isset($_GET["main-table"])){main_table();exit;}

if(isset($_GET["ksrn-table-catz"])){table_categories();exit;}
if(isset($_GET["category-details-js"])){details_category_js();exit;}
if(isset($_GET["category-details-table"])){details_category_table();exit;}

if(isset($_GET["ksrn-table-providers"])){table_provider();exit;}
if(isset($_GET["providers-details-js"])){details_provider_js();exit;}
if(isset($_GET["providers-details-table"])){details_provider_table();exit;}
if(isset($_GET["ksrn-requests-reset"])){ksrn_requests_reset();exit;}
if(isset($_GET["ksrn-detected-reset"])){ksrn_detected_reset();exit;}





page();

function details_provider_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $provider=$_GET["providers-details-js"];
    $sdate=$_GET["sdate"];
    $tpl->js_dialog1("$provider","$page?providers-details-table=$provider&sdate=$sdate");
}
function details_category_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $category_id=$_GET["category-details-js"];
    $sdate=$_GET["sdate"];
    $catz=new mysql_catz();
    $catename=$catz->CategoryIntToStr($category_id);
    $tpl->js_dialog1("{category} $catename","$page?category-details-table=$category_id&sdate=$sdate");
}

function ksrn_requests_reset(){
    $lib=new lib_memcached();
    $lib->Delkey("KSRN_REQUESTS");
    $page=CurrentPageName();
    $lib->saveKey("KSRN_REQUESTS","0");
    echo "LoadAjax('table-loader-ksrn-status','$page?table=yes');";
}
function ksrn_detected_reset() {
    $lib=new lib_memcached();
    $lib->Delkey("KSRN_DETECTED");
    $page=CurrentPageName();
    $lib->saveKey("KSRN_DETECTED","0");
    echo "LoadAjax('table-loader-ksrn-status','$page?table=yes');";
}

function page(){
	$page   = CurrentPageName();
	$tpl    = new template_admin();
    $libmem = new lib_memcached();
    $q      = new postgres_sql();
    $q->CREATE_KSRN();

	$KRSN_VERSION=trim($libmem->getKey("KSRN_VERSION"));
	VERBOSE("KSRN_VERSION = [$KRSN_VERSION]",__LINE__);
	if($KRSN_VERSION<>null){$KRSN_VERSION=" v$KRSN_VERSION";}


	$html[]="
    
	<div class=\"row border-bottom white-bg dashboard-header\">
	<table style='width:100%'>
	    <tr>
	        <td valign='top' style='padding-right: 10px'><i class='fa-8x fas fa-shield-virus'></i></td>
	        <td valign='top'><div class=\"col-sm-12\"><h1 class=ng-binding>{KSRN}$KRSN_VERSION</h1><p>{KSRN_EXPLAIN}</p></div></td>
        </tr>
	
    </table>
		
	</div>
	<div class='row'>
	<div id='progress-ksrn-restart'></div>";
$html[]="</div><div class='row'><div class='ibox-content'>";
	$html[]="
	<div id='table-loader-ksrn-status'></div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/the-shields');
	LoadAjax('table-loader-ksrn-status','$page?table=yes');
	
	</script>";

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,@implode("\n",$html));echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){


	$tpl=new template_admin();
	$page=CurrentPageName();
	$libmem                 = new lib_memcached();
    $KSRNEmergency          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEmergency"));
	$KRSN_DEBUG             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KRSN_DEBUG"));
	$KsrnPornEnable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnPornEnable"));
    $KsrnMixedAdultEnable   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnMixedAdultEnable"));
    $KsrnHatredEnable       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnHatredEnable"));
	$kInfos         = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}


    $KSRN_DETECTED_RESET["name"] = "{reset}";
    $KSRN_DETECTED_RESET["js"] = "Loadjs('$page?ksrn-detected-reset=yes')";

    $KSRN_ERROR=intval($libmem->getKey("KSRN_ERROR"));
    $KSRN_REQUESTS=intval($libmem->getKey("KSRN_REQUESTS"));
	$KSRN_DETECTED=intval($libmem->getKey("KSRN_DETECTED"));
    if($KSRN_DETECTED>0) {
        $KSRN_DETECTED_W = $tpl->widget_h("red", "fas fa-ban", $tpl->FormatNumber($KSRN_DETECTED), "{DETECTED_THREATS} ({websites})",$KSRN_DETECTED_RESET);
    }else{
        $KSRN_DETECTED_W= $tpl->widget_h("green", "fas fa-shield-check", 0, "{DETECTED_THREATS} ({websites})",$KSRN_DETECTED_RESET);
    }
    if($KSRN_ERROR>0){
        $KSRN_DETECTED_W = $tpl->widget_h("red", "fas fa-bug", "{error} $KSRN_ERROR", "{communication_error_server}");
    }

    $KSRN_REQUESTS_RESET["name"] = "{reset}";
    $KSRN_REQUESTS_RESET["js"] = "Loadjs('$page?ksrn-requests-reset=yes')";

    if($KSRN_REQUESTS>0) {
        $KSRN_REQUESTS_W = $tpl->widget_h("green", "fas fa-cloud-showers-heavy", $tpl->FormatNumber($KSRN_REQUESTS), "{connections_number}",$KSRN_REQUESTS_RESET);
    }else{
        $KSRN_REQUESTS_W= $tpl->widget_h("grey", "fas fa-cloud-showers-heavy", 0, "{connections_number}",$KSRN_REQUESTS_RESET);
    }



    $jsuninstall=$tpl->framework_buildjs("/theshield/uninstall","ksrn.progress","ksrn.log","progress-ksrn-restart","LoadAjax('table-loader-ksrn-status','$page?table=yes');");
    $jsinstall=$tpl->framework_buildjs("/theshield/install","ksrn.progress","ksrn.log","progress-ksrn-restart","LoadAjax('table-loader-ksrn-status','$page?table=yes');");

    $KSRNEnable=1;
    $button_disable["name"] = "{uninstall_service}";
    $button_disable["js"] = $jsuninstall;

    $button_enable["name"] = "{install_service}";
    $button_enable["js"] = $jsinstall;


    if($KSRNEnable==1) {
        if ($kInfos["enable"] == 0) {
            $KSRN_REQUESTS_LIC = $tpl->widget_h("red", "fad fa-file-certificate", "{license_error}", "{license}","minheight:150px");
        } else {
            if (intval($kInfos["expire"]) > 0) {
                VERBOSE("Expire in {$kInfos["expire"]}", __LINE__);
                $reste_days = $tpl->TimeToDays($kInfos["expire"]);
                VERBOSE("reste_days: $reste_days", __LINE__);

                if ($reste_days < 15) {
                    $color="yellow";
                    $text_expired="{expire_in}: $reste_days {days}";
                    if($reste_days==0){
                        $color="red";
                        $text_expired="{expired}";
                    }
                    $KSRN_REQUESTS_LIC = $tpl->widget_h($color,
                        "fad fa-file-certificate", "$text_expired", "{license}",$button_disable,"minheight:150px");

                } else {
                    $color = "yellow";
                    $error = "{not_paid_license}";

                    if ($kInfos["ispaid"] == 1) {
                        $color = "green";
                        $error = "{paid_license}";
                    }
                    $KSRN_REQUESTS_LIC = $tpl->widget_h($color,
                        "fad fa-file-certificate", "{expire_in}: $reste_days {days}", $error,$button_disable,"minheight:150px");

                }
            }

            if (intval($kInfos["expire"]) == 0) {
                if ($kInfos["status"] == "{gold_license}") {
                    $color = "green";
                    $KSRN_REQUESTS_LIC = $tpl->widget_h($color,
                        "fad fa-file-certificate", "{expire_in}: {never}", "{gold_license}",$button_disable);
                }
            }


        }
    }else{
        $KSRN_REQUESTS_LIC = $tpl->widget_h("grey",
            "fad fa-file-certificate", "{inactive2}","{reputation_services}",$button_enable );
    }


    $jsRestart      = restart_js();

    if($KSRNEmergency==1){
        $KSRN_DETECTED_W= $tpl->widget_h("grey", "fas fa-shield-check", "{emergency_mode}", "{DETECTED_THREATS} ({websites})");
        $KSRN_REQUESTS_W= $tpl->widget_h("grey", "fas fa-cloud-showers-heavy", "{emergency_mode}", "{graph_number_of_connections}");
    }

    $tabs=tabs();





//restart_service_each
	$html="
<table style='width:100%'>
    <tr>	
    <td style='vertical-align:top;'>
	    <table style='width:100%'>
	    <tr>
	    <td style='vertical-align:top;width:200px;padding:8px'>$KSRN_DETECTED_W</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$KSRN_REQUESTS_W</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$KSRN_REQUESTS_LIC</td>
	    </tr>
	   </table>
	</td>
	</tr>	
	<tr>
	<td style='vertical-align:top;' colspan='2'>$tabs</td>
	</tr>
	</table>
	<script>
	   
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restart_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ksrn.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ksrn.log";
    $ARRAY["CMD"]="/theshield/install";
    $ARRAY["TITLE"]="{KSRN} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrn-restart')";
}

function tabs():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{today}"]="$page?main-table=yes&sdate=today";
    $array["{yesterday}"]="$page?main-table=yes&sdate=yesterday";
    $array["{this_week}"]="$page?main-table=yes&sdate=week";
    $array["{this_month}"]="$page?main-table=yes&sdate=month";
    return $tpl->tabs_default($array);
}

function main_table():bool{
    $page=CurrentPageName();
    $date=$_GET["sdate"];
    $html=" <div id='ksrn-lines'></div>
	<table style='width:100%'>
	    <tr>
	        <td style='vertical-align:top;width:50%'><div id='ksrn-pie-providers'></div></td>
	        <td style='vertical-align:top;width:50%'><div id='ksrn-pie-catz'></div></td>
        </tr>
	        <td style='vertical-align:top;width:50%'><div id='ksrn-table-providers'></div></td>
	        <td style='vertical-align:top;width:50%'><div id='ksrn-table-catz'></div></td>  
	</table>
	<script>
	    Loadjs('$page?ksrn-lines=yes&sdate=$date');
	    Loadjs('$page?ksrn-pie-providers=yes&sdate=$date');
	    Loadjs('$page?ksrn-pie-catz=yes&sdate=$date');
	
    </script>
	
	";
    echo $html;
    return true;
}



function status(){
    $tpl            = new template_admin();
    $KSRNEmergency  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEmergency"));
    $jsRestart      = restart_js();

    if($KSRNEmergency==1){
        $btn[0]["name"]="{disable_emergency_mode}";
        $btn[0]["icon"]=ico_play;
        $btn[0]["js"]=$jsRestart;
        echo $tpl->widget_rouge("{emergency_mode}","{emergency_mode}",$btn);
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?status=yes");
    $bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/ksrn.status");
	echo $tpl->SERVICE_STATUS($bsini, "KSRN",$jsRestart);

    $krsn_src=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_SRC"));
    $krsn_dst=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_DST"));
    if($krsn_src<>$krsn_dst){
        $btn[0]["name"]="{fix_it}";
        $btn[0]["icon"]=ico_play;
        $btn[0]["js"]=$jsRestart;
        echo $tpl->widget_jaune("{need_update_ksrn}","{update2}",$btn);
    }

    $page=CurrentPageName();
    echo "<center style='margin-top:10px'>". $tpl->button_autnonome("{enable_emergency_mode}", "Loadjs('$page?emergency-enable=yes')","AsProxyMonitor", "fa fa-bell","335")."</center>";
    return true;

}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$KSRN_DAEMONS=base64_encode(serialize($_POST));

	if(intval($_POST["GoogleSafeEnable"])==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleSafeDisable",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleSafeDisable",0);
    }

	if(intval($_POST["CloudFlareSafeEnabgle"])==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CloudFlareSafeDisable",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CloudFlareSafeDisable",0);
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRSN_DEBUG",$_POST["KRSN_DEBUG"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleApiKey",$_POST["GoogleApiKey"]);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRSN_DEBUG",$_POST["KRSN_DEBUG"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRN_DAEMONS",$KSRN_DAEMONS);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KsrnPornEnable",$_POST["KsrnPornEnable"]);


}

function ksrn_lines(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $today=date("Y-m-d 00:00:00");
    $sql="SELECT * FROM ksrn_lines WHERE zdate>'$today' ORDER by zdate";



    if($_GET["sdate"]=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $sql="SELECT * FROM ksrn_lines WHERE zdate>'$yesterday' AND zdate < '$today' ORDER by zdate";
    }

    if($_GET["sdate"]=="week"){
        $Number = idate('W');
        $date_part="date_part('week', zdate)='$Number'";
        $sql="SELECT SUM(requests) as requests ,date_part('day', zdate) as zdate FROM ksrn_lines  WHERE $date_part group by date_part('day', zdate) ORDER by zdate";
        echo "//$sql\n";
    }
    if($_GET["sdate"]=="month") {
        $Number=date("m");
        $date_part = "date_part('month', zdate)='$Number'";
        $sql = "SELECT SUM(requests) as requests ,date_part('day', zdate) as zdate FROM ksrn_lines  WHERE $date_part group by date_part('day', zdate) ORDER by zdate";
        echo "//$sql\n";
    }
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
    $dayText=$tpl->_ENGINE_parse_body("{day}");
    while ($ligne = pg_fetch_assoc($results)) {
        $zdate=$ligne["zdate"];
        VERBOSE($zdate,__LINE__);
        if($_GET["sdate"]=="today" OR $_GET["sdate"]=="yesterday") {
            $stime = strtotime($zdate);
            $xdata[]=date("H:i",$stime);
        }
        if($_GET["sdate"]=="week") {
            $stime = strtotime(date("Y")."-".date("m")."-".$zdate." 00:00:00");
            $xdata[]=date("l",$stime);
        }
        if($_GET["sdate"]=="month") {
            $xdata[]="$dayText $zdate";
        }


        $ydata[]=$ligne["requests"];



    }

    $title="{requests_sent_to_reputation_cloud}";
    $highcharts=new highcharts();
    $highcharts->container="ksrn-lines";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{requests}";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->BuildChart();

}

function pie_provider(){
    $tpl        = new template_admin();
    $q          = new postgres_sql();


    $today      = date("Y-m-d 00:00:00");
    $sql        = "SELECT provider,count(*) as rqs FROM  ksrn WHERE zdate >'$today' GROUP BY provider";

    if($_GET["sdate"]=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $sql="SELECT provider,count(*) as rqs FROM  ksrn WHERE zdate>'$yesterday' AND zdate < '$today' GROUP BY provider";
    }
    if($_GET["sdate"]=="week"){
        $Number = idate('W');
        $date_part="date_part('week', zdate)='$Number'";
        $sql="SELECT provider, count(*) as rqs, date_part('week', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('week', zdate),provider ORDER by zdate";

    }
    if($_GET["sdate"]=="month"){
        echo "// This month:".date('m')."\n";
        $Number = date('m');
        $date_part="date_part('month', zdate)='$Number'";
        $sql="SELECT provider, count(*) as rqs, date_part('month', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('month', zdate),provider ORDER by zdate";
    }

    $PieData    = array();

    $results    = $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }


    while ($ligne = pg_fetch_assoc($results)) {
        $provider=$ligne["provider"];
        if($provider==null){$provider="Unknown";}
        $rqs=$ligne['rqs'];
        echo "// $provider --> $rqs\n";

        if(isset($PieData[$provider])){
            $PieData[$provider]=$PieData[$provider]+$rqs;
            continue;
        }
        $PieData[$provider]=$rqs;
    }

    $highcharts=new highcharts();
    $highcharts->container="ksrn-pie-providers";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{providers}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{reputation_engines}");
    echo $highcharts->BuildChart();
    $page=CurrentPageName();
    echo "LoadAjax('ksrn-table-providers','$page?ksrn-table-providers={$_GET["sdate"]}')";
    return true;

}

function details_category_table(){
    $tpl        = new template_admin();
    $q          = new postgres_sql();
    $today      = date("Y-m-d 00:00:00");
    $catz       = new mysql_catz();

    $sdate      = $_GET["sdate"];
    $category_id= $_GET["category-details-table"];
    $sitesql    = "statscom_websites.sitename";
    $filterCate = "AND ksrn.category='$category_id'";
    $filterdom  = ",statscom_websites WHERE (ksrn.siteid=statscom_websites.siteid) $filterCate AND";

    $sql        = "SELECT $sitesql,count(*) as rqs FROM  ksrn$filterdom zdate >'$today' GROUP BY $sitesql ORDER by rqs DESC";

    if($sdate=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $sql="SELECT $sitesql,count(*) as rqs FROM  ksrn$filterdom zdate>'$yesterday' AND zdate < '$today'
        GROUP BY $sitesql ORDER by rqs DESC";
    }
    if($sdate=="week"){
        $Number = idate('W');
        $date_part="date_part('week', zdate)='$Number'";
        $sql="SELECT $sitesql, count(*) as rqs, date_part('week', zdate) as zdate FROM ksrn$filterdom $date_part group by date_part('week', zdate),$sitesql ORDER by rqs DESC";
    }
    if($sdate=="month"){
        $Number = date('m');
        $date_part="date_part('month', zdate)='$Number'";
        $sql="SELECT $sitesql, count(*) as rqs, date_part('month', zdate) as zdate FROM ksrn$filterdom $date_part group by date_part('month', zdate),$sitesql ORDER by rqs DESC";
    }

    $results    = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->div_error($q->mysql_error);
        return false;
    }

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{sitename}</th>
        	<th nowrap>{requests}</th>
        </tr>
  	</thead>
	<tbody>
";

    $page=CurrentPageName();
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=$ligne["sitename"];
        $rqs=$tpl->FormatNumber($ligne['rqs']);


        $html[]="<tr>
				<td width=100% nowrap>$sitename</td>
				<td width=1% nowrap >$rqs</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function details_provider_table(){
    $q          = new postgres_sql();
    $tpl        = new template_admin();
    $today      = date("Y-m-d 00:00:00");
    $provider   = $_GET["providers-details-table"];


    $today      = date("Y-m-d 00:00:00");
    $sql        = "SELECT count(*) as rqs,statscom_websites.sitename FROM  ksrn,statscom_websites WHERE zdate >'$today' AND (ksrn.siteid=statscom_websites.siteid) AND ksrn.provider='$provider' GROUP BY sitename ORDER by rqs DESC";

    if($_GET["sdate"]=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $filterdate="ksrn.zdate >'$yesterday' AND ksrn.zdate < '$today'";
        $sql        = "SELECT count(*) as rqs,statscom_websites.sitename FROM  ksrn,statscom_websites WHERE $filterdate AND (ksrn.siteid=statscom_websites.siteid) AND ksrn.provider='$provider' GROUP BY sitename ORDER BY rqs DESC";
    }
    if($_GET["sdate"]=="week"){
        $Number = idate('W');
        $filterdate="date_part('week', zdate)='$Number'";
        $sql        = "SELECT count(*) as rqs,statscom_websites.sitename FROM  ksrn,statscom_websites WHERE $filterdate AND (ksrn.siteid=statscom_websites.siteid) AND ksrn.provider='$provider' GROUP BY sitename ORDER BY rqs DESC";


    }
    if($_GET["sdate"]=="month"){
        $Number = date('m');
        $filterdate="date_part('month', ksrn.zdate)='$Number'";
        $sql        = "SELECT count(*) as rqs,statscom_websites.sitename FROM  ksrn,statscom_websites WHERE $filterdate AND (ksrn.siteid=statscom_websites.siteid) AND ksrn.provider='$provider' GROUP BY sitename ORDER BY rqs DESC";
    }

    $results    = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<hr>$sql");
        return false;
    }


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{sitename}</th>
        	<th nowrap>{requests}</th>
        </tr>
  	</thead>
	<tbody>
";

    $page=CurrentPageName();
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=$ligne["sitename"];
        $rqs=$tpl->FormatNumber($ligne['rqs']);


        $html[]="<tr>
				<td width=100% nowrap>$sitename</td>
				<td width=1% nowrap >$rqs</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_provider(){
    $q          = new postgres_sql();
    $tpl        = new template_admin();
    $today      = date("Y-m-d 00:00:00");
    $sql        = "SELECT provider,count(*) as rqs FROM  ksrn WHERE zdate >'$today' GROUP BY provider";

    if($_GET["ksrn-table-providers"]=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $sql="SELECT provider,count(*) as rqs FROM ksrn WHERE zdate>'$yesterday' AND zdate < '$today' GROUP BY provider ORDER by rqs DESC";
    }
    if($_GET["ksrn-table-providers"]=="week"){
        $Number = idate('W');
        $date_part="date_part('week', zdate)='$Number'";
        $sql="SELECT provider, count(*) as rqs, date_part('week', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('week', zdate),provider ORDER by rqs DESC";

    }
    if($_GET["ksrn-table-providers"]=="month"){
        $Number = date('m');
        $date_part="date_part('month', zdate)='$Number'";
        $sql="SELECT provider, count(*) as rqs, date_part('month', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('month', zdate),provider ORDER by rqs DESC";
    }

    $results    = $q->QUERY_SQL($sql);
    if(!$q->ok){
       echo  $tpl->div_error($q->mysql_error."<hr>$sql");
        return false;
    }

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{providers} {$_GET["sdate"]}</th>
        	<th nowrap>{requests}</th>
        </tr>
  	</thead>
	<tbody>
";

    $page=CurrentPageName();
    while ($ligne = pg_fetch_assoc($results)) {
        $provider=$ligne["provider"];
        $js="Loadjs('$page?providers-details-js=$provider&sdate={$_GET["ksrn-table-providers"]}')";

        if($provider==null){$provider="Unknown";}
        $provider=$tpl->td_href("$provider","{details} $provider",$js);
        $rqs=$tpl->FormatNumber($ligne['rqs']);


        $html[]="<tr>
				<td width=100% nowrap>$provider</td>
				<td width=1% nowrap >$rqs</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_categories(){
    $tpl        = new template_admin();
    $q          = new postgres_sql();
    $today      = date("Y-m-d 00:00:00");
    $catz       = new mysql_catz();
    $sql        = "SELECT category,count(*) as rqs FROM  ksrn WHERE zdate >'$today' GROUP BY category";
    $sdate      = $_GET["ksrn-table-catz"];

    if($sdate=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $sql="SELECT category,count(*) as rqs FROM  ksrn WHERE zdate>'$yesterday' AND zdate < '$today' 
        GROUP BY category ORDER by rqs DESC";
    }
    if($sdate=="week"){
        $Number = idate('W');
        $date_part="date_part('week', zdate)='$Number'";
        $sql="SELECT category, count(*) as rqs, date_part('week', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('week', zdate),category ORDER by rqs DESC";
    }
    if($sdate=="month"){
        $Number = date('m');
        $date_part="date_part('month', zdate)='$Number'";
        $sql="SELECT category, count(*) as rqs, date_part('month', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('month', zdate),category ORDER by rqs DESC";
    }

    $results    = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->div_error($q->mysql_error);
        return false;
    }

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{category} {$_GET["sdate"]}</th>
        	<th nowrap>{requests}</th>
        </tr>
  	</thead>
	<tbody>
";

    $page=CurrentPageName();
    while ($ligne = pg_fetch_assoc($results)) {
        $category=$ligne["category"];
        $js="Loadjs('$page?category-details-js=$category&sdate=$sdate')";

        $sname=$catz->CategoryIntToStr($category);
        $provider=$tpl->td_href("$sname","{details} $sname",$js);
        $rqs=$tpl->FormatNumber($ligne['rqs']);



        $html[]="<tr>
				<td width=100% nowrap>$provider</td>
				<td width=1% nowrap >$rqs</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function pie_categories(){
    $tpl        = new template_admin();
    $q          = new postgres_sql();
    $today      = date("Y-m-d 00:00:00");
    $catz       = new mysql_catz();
    $sql        = "SELECT category,count(*) as rqs FROM  ksrn WHERE zdate >'$today' GROUP BY category";

    if($_GET["sdate"]=="yesterday"){
        $yesterday=date("Y-m-d 00:00:00",strtotime('Yesterday'));
        $sql="SELECT category,count(*) as rqs FROM  ksrn WHERE zdate>'$yesterday' AND zdate < '$today' GROUP BY category";
    }
    if($_GET["sdate"]=="week"){
        echo "// This week:".idate('W')."\n";
        $Number = idate('W');
        $date_part="date_part('week', zdate)='$Number'";
        $sql="SELECT category, count(*) as rqs, date_part('week', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('week', zdate),category ORDER by zdate";
        echo "//$sql\n";
    }
    if($_GET["sdate"]=="month"){
        echo "// This month:".date('m')."\n";
        $Number = date('m');
        $date_part="date_part('month', zdate)='$Number'";
        $sql="SELECT category, count(*) as rqs, date_part('month', zdate) as zdate FROM ksrn WHERE $date_part group by date_part('month', zdate),category ORDER by zdate";
        echo "//$sql\n";
    }


    $results    = $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }

    $PieData    = array();


    while ($ligne = pg_fetch_assoc($results)) {
        $category   = $catz->CategoryIntToStr($ligne["category"]);
        $rqs=$ligne['rqs'];

        if(isset($PieData[$category])){
            $PieData[$category]=$PieData[$category]+$rqs;
            continue;
        }

        $PieData[$category]=$rqs;

    }

    $highcharts=new highcharts();
    $highcharts->container="ksrn-pie-catz";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{categories}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{categories}");
    echo $highcharts->BuildChart();
    $page=CurrentPageName();
    echo "\n";
    echo "LoadAjax('ksrn-table-catz','$page?ksrn-table-catz={$_GET["sdate"]}')";
    return true;
}
