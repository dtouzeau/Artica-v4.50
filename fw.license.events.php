<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["buttons"])){buttons();exit;}
if(isset($_GET["download"])){download();exit;}
js();

function buttons(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $topbuttons[]=array("document.location.href='$page?download=yes';", ico_download,"{download}");
    echo $tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
}
function download():bool{

    $TargetFile="/usr/share/artica-postfix/ressources/logs/web/LicenseDebug.tar.gz";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/license/preparelogs");
    $psize=filesize($TargetFile);
    header('Content-type: application/gz');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"LicenseDebug.tar.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$psize);
    ob_clean();
    flush();
    readfile($TargetFile);
    unlink($TargetFile);
    return true;
}

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog6("{license}: {events}","$page?popup=yes",1096);
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div id='license-events-box' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,null,null);


}

function search(){
    $stringToSearch=$_GET["search"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MAIN=$tpl->format_search_protocol($stringToSearch);
    $PFile=PROGRESS_DIR."/license.syslog.pattern";
    $RFile=PROGRESS_DIR."/license.syslog";

    $line=base64_encode(serialize($MAIN));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?license-events=$line");

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

    $data=explode("\n",@file_get_contents($RFile));
    if(count($data)>3){$_SESSION["HACLUSTER_SEARCH"]=$_GET["search"];}
    krsort($data);


    foreach ($data as $line){
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

        if(preg_match("#(fatal|Err)#i", $line)){
            $line="<span class='text-danger'>$line</span>";
        }




        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('license-events-box','$page?buttons=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}