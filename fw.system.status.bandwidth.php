<?php
	include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["BandwithCalculationSchedule"])){save();exit;}
	if(isset($_GET["latest-picture"])){latest_picture();exit;}
	if(isset($_GET["bandwidth-line"])){bandwidth_line();exit;}
    if(isset($_GET["upload-line"])){upload_line();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{bandwidth}", "$page?popup=yes");
	
}



function latest_picture(){
    $ligne=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/latest"));
    $DATA=base64_decode($ligne["picture"]);
    $fsize = strlen("$DATA");
    header("Content-type: image/png");
    header("Content-Disposition: attachment; filename=\"".time().".png\";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".$fsize);
    echo $DATA;
    ob_clean();
    flush();
    return;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $BandwithCalculationSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationSchedule"));
    $BandwithCalculationInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationInterface"));
    $BandwithCalculationLocalProxy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationLocalProxy"));
    $BandwithCalculationServerID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationServerID"));
    if($BandwithCalculationSchedule==0){$BandwithCalculationSchedule=30;}
    $ServerList=file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/speedtest-servers.json");
    $showServerList=false;

    if (strlen($ServerList)>10 ){
        $showServerList=true;
        $servers=json_decode($ServerList,TRUE);
        foreach($servers["servers"] as $key => $value){
            $SLIST[$value["id"]]=mb_strimwidth($value["name"],0
                ,30,"...")." / ".$value["location"]." / ".$value["country"];
        }

    }


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/speedtests.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/speedtests.log";
    $ARRAY["CMD"]="speedtests.php?install=yes";
    $ARRAY["TITLE"]="{apply_parameters}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=bandwidth-progress');";


    $Array[30]="30 {minutes}";
    for($i=1;$i<49;$i++){
        if($i<10){ $Array[$i]="$i {hour}";continue;}
        $Array[$i]="$i {hours}";
    }
    $form[]=$tpl->field_checkbox("BandwithCalculationLocalProxy","{use_local_proxy}",$BandwithCalculationLocalProxy);
    $form[]=$tpl->field_array_hash($Array,"BandwithCalculationSchedule","nonull:{every}",$BandwithCalculationSchedule);
    $form[]=$tpl->field_interfaces("BandwithCalculationInterface","{outgoing_interface}",$BandwithCalculationInterface);
    if($showServerList){
        $form[]=$tpl->field_array_hash($SLIST,"BandwithCalculationServerID","{server} id",$BandwithCalculationServerID);
    }

    $forms=$tpl->form_outside("{parameters}",$form,null,"{apply}",$jsrestart,"AsSystemAdministrator",true);


    $ligne=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/latest"));
	$html[]="<H2>{latest_scan} (".$tpl->time_to_date($ligne["ztime"],true).")</H2>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:350px' valign='top'><img style='width:350px' src='$page?latest-picture=yes'></td>";
    $html[]="<td style='padding-left: 15px' valign='top'>";
    $html[]="<div id='bandwidth-progress'></div>";
    $html[]=$forms;
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><div id='bandwidth-line'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><div id='upload-line'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?bandwidth-line=yes');";
    $html[]="Loadjs('$page?upload-line=yes');";
    $html[]="</script>";

	echo $tpl->_ENGINE_parse_body($html);
}

function bandwidth_line(){
    $xtime=$_GET["chart-line-time"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $download_text=$tpl->javascript_parse_text("{download}");
    $upload_text=$tpl->javascript_parse_text("{upload}");

    $results=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/timeline"));


    foreach ($results as $index=>$ligne){
        $time = $ligne["ztime"];
        $download = $ligne["download"];

        $xdata[]=date("M-d H:i",$time);
        $ydata[]=$download;
    }



    $title="{bandwidth} {download} (MB)";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="bandwidth-line";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);
    echo $highcharts->BuildChart();
;



}
function upload_line(){
    $xtime=$_GET["chart-line-time"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $download_text=$tpl->javascript_parse_text("{download}");
    $upload_text=$tpl->javascript_parse_text("{upload}");

    $results=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/timeline"));


    foreach ($results as $index=>$ligne){
        $time = $ligne["ztime"];
        $download = $ligne["upload"];

        $xdata[]=date("M-d H:i",$time);
        $ydata[]=$download;
    }



    $title="{bandwidth} {$upload_text} (MB)";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="upload-line";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);
    echo $highcharts->BuildChart();
}

function save(){

    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}