<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}


page();

function download():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hotspot.php?download-events=yes");
    $compressfile=PROGRESS_DIR . "/hotspot.log.gz";
	$fsize=@filesize($compressfile);
	header("Content-Length: ".$fsize);
	header('Content-type: application/gzip');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"hotspot.log.gz\"");
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($compressfile);
	@unlink($compressfile);
    return true;
}
function empty_js():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hotspot.php?empty-events=yes");
    $function =$_GET["function"];
    header("content-type: application/x-javascript");
    echo "$function();\n";
    return true;
}

function ifisright():bool{
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}
    return false;
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();

    $html=$tpl->page_header("{hotspot}: {events}",ico_eye,"{HotSpot_text}",null,"hotspot-events",null,true);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{hotspot_auth}",$html);
        echo $tpl->build_firewall();
        return;
    }
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;
	$max=0;$date=null;$c=0;
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	$MAIN=$tpl->format_search_protocol($_GET["search"],false,false,false,true);
	$sock->getFrameWork("hotspot.php?events=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));

	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$service=$tpl->_ENGINE_parse_body("{service2}");
	$today=date("Y-m-d");

	
	$html[]="

<table class=\"table table-hover\" id='hotspot-event-table'>
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>$zdate</th>
        	<th style='width:1%' nowrap>PID</th>
            <th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    $page=CurrentPageName();
    $targetfile="/usr/share/artica-postfix/ressources/logs/hotspot.log.tmp";
	$data=explode("\n",@file_get_contents($targetfile));
	krsort($data);
	foreach ($data as $line){
		
    	if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.+)#", $line,$TR)){
            continue;}
		$c++;
		$color="black";
		$date=$TR[1] . " ".$TR[2];
		$TIME=$TR[3];
		$PID=$TR[4];
		$LINE=$TR[5];
		if($date==$today){$date=null;}
        $LINE=str_replace("[INFO]:","<span class='label label-info'>{info}</span>&nbsp;",$LINE);
        $LINE=str_replace("[WARNING]:","<span class='label label-warning'>{warning}</span>&nbsp;",$LINE);
        
        $LINE=str_replace("[ERROR]:","<span class='label label-danger'>{error}</span>&nbsp;",$LINE);
        $LINE=str_replace("[ERROR]","<span class='label label-danger'>{error}</span>&nbsp;",$LINE);
        $LINE=str_replace("[DEBUG]:","<span class='label label-default'>{debug}</span>&nbsp;",$LINE);
        $LINE=str_replace("[DEBUG]","<span class='label label-default'>{debug}</span>&nbsp;",$LINE);
        $LINE=str_replace("[WEB]: [SERVICE]:","<span class='label label-default'>Web service</span>&nbsp;",$LINE);
        $LINE=str_replace("[WEB]: [ACTIVEDIRECTORY]:","<span class='label label-info'>Active Directory</span>&nbsp;",$LINE);
        $LINE=str_replace("[SUCCESS]:","<span class='label label-primary'>{success}</span>&nbsp;",$LINE);
        $LINE=str_replace("[WEB]:","",$LINE);

        if(preg_match("#(.+?)\s+(.+?)\s+hotspot\.go:([0-9]+):(.*)#",$LINE,$re)){
            $LINE="<span class='label label-default'>Daemon</span>&nbsp;$re[4] (line $re[3])";
        }

		$html[]="<tr>
				<td width=1% nowrap><span style='color:$color;'>$date $TIME</td>
				<td width=1% nowrap><span style='color:$color;' width=1% nowrap>$PID</td>
                <td width=99%><span style='color:$color'  >$LINE</span></td>  
               </tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";


    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    $bts[]="<label class=\"btn btn-primary\" 
        OnClick=\"document.location.href='/$page?download=yes';\">
        <i class='fa-solid fa-download'></i> {download} </label>";

    $bts[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?empty-js=yes&function={$_GET["function"]}');\"><i class='fas fa-trash-alt'></i> {empty} </label>";
    $bts[]="</div>";


    $TINY_ARRAY["TITLE"]="{hotspot}: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{HotSpot_text}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="<div>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/hotspot.log.cmd")."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$jstiny
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}