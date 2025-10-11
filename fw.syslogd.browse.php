<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


if(isset($_GET["progress"])){GetProgress();exit;}
if(isset($_POST["RsyslogInterface"])){save();exit;}
if(isset($_GET["search-section"])){search_section();exit;}
if(isset($_GET["search-section-form"])){search_section_form();exit;}
if(isset($_GET["search-time"])){search_time();exit;}
if(isset($_GET["search-hosts"])){search_hosts();exit;}
if(isset($_GET["search-rows"])){search_rows();exit;}
if(isset($_GET["search-results"])){search_results();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["logs-sink-install"])){log_sink_install();exit;}
if(isset($_GET["logs-sink-uninstall"])){log_sink_uninstall();exit;}
if(isset($_GET["Tiny"])){Tiny();exit;}
if(isset($_GET["fname"])){download_fname();exit;}
if(isset($_GET["fsource"])){download_fsource();exit;}
if(isset($_GET["refeshindex"])){refeshindex();exit;}
if(isset($_GET["all-table-js"])){all_table_js();exit;}
if(isset($_GET["all-table-list"])){all_table_list();exit;}
page();

function status(){
    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    print_r($SyslogSinkStatus);
}

function all_table_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $hostname=null;
    if(!$users->AsProxyMonitor){return false;}
    if(isset($_GET["hostname"])){
        $hostname=base64_decode($_GET["hostname"]);
    }
    $hostname=urlencode($hostname);
    $tpl->js_dialog2("Logs:{storage} $hostname","$page?all-table-list=yes&hostname=$hostname",650);
    return true;
}

function GetProgress():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fname=$_GET["fname"];
    $shfile=PROGRESS_DIR."/$fname.sh";
    if(!is_file($shfile)){
        echo "// $shfile no such file";
        return false;
    }
    $prc=intval(file_get_contents($shfile));
    if($prc>110){
        echo "// $shfile 110%\n";
        return false;
    }
    if($prc==100){
        echo "if( document.getElementById('$fname') ) { document.getElementById('$fname').innerHTML='';}\n";
        @unlink($shfile);
        echo "zSearchSyslog2()\n";
        echo "// $shfile 100%\n";
        return false;
    }
    if($prc<5){$prc=5;}
    $html[]="<div class=\"progress progress-bar-default\">";
    $html[]="   <div style=\"width: $prc%\" aria-valuemax=\"600\" ";
    $html[]="aria-valuemin=\"0\" aria-valuenow=\"$prc\" role=\"progressbar\" class=\"progress-bar\">";
    $html[]="        <span class=\"sr-only\">$prc% {searching}</span>";
    $html[]="    </div>";
    $html[]="</div>";
    $ff=base64_encode($tpl->_ENGINE_parse_body($html));
    echo "// $shfile $prc%\n";
    echo "if( document.getElementById('$fname') ) { 
     document.getElementById('$fname').innerHTML=base64_decode('$ff');
    }";
return true;

}



function all_table_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsProxyMonitor){return false;}
    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    $hostname_search=$_GET["hostname"];
    $t=time();


    $html[]="<div id='logsink-searcher'></div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{address}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;



    foreach ($SyslogSinkStatus as $hostname=>$array){
        if($hostname_search<>null){
            if(strtolower($hostname)<>strtolower($hostname_search)){continue;}
        }
        $text_class=null;
        if(!isset($array["LOGS"])){continue;}
        $Subarray=unserialize($array["LOGS"]);
        foreach ($Subarray as $line){
            $tb=explode("|",$line);
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $size=$tb[1];
            $PATH=$tb[2];
            $date="0000-00-00";
            $ipaddr="0.0.0.0";
            if(preg_match("#([0-9\-]+)_([0-9\.]+).gz$#",$PATH,$re)){
                $date=$re[1];
                $ipaddr=$re[2];

            }
            $size=formatBytes($size/1024);
            $fname=base64_encode($PATH);
            $hostname_text=$tpl->td_href($hostname,"{download}","document.location.href='/$page?fsource=$fname&h=$hostname';");
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\"><strong>$hostname_text</strong></td>";
        $html[]="<td class=\"$text_class\">$ipaddr</a></td>";
        $html[]="<td class=\"$text_class\" width=1% nowrap>{$date}</a></td>";
        $html[]="<td class=\"$text_class\" width=1% nowrap><strong>$size</strong></td>";
        $html[]="</tr>";

    }

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
return true;

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{logs_sink}",
        ico_history,"{browse_events}","$page?search-section=yes","logs-sink",
        "progress-syslod-restart",false,"table-loader-syslod-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{logs_sink}",$html);
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
function download_fsource():bool{
    $fname=base64_decode($_GET["fsource"]);
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}
    $hostname=$_GET["h"];
    $fname=str_replace($LogSinkWorkDir,"",$fname);
    $fname=str_replace("..","",$fname);
    $fname=str_replace("..","",$fname);
    $fname="$LogSinkWorkDir$fname";

    $fname2=basename($fname);
    if($GLOBALS["VERBOSE"]){VERBOSE("$fname2 --- $fname",__LINE__);}


    if($GLOBALS["VERBOSE"]){return false;}
    header('Content-type: application/gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$hostname-$fname2\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = @filesize($fname);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($fname);
    return true;

}
function download_fname():bool{
    $fname=$_GET["fname"];
    $tmpfile=PROGRESS_DIR."/$fname.log";
    header('Content-type: '."application/text");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$fname.log\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = @filesize($tmpfile);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tmpfile);
    return true;
}
function search_section():bool{
    $page=CurrentPageName();
    $html="<div id='search-section-form'></div>
        <script>
            LoadAjax('search-section-form','$page?search-section-form=yes');
        </script>

        ";
    echo $html;
    return true;
}
function search_section_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!isset($_SESSION["LOGSINK"])){
        $_SESSION["LOGSINK"]=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOGSINK_SEARCH"));
    }
    if(!isset( $_SESSION["LOGSINK"]["ROWS"])){ $_SESSION["LOGSINK"]["ROWS"]=200;}

    $f[]="<input type='hidden' id='choose-host' value=''>";
    $f[]="<input type='hidden' id='choose-date' value=''>";
    $f[]="<input type='hidden' id='choose-rows' value='{$_SESSION["LOGSINK"]["ROWS"]}'>";
    $f[]="<div class=\"form-group\" style='margin-top:20px'>";
    $f[]="       <div class=\"input-group m-b\">";
    $f[]="            <div class=\"input-group-btn\">";
    $f[]="                <button data-toggle=\"dropdown\" class=\"btn btn-white dropdown-toggle\" type=\"button\">";
    $f[]="                    {hosts} <span class=\"caret\"></span>";
    $f[]="                </button>";
    $f[]="                <ul class=\"dropdown-menu\" id='search-hosts'> </ul>";
    $f[]="           </div>";
    $f[]="            <div class=\"input-group-btn\">";
    $f[]="                <button data-toggle=\"dropdown\" class=\"btn btn-white dropdown-toggle\" type=\"button\">";
    $f[]="                        {time} <span class=\"caret\"></span>";
    $f[]="                 </button>";
    $f[]="                <ul class=\"dropdown-menu\" id='search-time'> </ul>";
    $f[]="            </div>";
    $f[]="            <input type='text' class='form-control' id='Search-text'";
    $f[]="                OnKeyPress=\"javascript:zSearchSyslog(event);\" value=\"{$_SESSION["LOGSINK"]["SEARCH"]}\">";
    $f[]="            <div class=\"input-group-btn\">";
    $f[]="                <button data-toggle=\"dropdown\" class=\"btn btn-white dropdown-toggle\" ";
    $f[]="                type=\"button\" aria-expanded=\"false\">";
    $f[]="                    <span id='rows-label'></span>&nbsp;{rows} <span class=\"caret\"></span>";
    $f[]="                </button>";
    $f[]="                <ul class=\"dropdown-menu pull-right\" id='search-rows'></ul>";
    $f[]="            </div>";
    $f[]="      </div>";
    $f[]="</div>";
    $f[]="<div id='search-results' style='margin-top:20px'></div>";
    $f[]="<script>";
    $f[]="LoadAjaxSilent('search-time','$page?search-time=yes')";
    $f[]="LoadAjaxSilent('search-hosts','$page?search-hosts=yes')";
    $f[]="LoadAjaxSilent('search-rows','$page?search-rows=yes')";
    $f[]="function SearchSyslogDate(timeint){";
    $f[]="\tdocument.getElementById('choose-date').value=timeint;";
    $f[]="\tTinyObject();";
    $f[]="}";
    $f[]="function SearchSyslogTime(host){";
    $f[]="\tif(host.length<2){host='all';}";
    $f[]="\tdocument.getElementById('choose-host').value=host;";
    $f[]="\tLoadAjaxSilent('search-time','$page?search-time=yes&host='+host)";
    $f[]="\tTinyObject();";
    $f[]="}";
    $f[]="function SearchSyslogRows(rows){";
    $f[]="\tif(rows<200){rows=200;}";
    $f[]="\tdocument.getElementById('choose-rows').value=rows;";
    $f[]="\tdocument.getElementById('rows-label').innerHTML=rows;";
    $f[]="\tTinyObject();";
    $f[]="}";

    $f[]="function zSearchSyslog2(){";
    $f[]="\tvar host=document.getElementById('choose-host').value";
    $f[]="\tvar stime=document.getElementById('choose-date').value";
    $f[]="\tvar stext=document.getElementById('Search-text').value";
    $f[]="\tvar rows=document.getElementById('choose-rows').value";
    $f[]="\tif(rows<200){rows=200;}";
    $f[]="\tLoadAjax('search-results','$page?search-results=yes&date='+stime+'&search='+stext+'&host='+host+'&rows='+rows)";
    $f[]="}";

    $f[]="function zSearchSyslog(e){";
    $f[]="\tif(!checkEnter(e) ){return;}";
    $f[]="\tzSearchSyslog2();";
    $f[]="}";



$f[]="function TinyObject(){";
$f[]="\tvar host=document.getElementById('choose-host').value";
$f[]="\tvar stime=document.getElementById('choose-date').value";
$f[]="\tvar stext=document.getElementById('Search-text').value";
$f[]="\tvar rows=document.getElementById('choose-rows').value;";
$f[]="\tif(rows<200){rows=200;}";
$f[]="\tLoadjs('$page?Tiny=yes&date='+stime+'&search='+stext+'&host='+host+'&rows='+rows)";
$f[]="}";
if(isset($_SESSION["LOGSINK"]["HOST"])){
    $f[]="SearchSyslogTime('{$_SESSION["LOGSINK"]["HOST"]}');";
}
if(isset($_SESSION["LOGSINK"]["DATE"])){
       $f[]="SearchSyslogDate('{$_SESSION["LOGSINK"]["DATE"]}');";
}
$f[]="SearchSyslogRows('".$_SESSION["LOGSINK"]["ROWS"]."');";
$f[]="zSearchSyslog2();";
$f[]="</script>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function search_time():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_GET["host"])){$_GET["host"]=null;}
    $host=$_GET["host"];
    if($host=="all"){$host=null;}
    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    $f[]="    <li><a href=\"#\" OnClick='SearchSyslogDate(\"0\")'>{all}</a></li>";

    if($host==null){
        foreach ($SyslogSinkStatus["ALL_DATES"] as $date=>$none){
            $xtime=strtotime("$date 00:00:00");
            $year=date("Y",$xtime);
            $DAYS[$xtime]=$tpl->time_to_date($xtime)." ($year)";

        }

        krsort($DAYS);
        foreach ($DAYS as $xtime=>$text){
            $f[]="    <li><a href=\"#\" OnClick='SearchSyslogDate(\"$xtime\");'>$text</a></li>";
        }
        echo $tpl->_ENGINE_parse_body($f);
        return true;

    }



    if(isset($SyslogSinkStatus[$host]["LOGS"])){
        $DAYS=array();
        $SubArray=unserialize($SyslogSinkStatus[$host]["LOGS"]);
        foreach ($SubArray as $line){
            $zz=explode("|",$line);
            if(!preg_match("#^([0-9]+)-([0-9]+)-([0-9]+)_#",$zz[0],$re)){continue;}
            $year=$re[1];
            $month=$re[2];
            $day=$re[3];
            $xtime=strtotime("$year-$month-$day 00:00:00");
            $DAYS[$xtime]=$tpl->time_to_date($xtime)." ($year)";
        }
        krsort($DAYS);
        foreach ($DAYS as $xtime=>$text){
            $f[]="    <li><a href=\"#\" OnClick='SearchSyslogDate(\"$xtime\");'>$text</a></li>";
        }

    }


    $f[]="    <li class=\"divider\"></li>";
    $f[]="    <li><a href=\"#\" OnClick='SearchSyslogDate(\"0\")'>{all}</a></li>";

    echo $tpl->_ENGINE_parse_body($f);
    return true;
}
function search_results():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $date=intval($_GET["date"]);
    $rows=intval($_GET["rows"]);
    $host=$_GET["host"];
    $host=str_replace("{","",$host);
    $host=str_replace("}","",$host);
    $stext=trim($_GET["search"]);

    if(is_numeric($stext)){
        $stext="";
    }
    if(!isset($_GET["fname"])) {
        $_GET["fname"] = md5("$date$host$stext");
    }

    if($stext==null){
        echo $tpl->div_warning("{search}||{error_must_search_pattern}");
        return true;
    }

    $fname=$_GET["fname"];
    $stextenc=urlencode($stext);
    $stext=base64_encode($stext);
    $tmpfile=PROGRESS_DIR."/$fname.log";


     if(!is_file($tmpfile)) {

         if(strlen($host)==0){
                $host="NONE";
         }
         if(strlen($stext)==0){
                $stext="NONE";
         }

         $rqr[]=urlencode($date);
         $rqr[]=urlencode($host);
         $rqr[]=urlencode($stext);
         $rqr[]=urlencode($rows);
         $rqr[]=urlencode($fname);
         $GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/logsink/searcher/".@implode("/",$rqr));


    }

    $html[]="<div id='$fname'></div>
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

    $data=@file_get_contents($tmpfile);
    $results=@explode("\n",$data);
    krsort($results);
    $COuntOfRows=count($results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        $Month="";
        $Day=0;
        $time="00:00:00";
        $Hostname="unknown";
        $program="unknown";
        $pid="-1";
        $class=null;
        if(preg_match("#^(.+?):(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#",$line,$re)){
            $srcfile=$re[1];
            $Month=$re[2];
            $Day=$re[3];
            $time=$re[4];
            $Hostname=$re[5];
            $program=$re[6];
            $pid=$re[7];
            $line=trim($re[8]);

        }
        if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#",$line,$re)){
            $Month=$re[1];
            $Day=$re[2];
            $time=$re[3];
            $Hostname=$re[4];
            $program=$re[5];
            $pid=$re[6];
            $line=trim($re[7]);

        }

        if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+EvntSLog\s+(.+)#",$line,$re)){
            $Month=$re[1];
            $Day=$re[2];
            $time=$re[3];
            $Hostname=$re[4];
            $line=trim($tpl->utf8_encode($re[5]));
            $pid="000";
            $program="-";
            if(preg_match("#PID\s+([0-9]+)#",$line,$re)){
                $pid=$re[1];
            }
            if(preg_match("#[A-Za-z]:(.+?)\.exe#", $line, $output_array)){
                $program=$output_array[1];
                $program=str_replace("\\","/",$program);
                $program=basename($program).".exe";
            }

            if(preg_match("#^(.+?)\.\s+#",$line,$re)){
                $line=str_replace("$re[1].","",$line);
                $line=trim($line);
                $line=str_replace(". ",".<br>",$line);
                $line=str_replace(": -",":<br>-",$line);
                $line="<strong>$re[1].</strong><br>$line";



            }

        }

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
    $tiny="\tLoadjs('$page?Tiny=yes&date=$date&stext=$stextenc&host=$host&rows=$rows&CountOfResults=$COuntOfRows&fname=$fname');";

    $js=$tpl->RefreshInterval_Loadjs($fname,$page,"progress=yes&fname=$fname");

    $html[]="</table>";
    $html[]="<script>";
    $html[]="document.getElementById('Search-text').disabled=false;";
    $html[]=$tiny;
    $html[]=$js;
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
    $TINY_ARRAY["TITLE"]=@implode(" ",$title);
    $TINY_ARRAY["ICO"]=ico_history;
    $TINY_ARRAY["EXPL"]="{statistics_search_explain}";
    $TINY_ARRAY["URL"]="logs-sink";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo $jstiny;
    return true;
}