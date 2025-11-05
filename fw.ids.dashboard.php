<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_POST["unstall-confirm"])){uninstall_confirm();exit;}
if(isset($_GET["flat-config"])){flat_config();exit;}
if(isset($_GET["suricata-top"])){top_widgets();exit;}
if(isset($_POST["SnortRulesCode"])){Save_gen();exit;}
if(isset($_GET["suricata-status"])){suricata_status();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["pf-ring-infos"])){pf_ring_infos();exit;}
if(isset($_GET["pf-ring-popup"])){pf_ring_popup();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["reconfigure-popup"])){reconfigure_popup();exit;}
if(isset($_GET["restart-popup"])){restart_popup();exit;}

page();


function uninstall_confirm():bool{
    return admin_tracks("Uninstall IDS service..");
}

function pf_ring_infos():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog("PF Ring Info.", "$page?pf-ring-popup=yes");
}
function reconfigure_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsFirewallManager){$tpl->popup_no_privs();}
	$tpl->js_dialog6("{reconfigure_service}", "$page?reconfigure-popup=yes",650);
	
}

function uninstall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){$tpl->popup_no_privs();}
    $after=$tpl->framework_buildjs("/suricata/uninstall","suricata.progress","suricata.progress.log","progress-suricata-restart","window.location.href ='/index'");
    return $tpl->js_confirm_execute("{uninstall}","unstall-confirm","yes",$after);
}

function restart_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsFirewallManager){$tpl->popup_no_privs();}
	$tpl->js_dialog6("{restart_service}", "$page?restart-popup=yes",650);

}
function pf_ring_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();


    $jsonAVX=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/pfring/audit"));
    if(!$jsonAVX->Status){
        echo $tpl->div_error($jsonAVX->Error);
        return false;
    }


    if(!$jsonAVX->CPUHasAVX){
        echo $tpl->div_warning("{NOAVX_EXPLAIN}");
    }




    $json=json_decode($sock->REST_API("/pfring/infos"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }


	$f=explode("\n",$json->Info);
	
	$html[]="<table class='table table-hover'><tbody>";
	
	foreach ($f as $line){
		if(preg_match("#^(.+?):\s+(.+)#", $line,$re)){
			$value=trim($re[2]);
			if($value==null){continue;}
			
			if(preg_match("#(.+?):(.*)#", $value,$i)){
				$value="<strong>{$i[1]}</strong>: <i>{$i[2]}</i>";
				
			}
			
			$html[]="<tr>";
			$html[]="<td width=1% nowrap><strong>". trim($re[1])."</strong></td>";
			$html[]="<td>$value</td>";
			$html[]="</tr>";
		}
		
		
	}
	

	$f=explode("\n",@file_get_contents("/proc/net/pf_ring/info"));
    if(count($f)>2){
        $html[]="<tr>";
        $html[]="<td colspan=2><h2>{current_config}</h2></td>";
        $html[]="</tr>";
        foreach ($f as $line){
            if(!preg_match("#^(.+?):\s+(.+)#", $line,$re)){ continue;}
                $value=trim($re[2]);
                if($value==null){continue;}
                $html[]="<tr>";
                $html[]="<td width=1% nowrap><strong>". trim($re[1])."</strong></td>";
                $html[]="<td>$value</td>";
                $html[]="</tr>";

        }
    }
	
	
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}

function reconfigure_popup(){
	$t=time();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("suricata:/suricata/reconfigure",
        "suricata.progress",
        "suricata.progress.txt","&mainid=$t",
        "dialogInstance6.close()"
    );

	echo "<div id='$t'></div><script>$jsrestart;</script>";
	
}
function restart_popup(){
	$t=time();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("suricata:/suricata/restart",
    "suricata.progress",
        "suricata.progress.txt","&mainid=$t",
        "dialogInstance6.close()"
    );


	echo "<div id='$t'></div><script>$jsrestart;</script>";

}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $suricata_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_VERSION");

    $html=$tpl->page_header("{IDS} v$suricata_version",
        "fas fa-tachometer-alt","{about_ids}",
        "$page?main=yes","ids","progress-suricata-restart",false,"table-loader");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function CountOfInterfaces():int{
    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    $results=$q->QUERY_SQL("SELECT interface,threads,enable FROM suricata_interfaces WHERE enable='1'");
    if(!is_array($results)){return 0;}
    return count($results);
}
function widget_flow():string{
    $tpl=new template_admin();
    $widget_flow=$tpl->widget_style1("gray-bg",ico_nic,"{scanned_flow}",0);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/suricata/stats"));

    if(!$json->Status){
        return $tpl->widget_style1("red-bg",ico_nic,$json->Error,"{error}");
    }
    $kernel_packets=$json->Info->capture->kernel_packets;
    $bytes=$json->Info->decoder->bytes;
    if($bytes==0){
        return $tpl->widget_style1("gray-bg",ico_nic,"{scanned_flow}","{no_data}");
    }
    $BytesOrg = FormatBytes($bytes / 1024);
    $uptime = $json->Info->uptime;
    $mbPerSecond="";
    if ($uptime > 0) {
        $bytesPerSecond = $bytes / $uptime;
        $unit = "MB";
        $mbPerSecond = $bytesPerSecond / (1024 * 1024); // 1 MB = 1024 * 1024 bytes
        if ($mbPerSecond > 1024) {
            $mbPerSecond = $bytesPerSecond / (1024 * 1024 * 1024); // 1 GB = 1024 * 1024 * 1024 bytes
            $unit = "GB";
        }
        $mbPerSecond = round($mbPerSecond, 2);
        $mbPerSecond = "<small style='color:white'>($mbPerSecond$unit/s)</small>";
    }
    return $tpl->widget_style1("navy-bg", ico_nic, "{scanned_flow}", $BytesOrg . " $mbPerSecond");
}

function top_widgets_threads($json):string{
    $tpl=new template_admin();


    if(!$json->Status){
        return $tpl->widget_style1("red-bg",ico_bug,"{detected_threats}","{error}");
    }
    if($json->Alerts==0){
        return $tpl->widget_style1("gray-bg",ico_sensor,"{detected_threats}","{none}");
    }
    return $tpl->widget_style1("yellow-bg","fa-solid fa-sensor-triangle-exclamation","{detected_threats}",$tpl->FormatNumber($json->Alerts));

}
function top_widgets_rules($json):string{
    $tpl=new template_admin();

    if(!$json->Status){
        return $tpl->widget_style1("red-bg",ico_bug,"{rules}","{error}");
    }
    if($json->Info->RulesCount==0){
        return $tpl->widget_style1("gray-bg",ico_script,"{rules}","{none}");
    }
    $rules=$tpl->FormatNumber($json->Info->RulesCount);
    $Cur=$tpl->FormatNumber($json->Info->ActiveRules);
    return $tpl->widget_style1("green-bg",ico_script,"{rules}","$Cur/$rules");

}
function top_widgets():bool{
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$json->Status){
        $html[]=$tpl->div_error("{error} API||$json->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    }
    $top_widgets_rules=top_widgets_rules($json);
    $widget_threats=top_widgets_threads($json);
    $widget_flow=widget_flow();
    $html[]="<table style='width:100%;margin-top:-5px'><tbody>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_threats</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$top_widgets_rules</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_flow</td>";
    $html[]="</tr>";
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function flat_config():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $SuricataPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPurge"));
    if($SuricataPurge==0){$SuricataPurge=15;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$SuricataPurge=2;}

    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    }
    $GlobalConfig=$jsonStatus->Info;


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/pfring"));
    if(!$json->Status){
        $btn="<div style='margin:30px;text-align:right'>".$tpl->button_autnonome("{install}", "Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES')", ico_cd, "AsFirewallManager", 350, "btn-primary", 80)."</div>";
        $html[]=$tpl->div_error("{must_update_system}||$json->Error$btn");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/suricata/pfring-plugin"));
    if(!$json->Status){
        $btn="<div style='margin:30px;text-align:right'>".$tpl->button_autnonome("{install}", "Loadjs('fw.system.upgrade-software.php?product=APP_SURICATA')", ico_cd, "AsFirewallManager", 350, "btn-primary", 80)."</div>";
        $html[]=$tpl->div_error("{must_update_system}||$json->Error$btn");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/iface/list"));
    $CountOfIfaces=0;
    if($json->Status){
        $json2 = json_decode($json->Info);
        $CountOfIfaces=$json2->count;
    }
    if($CountOfIfaces==0){
        $html[]=$tpl->div_error("{error_ids_no_nic}");
    }
    $RulesCount_text="";
    $RulesCount=$GlobalConfig->RulesCount;
    if($RulesCount>0){
        $RulesCount=$tpl->FormatNumber($RulesCount);
        $RulesCount_text=" <small>{rules}: $RulesCount</small>";
    }



    $tpl->table_form_field_text("{APP_ARTICA_SURICATA}", "{version} $GlobalConfig->Version$RulesCount_text", ico_server);
    $tpl->table_form_field_js("Loadjs('$page?pf-ring-infos=yes');");
    $tpl->table_form_field_bool("{SuricataPfRing}",1,ico_performance);

    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    $results=$q->QUERY_SQL("SELECT interface,threads,enable FROM suricata_interfaces");
    $c=0;
    foreach($results as $ligne) {
        $interface = $ligne["interface"];
        $enable = intval($ligne["enable"]);
        if($enable==1){
            $c++;
        }
        $CONF[$interface] = $ligne;
    }


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/listnics"));
    $zPhysicalsInterfaces=unserialize(base64_decode($data->nics));

    foreach ($zPhysicalsInterfaces as $index=>$interface){
        $threads = 0;
        $enable =0;
        $nic=new system_nic($interface);
        $NicName=$nic->NICNAME;
        $IPADDR=$nic->IPADDR;
        if(isset( $CONF[$interface])){
            $enable=intval($CONF[$interface]["enable"]);
        }
        $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?interface-js=$interface')");
        if($enable==0){
            $tpl->table_form_field_bool($NicName." (<small>$IPADDR</small>)",0,ico_nic);
            continue;
        }
        $pkts="";$error=false;
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/iface/state/".urlencode($interface)));
        if ($json->Status) {
            $json2 = json_decode($json->Info);
            $pkts="{analyzed_flow} ".$tpl->FormatNumber($json2->pkts)."pkts";
        }else{
            $error=true;
            $pkts=$json->Error;
        }
        $tpl->table_form_field_text($NicName." (<small>$IPADDR</small>)", $pkts, ico_nic,$error);
    }

    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?statistics-js=yes')");
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();


    if($CORP_LICENSE==1) {
        $tpl->table_form_field_text("{retention_days}", $SuricataPurge, ico_hd);
    }else{
        $tpl->table_form_field_text("{retention_days}", 2, ico_hd);
    }
    if($GlobalConfig->UseQueueFailed==0){
        $tpl->table_form_field_bool("{use_queue_failed}",0,ico_bug);
    }



    $tpl=suricata_field_update($tpl,$jsonStatus);

    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?DataShieldIPv4Blocklist-js=yes')");
    if($GlobalConfig->DataShieldIPv4Blocklist==0){
        $tpl->table_form_field_bool("Data-Shield IPv4 Blocklist",$GlobalConfig->DataShieldIPv4Blocklist,ico_shield);
    }

    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?alienvault-js=yes')");
    if($GlobalConfig->Otx->Enabled==0){
        $tpl->table_form_field_bool("AlienVault",0,ico_shield);
    }else{
        $mpages=$GlobalConfig->Otx->MaxPages;
        $tpl->table_form_field_text("AlienVault","{active2} {max_feeds} $mpages",ico_shield);
    }


    $html[]= $tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function suricata_field_update($tpl,$json){
    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?update-js=yes')");
    $SuricataUpdateInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataUpdateInterval"));
    if($SuricataUpdateInterval==0){$SuricataUpdateInterval=1440;}
    if($SuricataUpdateInterval>518400){
        $tpl->table_form_field_bool("{update}", 0, ico_clock);
        return $tpl;

    }
    $GlobalConfig=$json->Info;
    $maxtime_array[420]="4 {hours}";
    $maxtime_array[480]="8 {hours}";
    $maxtime_array[720]="12 {hours}";
    $maxtime_array[1440]="1 {day}";
    $maxtime_array[2880]="2 {days}";
    $maxtime_array[10080]="1 {week}";
    $maxtime_array[43200]="1 {month}";
    $maxtime_array[518400]="1 {year}";
    $maxtime_array[518400*10]="{never}";
    $lastUpdateText="";
    $nextTime_text="";
    $lastUpdate=$GlobalConfig->LastUpdate;
    $UpdateTimeOut=$json->UpdateTimeOut;

    if($UpdateTimeOut>0) {
        if ($UpdateTimeOut > $SuricataUpdateInterval) {
            $nextTime_text = " <small>{nextcheck} {now}!</small>";
        } else {
            $nextTime = $SuricataUpdateInterval - $UpdateTimeOut;
            $nextTime_text = " <small>{nextcheck} $nextTime {minutes}</small>";
        }
    }

    if($lastUpdate>0){
        $lastUpdateText=" <small>{last_update} ".distanceOfTimeInWords($lastUpdate,time())."</small>";
    }
    $tpl->table_form_field_text("{update_each}", $maxtime_array[$SuricataUpdateInterval].$lastUpdateText.$nextTime_text, ico_clock);

    return $tpl;


}

function main():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();



    $jsReconfigure=$tpl->framework_buildjs("suricata:/suricata/reconfigure",
        "suricata.progress",
        "suricata.progress.txt","progress-suricata-restart",
        ""
    );
    $jsRestart=$tpl->framework_buildjs("suricata:/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt",
        "progress-suricata-restart"
    );

    $jsUninstallNetMonix=$tpl->framework_buildjs("/netmonix/uninstall","netmonix.progress","netmonix.progress.log","progress-suricata-restart","window.location.href ='/index'");

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:240px;vertical-align: top;'><div id='suricata-status'></div></td>";
	$html[]="<td style='width:95%;vertical-align: top;padding-left: 10px'>";
    $html[]="<div id='suricata-top'></div>";
    $html[]="<div id='suricata-config'></div>";


    $EnableNetMonix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetMonix"));
    if($EnableNetMonix==1){
        $topbuttons[]=array($jsUninstallNetMonix,ico_trash,"{uninstall} NetMonix");
    }
    $jsUninstall="Loadjs('$page?uninstall-js=yes');";

    $topbuttons[]=array($jsUninstall,ico_trash,"{uninstall}");
    $topbuttons[]=array($jsReconfigure,ico_save,"{reconfigure_service}");
    $topbuttons[]=array($jsRestart,ico_retweet,"{restart}");


    $suricata_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_VERSION");
    $TINY_ARRAY["TITLE"]="{IDS} v$suricata_version";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{about_ids}";
    $TINY_ARRAY["URL"]="ids";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $js=$tpl->RefreshInterval_js("suricata-status",$page,"suricata-status=yes");
    $html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>";
	$html[]="$js;\n$jstiny";
    $html[]="LoadAjax('suricata-config','$page?flat-config=yes');\n";
	$html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function suricata_status():bool{
    $tpl = new template_admin();
    $page=currentPageName();
    $html[]=suricata_artica_status();
    $html[]=suricata_main_status();


    $html[]= "<script>";
    $html[]= "LoadAjaxSilent('suricata-top','$page?suricata-top=yes')";
    $html[]= "LoadAjaxSilent('suricata-config','$page?flat-config=yes');";
    $html[]= "</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function suricata_artica_status():string{
    $tpl = new template_admin();

    $jsRestart = $tpl->framework_buildjs("/suricata/restart",
        "artica-suricata.progress",
        "artica-suricata.txt",
        "progress-suricata-restart"
    );

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data " . json_last_error() . "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));

    }

    if (!$json->Status) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));

    }
    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_ARTICA_SURICATA", $jsRestart));

}
function suricata_main_status():string{
    $tpl=new template_admin();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/suricata/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
    }
    if (!$json->Status) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
    }

    $jsRestart=$tpl->framework_buildjs("suricata:/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt",
        "progress-suricata-restart"
    );

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_SURICATA", $jsRestart));
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}