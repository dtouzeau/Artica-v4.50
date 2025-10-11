<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ufdbconf"])){ufdbconf_js();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["purge-js"])){purge_js();exit;}
if(isset($_GET["replicate-js"])){replicate_js();exit;}
page();



function replicate_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
	
	if($PowerDNSEnableClusterSlave==1){
        $jsrestart=$tpl->framework_buildjs("/cluster/client/download",
        "pdns.import.progress","pdns.import.progress.log",
            "progress-pdns-restart","LoadAjax('table-pdns','$page?table=yes')");
		echo $jsrestart;
		
	}
		
}


function purge_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?purge=yes");
	$purge=intval(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/pdns.purge"));
	$tpl->popup_info("$purge {items} {deleted}","LoadAjax('table-pdnsstatus','$page?table=yes');");

}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
    $html=$tpl->page_header("{APP_PDNS} $APP_PDNS_VERSION &raquo;&raquo; {service_status}",
        "fa-solid fa-database",
        "{APP_PDNS_EXPLAIN}","$page?table=yes","pdns-status","progress-pdns-restart",false,"table-pdns");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: PowerDNS status",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?status=yes");
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/pdns.status");
	$recursor_performance=null;

    $sock=new sockets();
    $data=$sock->REST_API("/pdns/status");
    $json=json_decode($data);

    $ini2=new Bs_IniHandler();
    $ini2->loadString($json->Info);

    $pdns_restart=$tpl->framework_buildjs("/pdns/restart",
        "pdns.restart.progress","pdns.restart.log","progress-pdns-restart","LoadAjax('table-pdnsstatus','$page?table=yes');");
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.log";
	$ARRAY["CMD"]="pdns.php?restart-recusor=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-pdnsstatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$recusrsor_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pdns-restart');";
	$PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
	
	if($PowerDNSEnableRecursor==1){
		$recursor_performance=recursor_performance();
	}else{
		$recursor_performance=pdns_performance();
	}
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td>
	<div class=\"ibox\" style='border-top:0px'>
    	<div class=\"ibox-content\" style='border-top:0px'>". $tpl->SERVICE_STATUS($ini2, "APP_PDNS",$pdns_restart)."</div>
    	<div class=\"ibox-content\" style='border-top:0px'>". $tpl->SERVICE_STATUS($ini, "APP_PDNS_CLIENT",$pdns_restart)."</div>
    	<div class=\"ibox-content\" style='border-top:0px'>". $tpl->SERVICE_STATUS($ini, "PDNS_RECURSOR",$recusrsor_restart)."</div>
	</div></td></tr>";
	
$html[]="</table></td>";

$html[]="<td style='width:99%;vertical-align:top'>";
$html[]="<table style='width:100%'>";
$html[]="<tr>";
$html[]="<td style='padding-left:10px;padding-top:20px'>$recursor_performance</td>";
$html[]="</tr>";
$html[]="</table>";
$html[]="</td>";
$html[]="</tr>";

$html[]="</table>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function import_export(){
	$q=new mysql_pdns();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$records=$q->COUNT_ROWS("records");
	$domains=$q->COUNT_ROWS("domains");
	$records=FormatNumber($records);
	$domains=FormatNumber($domains);
    $PowerDNSEnableClusterMaster=0;
    $PowerDNSEnableClusterSlave=0;
	if($HideCorporateFeatures==0){
		$PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
		$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));


		
	}

    $APP_PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    if( ($PowerDNSEnableClusterMaster==1) OR ($PowerDNSEnableClusterSlave==1) ){
        $bts[] = "<label class=\"btn btn btn-blue\" OnClick=\"Loadjs('$page?replicate-js=yes')\">
    <i class='fa-sync-alt'></i> {replicate}</label>";
    }
    $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.pdns.export.php')\">
    <i class='far fa-cloud-upload'></i> {export}</label>";
    $bts[] = "<label class=\"btn btn btn-blue\" OnClick=\"Loadjs('fw.pdns.import.php');\">
        <i class='far fa-cloud-download'></i> {import} </label>";
    $bts[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?purge-js=yes');\">
        <i class='fas fa-stop-circle'></i> {empty_cache} </label>";
    $bts[]="</div>";


    $btns=@implode("",$bts);
    $TINY_ARRAY["TITLE"]="{APP_PDNS} $APP_PDNS_VERSION &raquo;&raquo; {service_status}";
    $TINY_ARRAY["ICO"]="fa-solid fa-database";
    $TINY_ARRAY["EXPL"]="{APP_PDNS_EXPLAIN}";
    $TINY_ARRAY["URL"]="pdns-status";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html="
		<div class=\"ibox float-e-margins\" style='border-top:0px'>
                    <div class=\"ibox-title\">
                        <h5>{DNS_RECORDS}</h5>
                    </div>
                    <div class=\"ibox-content\">
                        <h1 class=\"no-margins\">$records</h1>
                        <div class=\"stat-percent font-bold text-primary\">$domains <i class=\"fa fa-cloud\"></i></div>
                        <small>{local_domains}</small>
                    </div>
                </div>
            </div>
	<script>$jstiny</script>";

return $html;
}
function pdns_performance(){
	
	$tpl=new template_admin();
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?pdns-infos=yes");
	$f=explode(",",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/pdns.infos"));
	
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^(.+?)=([0-9]+)#", $line,$re)){continue;}
		$GLOBALS["RECURSOR_INFOS"][trim($re[1])]=$re[2];
				
	}
	
	$data=trim(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/pdns.ccounts"));
	
	if(preg_match("#negative queries: ([0-9]+), queries: ([0-9]+)#", $data,$re)){
		$GLOBALS["RECURSOR_INFOS"]["negative-queries"]=$re[1];
		$GLOBALS["RECURSOR_INFOS"]["queries"]=$re[2];
		
	}
	
	
	$PowerDNSMaxCacheEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSMaxCacheEntries"));
	$PowerDNSMaxPacketCacheEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSMaxPacketCacheEntries"));
	if($PowerDNSMaxCacheEntries==0){$PowerDNSMaxCacheEntries=1000000;}
	if($PowerDNSMaxPacketCacheEntries==0){$PowerDNSMaxPacketCacheEntries=1000000;}
	
	
	$Memory_usage=$GLOBALS["RECURSOR_INFOS"]["real-memory-usage"];
	$udpQueries= $GLOBALS["RECURSOR_INFOS"]["udp-queries"];
	$udpAnswers= $GLOBALS["RECURSOR_INFOS"]["udp-answers"];
	
	$queryCacheSize=$GLOBALS["RECURSOR_INFOS"]["query-cache-size"];
	$packetCacheSize=$GLOBALS["RECURSOR_INFOS"]["packetcache-size"];
	
	$HIT = $GLOBALS["RECURSOR_INFOS"]["query-cache-hit"];
	$MISS= $GLOBALS["RECURSOR_INFOS"]["query-cache-miss"];
	
	$TOTAL=$MISS+$HIT;
	$Performance=($HIT/$TOTAL)*100;
	$Performance=round($Performance,2);
	
	$queryCacheSizePrc=($queryCacheSize/$PowerDNSMaxCacheEntries)*100;
	$queryCacheSizePrc=round($queryCacheSizePrc,2);
	
	$packetCacheSizePrc=($packetCacheSize/$PowerDNSMaxPacketCacheEntries)*100;
	$packetCacheSizePrc=round($packetCacheSizePrc,2);
	
	$HIT=FormatNumber($HIT);
	$MISS=FormatNumber($MISS);
	$TOTAL=FormatNumber($TOTAL);
	
	$queryCacheSize=FormatNumber($queryCacheSize);
	$packetCacheSize=FormatNumber($packetCacheSize);
	$PowerDNSMaxPacketCacheEntries=FormatNumber($PowerDNSMaxPacketCacheEntries);
	$PowerDNSMaxCacheEntries=FormatNumber($PowerDNSMaxCacheEntries);
	$udpQueries=FormatNumber($udpQueries);
	$udpAnswers=FormatNumber($udpAnswers);
	$import_export=import_export();
	$Memory_usage=FormatBytes($Memory_usage/1024);
	$html="
	<div class=\"col-lg-3\">
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fa fa-heart fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {memory_cache} </span>
	<h2 class=\"font-bold\">$Memory_usage</h2>
	</div>
	</div>
	</div>";
	
	$PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	if($PowerDNSEnableClusterSlave==1){
		$PowerDNSClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientDate"));
		if($PowerDNSClusterClientDate==0){
		$html=$html."<!-- -------------------------------------------------------------------------------------------------- -->
		<div class=\"widget style1 yellow-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fas fa-sync-alt fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {last_sync} </span>
		<h2 class=\"font-bold\">{nothing}</h2>
		</div>
		</div>
		</div>";
		}
		
		if($PowerDNSClusterClientDate>0){
			
			$xtime=$tpl->time_to_date($PowerDNSClusterClientDate,true);
			
			$html=$html."<!-- -------------------------------------------------------------------------------------------------- -->
			<div class=\"widget style1 navy-bg\">
			<div class=\"row\">
			<div class=\"col-xs-4\">
			<i class=\"fas fa-sync-alt fa-5x\"></i>
			</div>
			<div class=\"col-xs-8 text-right\">
			<span> {last_sync} </span>
			<h2 class=\"font-bold\">$xtime</h2>
			</div>
			</div>
			</div>";
		}
	
	}
	
	if($PowerDNSEnableClusterMaster==1){
		$PowerDNSEnableClusterMasterTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterTime"));
		if($PowerDNSEnableClusterMasterTime>0){
			$xtime=$tpl->time_to_date($PowerDNSEnableClusterMasterTime,true);
			$html=$html."<!-- -------------------------------------------------------------------------------------------------- -->
			<div class=\"widget style1 navy-bg\">
			<div class=\"row\">
			<div class=\"col-xs-4\">
			<i class=\"fas fa-file-export fa-5x\"></i>
			</div>
			<div class=\"col-xs-8 text-right\">
			<span> {last_export} </span>
			<h2 class=\"font-bold\">$xtime</h2>
			</div>
			</div>
			</div>";
		}else{
			$html=$html."<!-- -------------------------------------------------------------------------------------------------- -->
			<div class=\"widget style1 yellow-bg\">
			<div class=\"row\">
			<div class=\"col-xs-4\">
			<i class=\"fas fa-file-export fa-5x\"></i>
			</div>
			<div class=\"col-xs-8 text-right\">
			<span> {last_export} </span>
			<h2 class=\"font-bold\">{nothing}</h2>
			</div>
			</div>
			</div>";
			
		}
		
	}
	
	
	$html=$html."<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 navy-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fa fa-question fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {queries} UDP </span>
	<h2 class=\"font-bold\">$udpQueries</h2>
	</div>
	</div>
	</div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 navy-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fa fa-reply fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {answers} UDP</span>
	<h2 class=\"font-bold\">$udpAnswers</h2>
	</div>
	</div>
	</div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	</div>





	<div class=\"col-md-6\">
	<div class=\"ibox-content\" style='border-top:0px'>
	<div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div>
	<span>{performance} {$Performance}%</span>
	<small class=\"pull-right\">$HIT/$TOTAL</small>
	</div>
	<div class=\"progress progress-small\">
	<div class=\"progress-bar\" style=\"width: {$Performance}%;\"></div>
	</div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div>
	<span>{cache_entries}</span>
	<small class=\"pull-right\">$queryCacheSize/$PowerDNSMaxCacheEntries</small>
	</div>
	<div class=\"progress progress-small\">
	<div class=\"progress-bar\" style=\"width: {$queryCacheSizePrc}%;\"></div>
	</div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div>
	<span>{packets_cache_entries}</span>
	<small class=\"pull-right\">$packetCacheSize/$PowerDNSMaxPacketCacheEntries</small>
	</div>
	<div class=\"progress progress-small\">
	<div class=\"progress-bar\" style=\"width: {$packetCacheSizePrc}%;\"></div>
	</div>

	<!-- -------------------------------------------------------------------------------------------------- -->
	</div>
	</div>
	$import_export
	</div>";
	return $html;
}

function recursor_performance(){

	
	if(!isset($GLOBALS["RECURSOR_INFOS"])){
		$sock=new sockets();
		$sock->getFrameWork("pdns.php?recursor-infos=yes");
		$f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/recursor.infos"));
		foreach ($f as $line){
			$line=trim($line);
			if($line==null){continue;}
			if(!preg_match("#^(.+?)\s+([0-9]+)#", $line,$re)){continue;}
			$GLOBALS["RECURSOR_INFOS"][trim($re[1])]=$re[2];
			
		}
	}
	
	$maxCacheEntries=$GLOBALS["RECURSOR_INFOS"]["max-cache-entries"];
	$cacheEntries=$GLOBALS["RECURSOR_INFOS"]["cache-entries"];
	$cacheEntriesprc=($cacheEntries/$maxCacheEntries)*100;
	$cacheEntriesprc=round($cacheEntriesprc,2);
	$maxCacheEntries=FormatNumber($maxCacheEntries);
	$cacheEntries=FormatNumber($cacheEntries);

	$maxPacketcacheEntries=$GLOBALS["RECURSOR_INFOS"]["max-packetcache-entries"];
	$packetcacheEntries=$GLOBALS["RECURSOR_INFOS"]["packetcache-entries"];
	$packetcacheEntriesPrc=($packetcacheEntries/$maxPacketcacheEntries)*100;
	$maxPacketcacheEntries=FormatNumber($maxPacketcacheEntries);
	$packetcacheEntriesPrc=FormatNumber($packetcacheEntriesPrc);
	
	
	$allOutqueries=$GLOBALS["RECURSOR_INFOS"]["all-outqueries"];
	$questions=$GLOBALS["RECURSOR_INFOS"]["questions"];
	$cacheprc=($allOutqueries/$questions)*100;
	$cacheprc=round($cacheprc,2);
	$allOutqueries=FormatNumber($allOutqueries);
	$questions=FormatNumber($questions);
	$answers1001000=intval($GLOBALS["RECURSOR_INFOS"]["answers100-1000"])+intval($GLOBALS["RECURSOR_INFOS"]["auth4-answers100-1000"]);
	$answers10100=intval($GLOBALS["RECURSOR_INFOS"]["answers10-100"])+intval($GLOBALS["RECURSOR_INFOS"]["auth4-answers10-100"]);
	$answers01=intval($GLOBALS["RECURSOR_INFOS"]["answers0-1"])+intval($GLOBALS["RECURSOR_INFOS"]["auth4-answers0-1"]);
	$answers110=intval($GLOBALS["RECURSOR_INFOS"]["answers1-10"])+intval($GLOBALS["RECURSOR_INFOS"]["auth4-answers1-10"]);
	$answersslow=intval($GLOBALS["RECURSOR_INFOS"]["answers-slow"])+intval($GLOBALS["RECURSOR_INFOS"]["auth4-answers-slow"]);
	$answers_good=$answers10100+$answers110+$answers01;
	$answers_all=$answers_good+$answers1001000+$answersslow;
	$answer_perf=($answers_good/$answers_all)*100;
	$answer_perf=round($answer_perf,2);
	
	
	$answers10100=FormatNumber($answers10100);
	$answers1001000=FormatNumber($answers1001000);
	$answersslow=FormatNumber($answersslow);
	$answers_all=FormatNumber($answers_all);
	$answers_good=FormatNumber($answers_good);
	$import_export=import_export();
	
	$html="
<div class=\"col-lg-3\">
	<!-- -------------------------------------------------------------------------------------------------- -->
                <div class=\"widget style1 lazur-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fa fas fa-database fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {requests} </span>
                            <h2 class=\"font-bold\">$answers_all</h2>
                        </div>
                    </div>
                </div>
	<!-- -------------------------------------------------------------------------------------------------- -->  
                <div class=\"widget style1 navy-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-clock fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {answers10100} </span>
                            <h2 class=\"font-bold\">$answers10100</h2>
                        </div>
                    </div>
                </div>                
	<!-- -------------------------------------------------------------------------------------------------- -->  	
                <div class=\"widget style1 yellow-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-clock fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {answers1001000} </span>
                            <h2 class=\"font-bold\">$answers1001000</h2>
                        </div>
                    </div>
                </div>                
	<!-- -------------------------------------------------------------------------------------------------- -->  
                <div class=\"widget style1 red-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-clock fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {answersslow} </span>
                            <h2 class=\"font-bold\">$answersslow</h2>
                        </div>
                    </div>
                </div>                
	<!-- -------------------------------------------------------------------------------------------------- -->  		              
    </div>	

            
            
            
            
<div class=\"col-md-6\">
	<div class=\"ibox-content\" style='border-top:0px'>
	<div>
	<!-- -------------------------------------------------------------------------------------------------- -->
		<div>
			<span>{performance}</span>
			<small class=\"pull-right\">$answers_good/$answers_all</small>
		</div>
		<div class=\"progress progress-small\">
			<div class=\"progress-bar\" style=\"width: {$answer_perf}%;\"></div>
		</div>
	 <!-- -------------------------------------------------------------------------------------------------- -->	
		<div>
			<span>{cache_entries}</span>
			<small class=\"pull-right\">$cacheEntries/$maxCacheEntries</small>
		</div>
		<div class=\"progress progress-small\">
			<div class=\"progress-bar\" style=\"width: {$cacheEntriesprc}%;\"></div>
		</div>
	 <!-- -------------------------------------------------------------------------------------------------- -->
	<div>
		<span>{packets_cache_entries}</span>
		<small class=\"pull-right\">$packetcacheEntries/$maxPacketcacheEntries</small>
	</div>
	<div class=\"progress progress-small\">
		<div class=\"progress-bar\" style=\"width: {$packetcacheEntriesPrc}%;\"></div>
	</div>
	
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div>
		<span>{cache_performance}</span>
		<small class=\"pull-right\">$allOutqueries/$questions</small>
	</div>
	<div class=\"progress progress-small\">
		<div class=\"progress-bar\" style=\"width: {$cacheprc}%;\"></div>
	</div>
	
	<!-- -------------------------------------------------------------------------------------------------- -->	
	</div>
	</div>
	$import_export
	</div>";
	return $html;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
?>