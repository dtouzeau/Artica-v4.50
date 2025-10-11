<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["refresh-statues"])){td_status();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_POST["masqid"])){masq_save();exit;}
if(isset($_GET["new-port-js"])){new_port_js();exit;}
if(isset($_GET["delete-port-js"])){delete_port_js();exit;}
if(isset($_POST["delete-port"])){delete_port_confirm();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["new-select"])){new_select();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["NoFirewall"])){NoFirewall_js();exit;}
if(isset($_GET["counters-js"])){Counter_js();exit;}
if(isset($_GET["masq-sources"])){masq_sources();exit;}
if(isset($_GET["openvswtich-status"])){openvswtich_status();exit;}



page();

function new_port_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $switch=urlencode($_GET["new-port-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/port/add/$switch"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    return admin_tracks("Open Vswitch, create a new port for $switch");
}
function masq_sources(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["masq-sources"]);
    echo $tpl->search_block($page,"","","","masq-source-id=$ruleid");
}
function start():bool{
    $page=CurrentPageName();
    $t=time();
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width: 450px;vertical-align: top'><div id='openvswtich-status'></div></td>";
    $html[]="<td style='width: 99%;vertical-align: top'>";
    $html[]=$tpl->search_block($page,"","","","&table=yes");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $js=$tpl->RefreshInterval_js("openvswtich-status",$page,"openvswtich-status=yes");
    $html[]="<script>$js</script>";
    echo implode("\n",$html);
    return true;
}
function openvswtich_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }



    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs(
        "/openvswitch/restart",
        "openvswitch.restart.progress",
        "openvswitch.restart.log",
        "openvswtich-restart"
    );
    $jshelp="s_PopUpFull('https://wiki.articatech.com/ssh/sshproxy',1024,768,'SSH Proxy')";

    echo  $tpl->SERVICE_STATUS($bsini, "APP_OPENVSWITCH",$jsrestart);
/*
    $topbuttons[]=array($jsrestart,ico_retweet,"{reconfigure_service}");
    $topbuttons[]=array($jshelp,"fas fa-question-circle","WIKI");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_SSHPROXY}";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{APP_SSHPROXY_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshproxy";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
*/
}
function delete_port_js():bool{
    $tpl = new template_admin();
    $switch = $_GET["delete-port-js"];
    $iface = $_GET["iface"];
    return $tpl->js_confirm_delete("$switch/$iface", "delete-port", "$switch|$iface");
}
function delete_port_confirm():bool{
    $tpl = new template_admin();
    $pp = $_POST["delete-port"];
    $tr=explode("|",$pp);
    $switch = $tr[0];
    $iface = $tr[1];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/port/delete/$switch/$iface"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Open Vswitch, delete port $iface from $switch");

}
function new_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=$_GET["function"];
	return $tpl->js_dialog1("{new_virtual_switch}","$page?new-popup=yes&function=$function",650);

}

function Counter_js(){
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/iptables/stats");
    $json=json_decode($data);
    $IPTABLES_RRULES_STATUS=$json->Data->Family->ROUTER;
    $html=array();
    foreach ($IPTABLES_RRULES_STATUS as $ID=>$jclass) {
        $html[] = "if( document.getElementById('bridge-status-$ID') ){";
        if( $jclass->exists){
            $data=base64_encode($tpl->_ENGINE_parse_body("<span class='label label-primary'>{active2}</span>"));
            $html[] = "\tdocument.getElementById('bridge-status-$ID').innerHTML=base64_decode('$data');";
        }else{
            $data=base64_encode($tpl->_ENGINE_parse_body("<span class='label label-default'>{inactive2}</span>"));
            $html[] = "\tdocument.getElementById('bridge-status-$ID').innerHTML=base64_decode('$data');";
        }
        $html[] = "}";

        $BytesSZ=$jclass->bytes/1024;
        $html[]="// bytes=$jclass->bytes - $BytesSZ";
        $size= $tpl->FormatNumber(intval($jclass->pkts));
        $html[] = "if( document.getElementById('bridge-packets-$ID') ){";
        $html[] = "\tdocument.getElementById('bridge-packets-$ID').innerHTML='$size';";
        $html[] = "}";
        $Bandw= FormatBytes($BytesSZ);
        $html[] = "if( document.getElementById('bridge-size-$ID') ){";
        $html[] = "\tdocument.getElementById('bridge-size-$ID').innerHTML='$Bandw';";
        $html[] = "}";

    }
    header("content-type: application/x-javascript");
    echo @implode("\n",$html);

}
function NoFirewall_js(){
    $ID=intval($_GET["NoFirewall"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT NoFirewall FROM pnic_bridges WHERE ID=$ID");
    $NoFirewall=intval($ligne["NoFirewall"]);
    if($NoFirewall==1){$NoFirewall=0;}else{$NoFirewall=1;}
    $q->QUERY_SQL("UPDATE pnic_bridges SET NoFirewall='$NoFirewall' WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $sock=new sockets();
    $sock->REST_API("/system/network/routers/build");

}
function delete_js():bool{
	$t=time();
	$ruleid     = $_GET["delete-rule-js"];
    $md=$_GET["md"];
    $tpl        = new template_admin();
	$q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ruleid'");
    $NicFrom    = $ligne["nic_from"];
    $NiCTo      = $ligne["nic_to"];
    $RouterName="{$NicFrom}2{$NiCTo}";
    return $tpl->js_confirm_delete("{delete}: {connector} $RouterName N.$ruleid $NicFrom --> $NiCTo","delete-remove",$ruleid,"$('#$md').remove()");

}
function delete_remove():bool{
	$ruleid=$_POST["delete-remove"];
	$eth=$_POST["eth"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $ligne      = $q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ruleid'");
    $NicFrom    = $ligne["nic_from"];
    $NiCTo      = $ligne["nic_to"];
    $RouterName="{$NicFrom}2{$NiCTo}";

    $q->QUERY_SQL("DELETE FROM iptables_main WHERE `eth` = '$RouterName'");
    if(!$q->ok){echo $q->mysql_error;return false;}
	
	if(!$q->QUERY_SQL("DELETE FROM pnic_bridges WHERE ID='$ruleid'")){echo $q->mysql_error;return false;}

	$results=$q->QUERY_SQL("SELECT ID FROM iptables_main WHERE eth='$eth'");
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$q->QUERY_SQL("DELETE FROM iptables_main WHERE ID='$ID'","artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return false;}
		
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("DELETE FROM firewallfilter_sqacllinks WHERE aclid='$ID'");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return false;}
		
	}
    $sock=new sockets();
    $sock->REST_API("/system/network/routers/build");
    return admin_tracks("Delete Network router $RouterName id:$ID");

}
function td_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/metrics"));

    $switches=explode(",",$_GET["refresh-statues"]);
    $js=array();
    foreach ($switches as $SwitchName){
        $status=base64_encode($tpl->_ENGINE_parse_body(td_switch_status($SwitchName,$json)));
        list($pkts,$bytes)=td_switch_rxtx($SwitchName,$json);
        $td_switch_interfaces=base64_encode($tpl->_ENGINE_parse_body(td_switch_interfaces($SwitchName,$json)));

        $js[]="if( document.getElementById('$SwitchName-status') ){";
        $js[]="document.getElementById('$SwitchName-status').innerHTML=base64_decode('$status');";
        $js[]="}";
        $js[]="if( document.getElementById('$SwitchName-rx') ){";
        $js[]="document.getElementById('$SwitchName-rx').innerHTML='$pkts';";
        $js[]="}";
        $js[]="if( document.getElementById('$SwitchName-tx') ){";
        $js[]="document.getElementById('$SwitchName-tx').innerHTML='$bytes';";
        $js[]="}";
        $js[]="if( document.getElementById('$SwitchName-interfaces') ){";
        $js[]="document.getElementById('$SwitchName-interfaces').innerHTML=base64_decode('$td_switch_interfaces');";
        $js[]="}";

    }
    header("content-type: application/x-javascript");
    echo implode("\n",$js);

}
function td_switch_rxtx($switch,$json):array{
    $tpl=new template_admin();
    if(!$json->Status){
        return array("-","-");
    }
    if(!property_exists($json,"vswitchs")){
        return array("-","-");
    }
    foreach ($json->vswitchs->switchs as $Name=>$vswitch){
        if($Name<>$switch){
            continue;
        }
        $rx=$tpl->FormatNumber($vswitch->metrics->packetCount);
        $tx=FormatBytes($vswitch->metrics->byteCount/1024);
        return array("$rx","$tx");

    }
    return array("-","-");
}
function td_switch_interfaces($switch,$json):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!$json->Status){
        return "";
    }
    $ico=ico_nic;
    if(!property_exists($json,"vswitchs")){
        return "";
    }
    $html=array();
    foreach ($json->vswitchs->switchs as $Name=>$vswitch){
        if($Name<>$switch){
            continue;
        }
        $html[]="<table>";
        foreach ($vswitch->ports as $ports){
            if($ports->name==$switch){
                continue;
            }

            foreach ($ports->interfaces as $xname=>$ifaces){
                $portName=$ifaces->name;
                if($portName==$switch){
                    continue;
                }
               // var_dump($ifaces);

                $adminState=$ifaces->adminState;
                $linkState=$ifaces->linkState;
                $addr=$ifaces->addr;
                $cdir=$ifaces->cdir;
                $state="<span class='label label-primary'>{active2}</span>";
                if($linkState<>"up"){
                    $state="<span class='label label-default'>{inactive2}</span>";
                }

                $addrs="";
                if(strlen($addr)>3){
                    $addrs="$addr/$cdir";
                }

                $InterfaceName=$tpl->td_href($portName,"","Loadjs('fw.network.interfaces.php?nic-config-js=$portName&md=');");
                $delete=$tpl->icon_delete("Loadjs('$page?delete-port-js=$switch&iface=$portName');");
                if(preg_match("#h0$#",$portName)){
                    $delete="";
                    $InterfaceName=$portName;
                }
                if(!preg_match("#h([0-9]+)$#",$portName,$re)){
                    $Index=intval($re[1]);
                    if($Index==0){
                        $InterfaceName=$portName;
                    }
                    $delete="";
                }


                $html[]="<tr>";
                $html[]="<td style='width:1%;padding-top:5px'>$state</td>";
                $html[]="<td style='width:99%;padding-top:5px;padding-left:5px' nowrap><i class='$ico'></i>&nbsp;<strong>$InterfaceName</strong></td>";
                $html[]="<td style='width:1%;padding-top:5px;padding-left:5px;padding-right:5px'>$addrs</td>";
                $rx_bytes=FormatBytes($ifaces->metrics->rx_bytes/1024);
                $tx_bytes=FormatBytes($ifaces->metrics->tx_bytes/1024);
                $html[]="<td style='width:1%;padding-top:5px;padding-left:5px;padding-right:5px'>RX:$rx_bytes</td>";
                $html[]="<td style='width:1%;padding-top:5px;padding-left:5px;border-left: 2px solid #cccccc'>TX:$tx_bytes</td>";
                $html[]="<td style='width:1%;padding-left: 10px'>$delete</td>";
                $html[]="</tr>\n";
            }

        }
        $html[]="</table>";
    }
return @implode("",$html);
}
function td_switch_status($switch,$json):string{
    if(!$json->Status){
        return "<span class='label label-danger'>$json->Error</span>";
    }
    if(!property_exists($json,"vswitchs")){
        return "<span class='label label-danger'>{error} #2</span>";
    }

    foreach ($json->vswitchs->switchs as $Name=>$vswitch){
        if($Name<>$switch){
            continue;
        }
        return "<span class='label label-primary'>{active2}</span>";

    }
    return "<span class='label label-default'>{inactive2}</span>";
}

function masq_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $ID = intval($_POST["masqid"]);
    $nic_from = $_POST["nic_from"];
    $nic_to = $_POST["nic_to"];
    $enabled = $_POST["enabled"];
    $rulename = $_POST["rulename"];
    $OnlyMASQ = 1;
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $zMD5 = md5("$OnlyMASQ$nic_from$nic_to");

    if ($ID == 0) {
        $admtrack = "Create new masquerade rule $nic_from to $nic_to";
        $sql = "INSERT INTO pnic_bridges (zMD5,nic_from,nic_to,enabled,STP,DenyDHCP,masquerading,masquerading_invert,NoFirewall,policy,jlog,rulename,OnlyMASQ) 
	VALUES ('$zMD5','$nic_from','$nic_to','$enabled','0','0','0','0','0','0','0','$rulename',$OnlyMASQ)";

    } else {
        $admtrack = "Update masquerade rule $nic_from to $nic_to";
        $sql = "UPDATE pnic_bridges SET 
                `nic_from`            = '$nic_from',
                `nic_to`              = '$nic_to',
                `enabled`             = '$enabled',
                `OnlyMASQ`            = '$OnlyMASQ',
                `rulename`            = '$rulename'     
                WHERE   ID            =  $ID";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error)."$sql";return false;}


    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routers/build");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error("Decoding data ".json_last_error()."<br>".$json->Error);
        return false;

    }

    if (!$json->Status) {
        echo $tpl->post_error($tpl->_ENGINE_parse_body($json->Error));
        return false;
    }

    return admin_tracks($admtrack);
}
function new_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $BootstrapDialog="";
    $function=$_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT Interface,IPADDR,NETMASK,NICNAME FROM nics WHERE virtualbridge=0");
    if(!$q->ok){
        echo $tpl->div_warning($q->mysql_error);
        return false;
    }
    $tpl->table_form_section("","{new_virtual_switch_explain}");

    foreach ($results as $index=>$ligne) {
        $nic=$ligne["Interface"];
        $topbuttons[] = array("Loadjs('$page?new-select=$nic&function=$function');", ico_arrow_right, "{select}");
        $text=$nic." ".$ligne["IPADDR"]."/".$ligne["NETMASK"];
        $tpl->table_form_field_info($ligne["NICNAME"],"<small>$text</small>",ico_nic,$topbuttons);

    }
    echo $tpl->table_form_compile();
    return true;

}
function new_select(){
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $interface=$_GET["new-select"];
    $q->QUERY_SQL("UPDATE nics SET virtualbridge=1 WHERE Interface='$interface'");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "dialogInstance1.close();\n";
    echo "$function();";
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{APP_OPENVSWITCH}",
        "fas fa-bezier-curve",
        "{APP_OPENVSWITCH_EXPLAIN}",
        "$page?start=yes",
        "virtual-switches",
        "openvswtich-restart",false,
        "table-routers-loader"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {interfaces_connectors}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $function=$_GET["function"];
	$token=null;
    $t=time();
	$th_sort        = " class='text-capitalize' data-type='text'";
	$packets_from=$tpl->_ENGINE_parse_body("{packets_from}");
	$should_be_forwarded_to=$tpl->_ENGINE_parse_body("{should_be_forwarded_to}");
	$masquerading=$tpl->_ENGINE_parse_body("{masquerading}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));


    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT Interface,IPADDR,NETMASK,NICNAME FROM nics WHERE virtualbridge=1");
    if(!$q->ok){
        echo $tpl->div_warning($q->mysql_error);
        return false;
    }
    $html[]="<table id='table-main-table-openvswtiches' class='table table-stripped'>";
 	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th  class='text-capitalize'>{virtual_switch}</th>";
    $html[]="<th  class='text-capitalize'>{status}</th>";
    $html[]="<th  class='text-capitalize'>{interfaces}</th>";
    $html[]="<th  class='text-capitalize'>&nbsp;</th>";
    $html[]="<th  class='text-capitalize' style='text-align:right'>{packets}</th>";
    $html[]="<th  class='text-capitalize' style='text-align:right' nowrap>{bandwidth}</th>";
	$html[]="<th data-sortable=false>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="$function()";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $icoS="fas fa-bezier-curve";
	$TRCLASS=null;
    $jsSwitchs=array();
    foreach ($results as $index=>$ligne){

        $nic=$ligne["Interface"];
        $nicName=$ligne["NICNAME"];
        $SwitchName="switch$nic";
        $text_class="";
        $color="black";
        $icoNic=ico_nic;
        $topbuttons=array();
	    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md             = md5(serialize($ligne));
		$t1perc         = "style='vertical-align:top !important;color:$color;with:1%' nowrap";
        $t2perc         = "style='vertical-align:top !important;color:$color;with:1%;text-align:right;font-size:18px' nowrap";
		$js             = "Loadjs('$page?ruleid-js={$ligne["ID"]}&function=$function',true);";
        $delete         = $tpl->icon_delete("Loadjs('$page?delete-rule-js={$ligne["ID"]}&md=$md')");
        $topbuttons[] = array("Loadjs('$page?new-port-js=$SwitchName')", ico_plus, "{new_interface}");

        $bts=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));

        $jsSwitchs[]=$SwitchName;
    	$html[]= "<tr class='$TRCLASS' id='$md'>";
		$html[]= "<td $t1perc><i class='$icoS'></i>&nbsp;<span style='font-size:16px'>$SwitchName</span></td>";
        $html[]= "<td $t1perc><span id='$SwitchName-status'>...</span></td>";
        $html[]= "<td style='width:99%;vertical-align: top !important;font-size:16px'><i class='$icoNic'></i>&nbsp;$nicName ($nic)<div id='$SwitchName-interfaces' style='margin-top:5px;margin-left:10px;width:10%;font-size:14px'></div></td>";

        $html[]= "<td $t1perc>$bts</td>";
        $html[]="<td $t2perc><span id='$SwitchName-rx'></span></td>";
        $html[]="<td $t2perc><span id='$SwitchName-tx'></span></td>";
		$html[]= "<td $t1perc>$delete</td>";
		$html[]= "</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='11'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $add="Loadjs('$page?new-js&function=$function')";
    $zswitchs=urlencode(@implode(",",$jsSwitchs));
    $refresh=$tpl->RefreshInterval_Loadjs("table-main-table-openvswtiches",$page,"refresh-statues=$zswitchs");
    $topbuttons[] = array($add, ico_plus, "{new_virtual_switch}");
    $TINY_ARRAY["TITLE"]="{APP_OPENVSWITCH}";
    $TINY_ARRAY["ICO"]="fas fa-bezier-curve";
    $TINY_ARRAY["EXPL"]="{APP_OPENVSWITCH_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="
	<script>
	$refresh
	$headsjs
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."

</script>";

			echo $tpl->_ENGINE_parse_body($html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}