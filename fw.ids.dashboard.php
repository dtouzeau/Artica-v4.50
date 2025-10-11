<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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



function pf_ring_infos(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("PF Ring Info.", "$page?pf-ring-popup=yes");
}
function reconfigure_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsFirewallManager){$tpl->popup_no_privs();}
	$tpl->js_dialog6("{reconfigure_service}", "$page?reconfigure-popup=yes",650);
	
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

    $json=json_decode($sock->REST_API("/suricata/pfring"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return;
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

    $jsrestart=$tpl->framework_buildjs("/suricata/reconfigure",
        "suricata.progress",
        "suricata.progress.txt","&mainid=$t",
        "dialogInstance6.close()"
    );

	echo "<div id='$t'></div><script>$jsrestart;</script>";
	
}
function restart_popup(){
	$t=time();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/suricata/restart",
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
function top_widgets():bool{
    $tpl=new template_admin();
    $COUNT_OF_SURICATA=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/COUNT_OF_SURICATA"));
    $COUNT_OF_SURICATA=FormatNumber($COUNT_OF_SURICATA);

    $COUNT_OF_SURICATA_IP_SRC=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/COUNT_OF_SURICATA_IP_SRC"));
    $COUNT_OF_SURICATA_IP_SRC=FormatNumber($COUNT_OF_SURICATA_IP_SRC);

    $widget_threats=$tpl->widget_style1("gray-bg",ico_bug,"{detected_threats}",0);
    $widget_srcIps=$tpl->widget_style1("gray-bg",ico_computer,"{src_ips}",0);
    $widget_flow=$tpl->widget_style1("gray-bg",ico_nic,"{scanned_flow}",0);

    if($COUNT_OF_SURICATA>0){
        $widget_threats=$tpl->widget_style1("yellow-bg",ico_bug,"{detected_threats}",$tpl->FormatNumber($COUNT_OF_SURICATA));
    }
    if($COUNT_OF_SURICATA_IP_SRC>0){
        $widget_srcIps=$tpl->widget_style1("navy-bg",ico_computer,"{src_ips}",$tpl->FormatNumber($COUNT_OF_SURICATA_IP_SRC));
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/stats"));

    if(!is_null($json->Info)) {
        $json2=json_decode($json->Info);
        if(!is_null($json2)) {
            if (property_exists($json2, "message")) {
                if (property_exists($json2->message, "decoder")) {
                    if ($json2->message->decoder->bytes > 1024) {
                        $BytesOrg = FormatBytes($json2->message->decoder->bytes / 1024);
                        $uptime = $json2->message->uptime;
                        if ($uptime > 0) {
                            $bytesPerSecond = $json2->message->decoder->bytes / $uptime;
                            $unit = "MB";
                            $mbPerSecond = $bytesPerSecond / (1024 * 1024); // 1 MB = 1024 * 1024 bytes
                            if ($mbPerSecond > 1024) {
                                $mbPerSecond = $bytesPerSecond / (1024 * 1024 * 1024); // 1 GB = 1024 * 1024 * 1024 bytes
                                $unit = "GB";
                            }
                            $mbPerSecond = round($mbPerSecond, 2);
                            $mbPerSecond = "<small style='color:white'>($mbPerSecond$unit/s)</small>";
                        }
                        $widget_flow = $tpl->widget_style1("navy-bg", ico_nic, "{scanned_flow}", $BytesOrg . " $mbPerSecond");
                    }
                }

            }
        }
    }

    $html[]="<table style='width:100%;margin-top:-5px'><tbody>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_threats</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_srcIps</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_flow</td>";
    $html[]="</tr>";
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function flat_config():bool{
    $tpl=new template_admin();
    $SuricataPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPurge"));
    if($SuricataPurge==0){$SuricataPurge=15;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$SuricataPurge=2;}
    $SuricataPfRing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPfRing"));

    $tpl->table_form_field_bool("{SuricataPfRing}",$SuricataPfRing,ico_performance);


    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    $results=$q->QUERY_SQL("SELECT interface,threads FROM suricata_interfaces WHERE enable=1");
    $c=0;
    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?main-js=yes')");
    if($q->ok){
        foreach($results as $ligne){
            $interface=$ligne["interface"];
            $threads=$ligne["threads"];
            $c++;
            $tpl->table_form_field_text("{interface}","$interface ({threads} $threads)",ico_nic);
        }
    }
    if($c==0){
        $tpl->table_form_field_text("{interface}","{none}",ico_nic);
    }

    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?statistics-js=yes')");
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();


    if($CORP_LICENSE==1) {
        $tpl->table_form_field_text("{retention_days}", $SuricataPurge, ico_hd);
    }else{
        $tpl->table_form_field_text("{retention_days}", 2, ico_hd);
    }


    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?update-js=yes')");
    $SuricataUpdateInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataUpdateInterval"));
    if($SuricataUpdateInterval==0){$SuricataUpdateInterval=1440;}

    $maxtime_array[1]="{never}";
    $maxtime_array[420]="4 {hours}";
    $maxtime_array[480]="8 {hours}";
    $maxtime_array[720]="12 {hours}";
    $maxtime_array[1440]="1 {day}";
    $maxtime_array[2880]="1 {days}";
    $maxtime_array[10080]="1 {week}";
    $tpl->table_form_field_text("{update_each}", $maxtime_array[$SuricataUpdateInterval], ico_clock);

    echo $tpl->table_form_compile();
    return true;

}

function main():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();



    $jsReconfigure=$tpl->framework_buildjs("/suricata/reconfigure",
        "suricata.progress",
        "suricata.progress.txt","progress-suricata-restart",
        ""
    );
    $jsRestart=$tpl->framework_buildjs("/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt",
        "progress-suricata-restart"
    );

    $jsUninstallNetMonix=$tpl->framework_buildjs("/netmonix/uninstall","netmonix.progress","netmonix.progress.log","progress-suricata-restart","window.location.href ='/index'");

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:240px;vertical-align: top;'><div id='suricata-status'></div></td>";
	$html[]="<td style='width:95%;vertical-align: top;padding-left: 10px'>";
    if(CountOfInterfaces()==0){
        $html[]=$tpl->div_error("{error_ids_no_nic}");
    }
    $html[]="<div id='suricata-top'></div>";
    $html[]="<div id='suricata-config'></div>";

    $SuricataPfRing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPfRing"));
    $EnableNetMonix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetMonix"));
    if($EnableNetMonix==1){
        $topbuttons[]=array($jsUninstallNetMonix,ico_trash,"{uninstall} NetMonix");
    }
    $topbuttons[]=array($jsReconfigure,ico_save,"{reconfigure_service}");
    $topbuttons[]=array($jsRestart,ico_retweet,"{restart}");
    if($SuricataPfRing==1){
        $topbuttons[]=array("Loadjs('$page?pf-ring-infos=yes');",ico_plug,"PF Ring Info");
    }
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
    $tpl=new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return false;
    }


        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));
        return false;
        }

    $jsRestart=$tpl->framework_buildjs("/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt",
        "progress-suricata-restart"
    );

        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_SURICATA", $jsRestart));

        $page=currentPageName();

        echo "<script>";
        echo "LoadAjaxSilent('suricata-top','$page?suricata-top=yes')";
        echo "</script>";

    return true;

	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}