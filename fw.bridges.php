<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["start"])){start();exit;}
if(isset($_POST["masqid"])){masq_save();exit;}
if(isset($_GET["masq-id"])){masq_id_js();exit;}
if(isset($_GET["masq-tab"])){masq_tab();exit;}
if(isset($_GET["masq-popup"])){masq_popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["NoFirewall"])){NoFirewall_js();exit;}
if(isset($_GET["counters-js"])){Counter_js();exit;}
if(isset($_GET["masq-sources"])){masq_sources();exit;}


page();

function masq_id_js():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tpl=new template_admin();
    $ruleid=intval($_GET["masq-id"]);
    $function=$_GET["function"];
    if($ruleid==0){
        $NAT_TYPE_TEXT="Masquerade {new_rule}";
    }else{
        $ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ruleid'");
        $NAT_TYPE_TEXT="Masquerade N.$ruleid {$ligne["nic_from"]} -- &raquo; {$ligne["nic_to"]}";
    }
    return $tpl->js_dialog("$NAT_TYPE_TEXT","$page?masq-tab=$ruleid&function=$function");
}
function masq_sources(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["masq-sources"]);
    echo $tpl->search_block($page,"","","","masq-source-id=$ruleid");
}
function start(){
    $page=CurrentPageName();
    $t=time();
    $tpl=new template_admin();
    $html[]="<span id='bridges-counter-$t'></span>";
    $html[]=$tpl->search_block($page,"","","","&table=yes");
    $js=$tpl->RefreshInterval_Loadjs("bridges-counter-$t",$page,"counters-js=yes");
    $html[]="<script>$js</script>";
    echo implode("\n",$html);
}
function masq_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["masq-tab"]);
    $function=$_GET["function"];
    $array["{rule}"]="$page?masq-popup=$id&function=$function";
    if($id>0){
        $array["{source_network}"]="fw.bridges.masq.sources.php?popup-main=$id&function=$function";
    }
    echo $tpl->tabs_default($array);
}
function rule_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);
    $function=$_GET["function"];

	if($ruleid==0){
		$NAT_TYPE_TEXT="{new_connector}";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ruleid'");
		$NAT_TYPE_TEXT="{connector} N.$ruleid {$ligne["nic_from"]} -- &raquo; {$ligne["nic_to"]}";
	}
	$tpl->js_dialog("$NAT_TYPE_TEXT","$page?rule-popup=$ruleid&function=$function");
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
function masq_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["masq-popup"]);
    $function=$_GET["function"];
    $BootstrapDialog="";
    if(!is_numeric($ID)){$ID=0;}
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ID'");

    if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
    if(!isset($ligne["rulename"])){$ligne["rulename"]="Masquerade packets";}
    if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
    $tpl->field_hidden("masqid", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);
    $form[]=$tpl->field_interfaces("nic_from","{masq_from}",$ligne["nic_from"],true);
    $form[]=$tpl->field_interfaces("nic_to","{masq_to}",$ligne["nic_to"],true);
    if($ID==0){$BootstrapDialog="BootstrapDialog1.close();";}

    $but="{add}";
    if($ID>0){
        $but="{apply}";
    }

    echo $tpl->form_outside("",$form,null,$but,
        "$function();$BootstrapDialog");

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
function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $BootstrapDialog="";
    $function=$_GET["function"];
	$ID=intval($_GET["rule-popup"]);
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if(!is_numeric($ID)){$ID=0;}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ID'");
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	

	foreach ($interfaces as $eth){
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	}

    $BEHA[1]="{finally_deny_all}";
    $BEHA[0]="{finally_allow_all}";

	
	$but="{add}";
	$title="{new_connector}";
	if($ID>0){
			$but="{apply}";
			$title="{connector} {$ligne["nic_from"]}2{$ligne["nic_to"]}";
		}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
    if(!is_numeric($ligne["masquerading"])){$ligne["masquerading"]=0;}
	if(!is_numeric($ligne["DenyCountries"])){$ligne["DenyCountries"]=0;}
	if($ID==0){$BootstrapDialog="BootstrapDialog1.close();";}



	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);
	$form[]=$tpl->field_interfaces("nic_from","{packets_from}",$ligne["nic_from"],true);
	$form[]=$tpl->field_interfaces("nic_to","{should_be_forwarded_to}",$ligne["nic_to"],true);
    $form[]=$tpl->field_checkbox("masquerading","{masquerading}",$ligne["masquerading"],false);

    if($FireHolEnable==1){
        $form[]=$tpl->field_array_hash($BEHA,"policy","{policy}",intval($ligne["policy"]));
        $form[]=$tpl->field_checkbox("NoFirewall","{join_interfaces_only}",$ligne["NoFirewall"],false,"{join_interfaces_only_explain}");
        $form[]=$tpl->field_checkbox("DenyDHCP","{deny_dhcp_requests}",$ligne["DenyDHCP"],false);
        $form[]=$tpl->field_checkbox("jlog","{log_all_events}",$ligne["jlog"]);
    }else{
        $tpl->field_hidden("policy",0);
        $tpl->field_hidden("masquerading_invert",$ligne["masquerading_invert"]);
        $tpl->field_hidden("NoFirewall",$ligne["NoFirewall"]);
        $tpl->field_hidden("DenyDHCP",$ligne["DenyDHCP"]);
        $tpl->field_hidden("jlog",0);

    }
    echo $tpl->form_outside($title,$form,null,$but,
			"$function();$BootstrapDialog");


}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{interfaces_connectors}",
        "fas fa-bezier-curve",
        "<i>{dashboard_router_explain}</i><br>{bridges_iptables_explain}",
        "$page?start=yes",
        "interfaces-connector",
        "progress-firehol-restart",false,
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
	$th_sort        = "data-sortable=true class='text-capitalize' data-type='text'";
	$packets_from=$tpl->_ENGINE_parse_body("{packets_from}");
	$should_be_forwarded_to=$tpl->_ENGINE_parse_body("{should_be_forwarded_to}");
	$masquerading=$tpl->_ENGINE_parse_body("{masquerading}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	$add="Loadjs('$page?ruleid-js=0$token&function=$function',true);";



    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure",
        "firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart"
    );


    if($FireHolEnable==0){
        $jsrestart=$tpl->framework_buildjs("/system/network/routers/nohupbuild",
            "routers.build.progress","squid.access.center.log","progress-firehol-restart");
    }
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/iptables/stats");
    $json=json_decode($data);
    $IPTABLES_RRULES_STATUS=$json->Data->Family->ROUTER;


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
 	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>id</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{packets}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{size}</th>";
	$html[]="<th $th_sort>$packets_from</th>";
    $html[]="<th>&nbsp;</th>";
	$html[]="<th $th_sort'>$should_be_forwarded_to</th>";
	$html[] = "<th $th_sort nowrap>{firewall}</th>";
    $html[] = "<th $th_sort nowrap>{policy}</th>";
	$html[]="<th $th_sort>$masquerading</th>";
    $html[]="<th data-sortable=false>LOG</th>";
	$html[]="<th data-sortable=false>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="$function()";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$results=$q->QUERY_SQL("SELECT * FROM pnic_bridges ORDER BY ID DESC");
	$TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $text_class     = null;
        $STATUS         = "<span class='label'>{inactive}</span>";
        $color          = "black";
        $ID             = $ligne["ID"];
        $nic_from       = $ligne["nic_from"];
        $nic_to         = $ligne["nic_to"];
        $jlog           = intval($ligne["jlog"]);
        $NoFirewall     = intval($ligne["NoFirewall"]);
        $policy         = intval($ligne["policy"]);
        $OnlyMASQ=intval($ligne["OnlyMASQ"]);


        $masquerading_ico   = null;
        $pks            = 0;
        $size           = 0;
        $jlog_ico       = "&nbsp;";
        $DenyDHCP="";
        $md=md5(serialize($ligne));
        $enabled=intval($ligne["enabled"]);
        if($enabled==0){
            $STATUS             ="<span class='label label-default'>{disabled}</span>";
            $text_class=" text-muted";
            $color="#8a8a8a";

        }
	    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        if (property_exists($IPTABLES_RRULES_STATUS,$ID) AND $enabled==1){
            $STATUS             ="<span class='label label-primary'>{active2}</span>";
            $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS->{$ID}->pkts));
            $size               = intval($IPTABLES_RRULES_STATUS->{$ID}->bytes);
            if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
        }
        if($NoFirewall==1){$NoFirewall=0;}else{$NoFirewall=1;}
        $NoFirewallico  =   $tpl->icon_check($NoFirewall,"Loadjs('$page?NoFirewall={$ligne["ID"]}')");
	
		$nic=new system_nic($nic_from);
        if($nic->NETMASK=="0.0.0.0"){$nic->NETMASK="";}
        $nic->NICNAME=str_replace("Interface $nic_from","",$nic->NICNAME);
        if($nic->NICNAME<>null){
            $nicTitle[]="$nic_from: ".$nic->NICNAME;
        }
        $nicTitle[]=$nic->IPADDR;
        if(strlen($nic->NETMASK)>1){
            $nicTitle[]="netmask:$nic->NETMASK";
        }
		$nic_from_text=@implode("&nbsp;/&nbsp;",$nicTitle);

	
		$nic=new system_nic($nic_to);
		$nic_to_text="$nic->NICNAME ($nic_to) $nic->IPADDR/$nic->NETMASK ";

        $BEHA[1]="<span class='label label-danger'>{deny}</span>";
        $BEHA[0]="<span class='label label-primary'>{allow}</span>";

        $policy_ico=$BEHA[$policy];

        if($ligne["masquerading"]==1 OR $OnlyMASQ==1){
			$masquerading_ico="<i class='fas fa-check'></i>";
		}
		if($jlog==1){
            $jlog_ico="<i class='fas fa-check'></i>";
        }
        if($ligne["DenyDHCP"]==1){
            $DenyDHCP="&nbsp;<span class='label label-danger'>{deny} DHCP</span>";
        }

		if($NoFirewall==0){
            $masquerading_ico="&nbsp;";
            $jlog_ico="&nbsp;";
            $policy_ico="<span class='label'>{none}</span>";
        }
        $tclass         = "class='$text_class' style='vertical-align:middle;color:$color'";
		$t1perc         = "class='center' width='1%' style='vertical-align:middle;color:$color' nowrap";
		$js             = "Loadjs('$page?ruleid-js={$ligne["ID"]}&function=$function',true);";
        if($OnlyMASQ==1){
            $js             = "Loadjs('$page?masq-id={$ligne["ID"]}&function=$function',true);";
        }
        $delete         = $tpl->icon_delete("Loadjs('$page?delete-rule-js={$ligne["ID"]}&md=$md')");
        $nic_from_text  = $tpl->td_href($nic_from_text,null,$js);
        $nic_to_text    = $tpl->td_href($nic_to_text,null,$js);

        if($FireHolEnable==0){
            $NoFirewallico=$tpl->icon_nothing();
            $jlog_ico=$tpl->icon_nothing();
            $policy_ico=$tpl->icon_nothing();
        }
        $rulename=array();
        if(strlen($ligne["rulename"])>2){
            $rulename[]="<small>({$ligne["rulename"]})</small>";
        }
        if($OnlyMASQ==1){
            $txt="<small><i>{masq_only_explain}</small></i>";
            $ligneC=$q->mysqli_fetch_array("SELECT count(networks) as tcount FROM pnic_bridges_src WHERE pnicid='$ID'");
            $count=intval($ligneC["tcount"]);
            if($count>0){
                $txt="<small><i>{masq_only_explain} {source_network} <string>$count</string> {ipaddresses}</small></i>";
            }
            $rulename[]=$txt;
        }
        $rulename_text="";
        if(count($rulename)>0) {
            $rulename_text = "<br>".implode("<br>", $rulename);
        }
		$html[]= "<tr class='$TRCLASS' id='$md'>";
		$html[]= "<td $t1perc>$ID</td>";
        $html[]="<td $t1perc><span id='bridge-status-$ID'>$STATUS</span></td>";
        $html[]="<td $t1perc><span id='bridge-packets-$ID'>$pks<span></td>";
        $html[]="<td $t1perc><span id='bridge-size-$ID'>$size</span></td>";
		$html[]= "<td $tclass>$nic_from_text$rulename_text$DenyDHCP</td>";
        $html[]= "<td $tclass><i class='$text_class fa-2x ".ico_arrow_right."'></i></td>";
		$html[]= "<td $tclass>$nic_to_text</td>";
		$html[]= "<td $t1perc>$NoFirewallico</td>";
        $html[]= "<td $t1perc>$policy_ico</td>";
        $html[]= "<td $t1perc>$masquerading_ico</td>";
        $html[]= "<td $t1perc>$jlog_ico</td>";
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


    $addMasq="Loadjs('$page?masq-id=0&function=$function')";
    $topbuttons[] = array($add, ico_plus, "{new_connector}");
    $topbuttons[] = array($addMasq, ico_plus, "{rule}: Masquerade");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_connectors}");
    $TINY_ARRAY["TITLE"]="{interfaces_connectors}";
    $TINY_ARRAY["ICO"]="fas fa-bezier-curve";
    $TINY_ARRAY["EXPL"]="<i>{dashboard_router_explain}</i><br>{bridges_iptables_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="
	<script>
	$headsjs
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
function rule_save():bool{
	include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
	patch_firewall_tables();
	$tpl        = new template_admin();
    $sql="";
    $tpl->CLEAN_POST();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID         = $_POST["ID"];
	$nic_from   = $_POST["nic_from"];
	$nic_to     = $_POST["nic_to"];
	$policy     = intval($_POST["policy"]);
	$STP        = intval($_POST["STP"]);
	$NoFirewall = intval($_POST["NoFirewall"]);
	$jlog       = intval($_POST["jlog"]);
	$masquerading = intval($_POST["masquerading"]);

	if($masquerading==1){
	    if($tpl->masquerading_alreadyset($nic_to)){
	        echo "jserror:$nic_to: ".$tpl->_ENGINE_parse_body("{already_masqueraded}");
	        return false;
        }
    }

	$zMD5       = md5($nic_from.$nic_to);

	if($ID==0){
        $admtrack="Create new Firewall bridge $nic_from to $nic_to";
        $sql="INSERT INTO pnic_bridges (zMD5,nic_from,nic_to,enabled,STP,DenyDHCP,masquerading,masquerading_invert,NoFirewall,policy,jlog,rulename) 
	VALUES ('$zMD5','$nic_from','$nic_to','{$_POST["enabled"]}','$STP','{$_POST["DenyDHCP"]}','{$_POST["masquerading"]}','0','$NoFirewall','$policy','$jlog','{$_POST["rulename"]}')";

    }

	if($ID>0){
        $admtrack="Update Firewall bridge $nic_from to $nic_to";
        $sql="UPDATE pnic_bridges SET `zMD5`='$zMD5',
	`nic_from`            = '$nic_from',
	`nic_to`              = '$nic_to',
	`enabled`             = '{$_POST["enabled"]}',
	`STP`                 = '$STP',
	`DenyDHCP`            = '{$_POST["DenyDHCP"]}',
	`masquerading`        = '{$_POST["masquerading"]}',
	`masquerading_invert` = '0',
	`DenyCountries`       = '0',
	`NoFirewall`          = '$NoFirewall',
	`policy`              = '$policy',
	`jlog`                = '$jlog',
    `rulename`            = '{$_POST["rulename"]}'     
	WHERE   ID            =  $ID";}
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
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}