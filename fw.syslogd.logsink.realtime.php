<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.rsyslogd.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["RsyslogInterface"])){save();exit;}
if(isset($_GET["search-section"])){search_section();exit;}
if(isset($_GET["search"])){search_results();exit;}
if(isset($_GET["Tiny"])){Tiny();exit;}



page();

function status(){
    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    print_r($SyslogSinkStatus);
}


function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{logs_sink} {realtime_events_squid}",
        ico_search_in_file,"{browse_events}","$page?search-section=yes","logs-sink-realtime",
        "progress-syslod-restart",false,"table-loader-syslod-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{logs_sink} {realtime_events_squid}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function refeshindex():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $js_progress=$tpl->framework_buildjs("/syslog/logsink/scan",
        "logs-sink-refresh.progress","logs-sink-refresh.log","search-section-form",
        "LoadAjax('search-section-form','$page?search-section-form=yes');",
        "LoadAjax('search-section-form','$page?search-section-form=yes');"
    );

    echo $js_progress;
    return true;
}


function search_section():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null);
    return true;

}

function search_results():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $MAIN       = $tpl->format_search_protocol($_GET["search"],false,false,false,true);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/logs-sink.rtt.syslog";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("syslog.php?logs-sink-rtt=$line");


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th nowrap>{hostname}</th>
        	<th>{program}</th>
            <th>PID</th>
            <th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $rsyslogd=new rsyslogd_events();
    $data=@file_get_contents($tfile);
    $results=@explode("\n",$data);
    krsort($results);
    $COuntOfRows=count($results);
    foreach ($results as $line){
        $WINEVNTS=false;
        $line=trim($line);
        if($line==null){continue;}


        $main=$rsyslogd->parse_line($line);

        $Month=$main["Month"];
        $Day=$main["Day"];
        $time=$main["time"];
        $Hostname=$main["Hostname"];
        $program=$main["program"];
        $pid=$main["pid"];
        $line=$main["line"];
        $class=null;

        if(preg_match("#(No such file|Invalid|Error querying)#i",$line)){
            $class="text-warning font-bold";
        }
        if(preg_match("#(syntax error|failed to)#",$line)){
            $class="text-danger text-bold";
        }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$Month $Day $time</td>
				<td style='width:1%;' nowrap class='$class'>$Hostname</td>
				<td style='width:1%;' nowrap class='$class'>$program</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td class='$class'>$line</td>
				</tr>";
    }

    $LogSynRTMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTMaxSize"));
    if($LogSynRTMaxSize==0){$LogSynRTMaxSize=80;}

    $file_size=FormatBytes(@filesize("/var/log/logsink-rtime.log")/1024);
    $TINY_ARRAY["TITLE"]="{logs_sink} {realtime_events_squid}";
    $TINY_ARRAY["ICO"]=ico_search_in_file;
    $TINY_ARRAY["EXPL"]="{browse_events} {filesize} <strong>$file_size/{$LogSynRTMaxSize}MB</strong>";
    $TINY_ARRAY["URL"]="logs-sink-realtime";
    //$TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function search_hosts():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    $f[]="    <li><a href=\"#\" OnClick='SearchSyslogTime(\"\");'>{all}</a></li>";
    foreach ($SyslogSinkStatus as $host=>$array){
        if($host=="ALL_DATES"){continue;}
        $f[]="    <li><a href=\"#\" OnClick='SearchSyslogTime(\"$host\");'>$host</a></li>";


    }
    $f[]="    <li class=\"divider\"></li>";
    $f[]="    <li><a href=\"#\" OnClick='SearchSyslogTime(\"\");'>{all}</a></li>";

    echo $tpl->_ENGINE_parse_body($f);
    return true;
}
function search_rows():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $rows[200]=200;
    $rows[500]=500;
    $rows[1000]=1000;
    $rows[1500]=1500;
    $rows[3000]=3000;

    foreach ($rows as $rows=>$array){
        $f[]="    <li><a href=\"#\" OnClick='SearchSyslogRows(\"$rows\");'>$rows</a></li>";


    }
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function Tiny():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CountOfResults=0;
    $date=intval($_GET["date"]);
    $host=$_GET["host"];
    $stext=$_GET["stext"];
    $rows=intval($_GET["rows"]);
    $fname=$_GET["fname"];
    $title[]="{search} &laquo;$stext&raquo;";
    if($rows==0){$rows=200;}
    if(isset($_GET["CountOfResults"])){
        $CountOfResults=intval($_GET["CountOfResults"]);
    }
    $host_field=$host;
    if($host==null){$host="{all}";}
    if($host=="all"){$host="{all}";}
    $title[]="{host}: $host";

    if($date>0){
        $title[]=$tpl->time_to_date($date);
    }else{
        $title[]="{all_times}";
    }
    if($rows>0){
        if($CountOfResults>0) {
            $title[] = "<small>($CountOfResults/$rows {rows})</small>";
        }else{
            $title[] = "<small>($rows {rows})</small>";

        }
    }


    if($date>10) {
        $_SESSION["LOGSINK"]["DATE"] = $date;
    }
    $_SESSION["LOGSINK"]["SEARCH"]=$stext;
    if($host<>null) {
        $_SESSION["LOGSINK"]["HOST"] = $host_field;
    }
    $_SESSION["LOGSINK"]["ROWS"]=$rows;

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOGSINK_SEARCH",serialize($_SESSION["LOGSINK"]));
    $btn=array();
    $btn[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    if($fname<>null) {

        $btn[] = "<label class=\"btn btn btn-primary\" OnClick=\"document.location.href='/$page?fname=$fname';\">";
        $btn[] = "            <i class='fad fa-download'></i> {download}: {your_query} </label>";
    }

    $btn[] = "<label class=\"btn btn btn-blue\" OnClick=\"Loadjs('$page?all-table-js=yes');\">";
    $btn[] = "            <i class='fa-solid fa-file-zipper'></i> {storage} </label>";

        $btn[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?refeshindex=yes');\">";
        $btn[] = "            <i class='fa-solid fa-retweet'></i> {scan_dir} </label>";




    $btn[] = "</div>";
    //filesize

    return true;
}