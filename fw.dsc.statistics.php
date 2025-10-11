<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["dsc-current-users"])){current_users();exit;}
if(isset($_GET["dsc-current-requests"])){current_requests();exit;}
if(isset($_GET["dsc-top-domains"])){top_domains();exit;}
if(isset($_GET["dsc-top-domains-list"])){top_domains_list();exit;}

if(isset($_GET["dsc-top-ipaddr"])){top_ipaddr();exit;}
if(isset($_GET["dsc-top-ipaddr-list"])){top_ipaddr_list();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["purge-js"])){purge_js();exit;}
if(isset($_GET["replicate-js"])){replicate_js();exit;}
if(isset($_GET["status"])){status();exit;}

if(isset($_GET["tabs"])){tabs();exit;}

page();




function tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $array["{status}"]="$page?status=yes";
    $array["{today}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}


function page(){
	$page=CurrentPageName();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>DNS &raquo;&raquo; {statistics} &raquo;&raquo; {today}</h1>
	</div>
	</div>
		

		
	<div class='row'><div id='progress-pdns-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-pdns'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/dns-statistics');
	$.address.title('Artica: DNS Statistics');	
	LoadAjax('table-pdns','$page?tabs=yes');
	</script>";
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: DNS Statistics",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}

function status(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $ini            = new Bs_IniHandler();
    $DSC_DASHBOARD  = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSC_DASHBOARD"));
    $DSCReverseDnsLookup = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCReverseDnsLookup"));
    $RtMDays        = $DSC_DASHBOARD["TOTAL"]["ALL"]["DAYS"];
    $tables_size    = $DSC_DASHBOARD["TOTAL"]["ALL"]["SIZE"];
    $tables_size_day= $DSC_DASHBOARD["TOTAL"]["ALL"]["SIZE_DAY"];


    $DSCBlacklistDoms       = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));

    if(!is_array($DSCBlacklistDoms) or empty($DSCBlacklistDoms)){
        $DSCBlacklistDoms[".ntp.org"]=true;
        $DSCBlacklistDoms[".tld"]=true;
        $DSCBlacklistDoms[".lab"]=true;
        $DSCBlacklistDoms[".local"]=true;
        $DSCBlacklistDoms[".int"]=true;
        $DSCBlacklistDoms[".infra"]=true;

    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
    $ARRAY["CMD"]="pdns.php?restart-dsc=yes";
    $ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestartDSC="Loadjs('fw.progress.php?content=$prgress&mainid=progress-dsc-restart')";
    $DISTANCE_DATE=$DSC_DASHBOARD["TOTAL"]["ALL"]["DISTANCE"];

    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    $PDNSStatsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if($PDNSStatsRetentionTime==0){$PDNSStatsRetentionTime=5;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$PDNSStatsRetentionTime=5;}

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?dsc-status=yes");
	$ini->loadFile(PROGRESS_DIR."/dsc.status");
    $html[]="<div id='progress-dsc-restart' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px' valign='top'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td>";
    $html[]="<div class=\"ibox\">";
    $html[]="<div class=\"ibox-content\">". $tpl->SERVICE_STATUS($ini, "APP_DSC",$jsRestartDSC);
    $html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;'>";
    $html[]="<H2>{APP_POSTGRES}</H2>";
    $html[]="<table style='width:100%;margin-top:5px'>";
    $html[]="<tr>";
    $html[] = "<td style='padding:2px'>" .
        $tpl->widget_style1("navy-bg", "fas fa-alarm-clock", "{retention} ($DISTANCE_DATE)", $tpl->FormatNumber($RtMDays)."/$InfluxAdminRetentionTime {days}") .
        "</td>";
    $html[] = "<td style='padding:2px'>" .
        $tpl->widget_style1("navy-bg", "fas fa-database", "{data_size}", $tables_size) .
        "</td>";
    $html[] = "<td style='padding:2px'>" . $tpl->widget_style1("navy-bg", "fas fa-server", "{daily_volume}", "$tables_size_day/{day}")."</td>";

    $html[]="</tr>";
    $html[]="</table>";
    $CountOfDomains=count($DSCBlacklistDoms);
    $form[]=$tpl->field_checkbox("DSCReverseDnsLookup","{store_rdl}",$DSCReverseDnsLookup);
    $form[]=$tpl->field_info("DSCWHites", "{excludes}",
        array("VALUE"=>null,
            "BUTTON"=>true,
            "BUTTON_CAPTION"=>"$CountOfDomains {domains}",
            "BUTTON_JS"=>"Loadjs('fw.dsc.exclude.domains.php')"

        ),"");

    $html[]= $tpl->form_outside("{options}", $form,null,"{apply}","blur();","AsDnsAdministrator");

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$sock=new sockets();
	$results=unserialize(base64_decode($sock->GET_INFO("CURRENT_DSC_USERS")));
	
	foreach ($results as $index=>$ligne){
		$rqs=$ligne["rqs"];
		$clients=intval($ligne["clients"]);
		$zdate=date("H:i:00",$ligne["zdate"]);
		$MAIN["xdata"][]=$zdate;
		$MAIN["ydata"][]=$clients;
		
		$MAIN2["xdata"][]=$zdate;
		$MAIN2["ydata"][]=$rqs;
		
	}
	
	if(count($MAIN["xdata"])>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/dsc.UsersNumber.data", $data);
		$html[]="<div id='dsc-current-users' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?dsc-current-users=yes');";
	}

	if(count($MAIN2["xdata"])>1){
		$data=serialize($MAIN2);
		@file_put_contents(PROGRESS_DIR."/dsc.requests.data", $data);
		$html[]="<div id='dsc-current-requests' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?dsc-current-requests=yes');";
	}

	
	$results=unserialize(base64_decode($sock->GET_INFO("CURRENT_DSC_TOPDOMAINS")));
	foreach ($results as $index=>$ligne){
		$rqs=$ligne["rqs"];
		$familysite=$ligne["familysite"];
		$PieData[$familysite]=$rqs;
		//familysite
	}
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:360px' valign='top'>";
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/dsc.pie.domain.data", $data);
		$html[]="<div id='dsc-top-domains' style='with:400px;height:600px'></div>";
		$js[]="Loadjs('$page?dsc-top-domains=yes&container=dsc-top-domains&data=dsc.pie.domain.data');";
	}
	$html[]="</td>";
	$html[]="<td style='width:360px' valign='top'>";
	$html[]="<div id='dsc-top-domains-list'></div>";
	if(count($PieData)>1){$js[]="LoadAjax('dsc-top-domains-list','$page?dsc-top-domains-list=yes&data=dsc.pie.domain.data');";}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	
	$PieData=array();
	$results=unserialize(base64_decode($sock->GET_INFO("CURRENT_DSC_TOPIPS")));
	foreach ($results as $index=>$ligne){
		$rqs=$ligne["rqs"];
		$ipaddr=$ligne["ipaddr"];
		$PieData[$ipaddr]=$rqs;
		//familysite
	}
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:360px' valign='top'>";
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/dsc.pie.ipaddr.data", $data);
		$html[]="<div id='dsc-top-ipaddr' style='with:400px;height:600px'></div>";
		$js[]="Loadjs('$page?dsc-top-ipaddr=yes&container=dsc-top-ipaddr&data=dsc.pie.ipaddr.data');";
	}
	$html[]="</td>";
	$html[]="<td style='width:360px' valign='top'>";
	$html[]="<div id='dsc-top-ipaddr-list'></div>";
	if(count($PieData)>1){$js[]="LoadAjax('dsc-top-ipaddr-list','$page?dsc-top-ipaddr-list=yes&data=dsc.pie.ipaddr.data');";}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";	
	
	
	
	$html[]="<script>".@implode("\n", $js)."</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}
function current_users(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/dsc.UsersNumber.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{members}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="dsc-current-users";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=" users";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}: ');
	$highcharts->LegendSuffix=" users";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("| {members}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();

}

function current_requests(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/dsc.requests.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{requests}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="dsc-current-requests";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=" {requests}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}: ');
	$highcharts->LegendSuffix=" {requests}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("| {requests}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
}

function top_domains(){
	$tpl=new templates();
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents(PROGRESS_DIR."/{$_GET["data"]}"));
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_domains}");
	echo $highcharts->BuildChart();
}

function top_ipaddr(){
	$tpl=new templates();
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents(PROGRESS_DIR."/{$_GET["data"]}"));
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_ipaddr}");
	echo $highcharts->BuildChart();
}

function top_domains_list(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=md5(time().rand(0,6546465464));
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{domain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{requests}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$PieData=unserialize(@file_get_contents(PROGRESS_DIR."/{$_GET["data"]}"));
    $TRCLASS=null;
	foreach ($PieData as $domain=>$requests){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5($domain.$requests);
		$requests=FormatNumber($requests);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$domain</strong></td>";
		$html[]="<td width=1% nowrap>$requests</td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function top_ipaddr_list(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=md5(time().rand(0,6546465464));

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{requests}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$PieData=unserialize(@file_get_contents(PROGRESS_DIR."/{$_GET["data"]}"));

	foreach ($PieData as $domain=>$requests){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5($domain.$requests);
		$requests=FormatNumber($requests);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$domain</strong></td>";
		$html[]="<td width=1% nowrap>$requests</td>";
		$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

	echo $tpl->_ENGINE_parse_body($html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
?>