<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}


page();

// GET /hotspot/events/download : articarest rend le journal deja compresse.
// REST_API() decode du JSON et ne porte pas de binaire : cURL direct sur le socket.
// Remplace framework/hotspot.php?download-events (gzip ecrit sous ressources/logs/web).
function download():bool{
    $ch=curl_init("http://localhost/hotspot/events/download");
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, "/usr/share/artica-postfix/bin/run/articarest.sock");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $GLOBALS["CLASS_SOCKETS"]->META_SCOPE_HEADERS());
    $data=curl_exec($ch);
    $code=intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    $ctype=(string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if(function_exists("curl_close")){curl_close($ch);}
    if($data===false || $code!=200 || strpos($ctype,"application/gzip")!==0){
        header("content-type: text/plain; charset=UTF-8");
        $json=json_decode((string)$data);
        echo (is_object($json) && isset($json->Error))?(string)$json->Error:"Unable to download the HotSpot events log (HTTP $code)";
        return false;
    }
    header('Content-type: application/gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"hotspot.log.gz\"");
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".strlen($data));
    if(ob_get_level()>0){ob_clean();}
    echo $data;
    return true;
}
// POST /hotspot/events/empty : vider le journal est une ecriture, reservee a
// l'administrateur du proxy (la page est ouverte aux roles en lecture seule).
// Remplace framework/hotspot.php?empty-events.
function empty_js():bool{
    header("content-type: application/x-javascript");
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){
        echo "alert(".json_encode("Permission denied").");\n";
        return false;
    }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hotspot/events/empty",array()));
    if(!is_object($json) || empty($json->Status)){
        $err="articarest: no answer";
        if(is_object($json) && isset($json->Error)){$err=(string)$json->Error;}
        elseif(is_object($json) && isset($json->error)){$err=(string)$json->error;}
        echo "alert(".json_encode($err).");\n";
        return false;
    }
    admin_tracks("Emptied the HotSpot events log");
    // Le nom vient de l'URL : un identifiant JS seulement, jamais du code.
    $function=(string)($_GET["function"] ?? "");
    if(preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/',$function)){echo "$function();\n";}
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
	// /system/logfile/search, identifiant "hotspot" (motif passe a grep sans shell),
	// lignes les plus recentes en premier : plus de krsort(). Remplace
	// framework/hotspot.php?events, qui montait un grep en root.
	$data=$tpl->logfile_search("hotspot",$MAIN,false);

	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$service=$tpl->_ENGINE_parse_body("{service2}");
	$today=date("Y-m-d");

	
	$html[]="

<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>$zdate</th>
        	<th style='width:1%' nowrap>PID</th>
            <th>{events}</th>
        </tr>
  	</thead>
	<tbody id='hotspotevent-rows'>
";
    $page=CurrentPageName();
	foreach ($data as $line){
		
    	if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.+)#", $line,$TR)){
            continue;}
		$c++;
		$color="black";
		// Pentest 2026-11-04 : ligne du journal hotspot (donnee externe) affichee sans encodage (XSS stockee).
		$date=req_html($TR[1] . " ".$TR[2]);
		$TIME=$TR[3];
		$PID=$TR[4];
		$LINE=req_html($TR[5]);
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
				<td style='width:1%' nowrap><span style='color:$color;'>$date $TIME</td>
				<td style='width:1%' nowrap><span style='color:$color;' style='width:1%' nowrap>$PID</td>
                <td width=99%><span style='color:$color'  >$LINE</span></td>  
               </tr>";
		
	}
	$html[]="";
	$html[]="</tbody></table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='hotspotevent-pager'></span></div></div></div>";


    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    $bts[]="<label class=\"btn btn-primary\" 
        OnClick=\"document.location.href='/$page?download=yes';\">
        <i class='fa-solid fa-download'></i> {download} </label>";

    $users=new usersMenus();
    if($users->AsSquidAdministrator){
        $bts[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?empty-js=yes&function=".req_url($_GET["function"] ?? "")."');\"><i class='fas fa-trash-alt'></i> {empty} </label>";
    }
    $bts[]="</div>";


    $TINY_ARRAY["TITLE"]="{hotspot}: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{HotSpot_text}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$jstiny
	</script>";
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"hotspotevent-rows","hotspotevent-pager");
	
	
	
}