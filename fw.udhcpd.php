<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["interface"])){save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["service-status"])){status_service();exit;}
if(isset($_GET["leases"])){leases();exit;}
if(isset($_GET["service-flat"])){status_flat();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["search"])){events_search();exit;}

js();

function js(){
    $Interface      = $_GET["interface"];
    $tpl            = new template_admin();
    $page           = CurrentPageName();

    $nicz           = new system_nic($Interface);
    $dhcp_enabled=$nicz->udhcpd;

    if(preg_match("#^vlan([0-9]+)#",$Interface,$re)){
        $q      = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ID     = $re[1];
        $sline  = $q->mysqli_fetch_array("SELECT udhcpd_conf,enabled,udhcpd,nic FROM nics_vlan WHERE ID=$ID");
        $dhcp_enabled=intval($sline["udhcpd"]);
    }

    if($dhcp_enabled==1){
        $tpl->js_dialog1("{dhcp_server}: $Interface","$page?tabs=$Interface");
        return true;
    }

    $tpl->js_dialog1("{dhcp_server}: $Interface","$page?popup=$Interface");

}
function status_service():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $sock=new sockets();
    $interface=$_GET["service-status"];
    $json=json_decode($sock->REST_API("/system/network/udhcpd/status/$interface"));

    $ini->loadString($json->Info);


    $jsrestart=$tpl->framework_buildjs(
        "/system/network/udhcpd/restart/$interface",
        "udhcpd.$interface.progress",
        "udhcpd.$interface.progress",
        "udhcpd-$interface-restart",
        "LoadAjaxSilent('udhcpd-$interface-status','$page?service-status=$interface')"
    );

    $html[]=$tpl->SERVICE_STATUS($ini, "APP_UDHCPD",$jsrestart);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function status_flat(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $Interface=$_GET["service-flat"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/system/network/udhcpd/lease/$Interface"));

    $nicz           = new system_nic($Interface);

    $dhcp_config=$nicz->udhcpd_conf;

    if(preg_match("#^vlan([0-9]+)#",$Interface,$re)){
        $q      = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ID     = $re[1];
        $sline  = $q->mysqli_fetch_array("SELECT udhcpd_conf,enabled,udhcpd,nic FROM nics_vlan WHERE ID=$ID");
        $dhcp_config        = unserialize(base64_decode($sline["udhcpd_conf"]));
       }



    $tpl->table_form_field_text("{leases}",count($json->Info),ico_computer);
    $tpl->table_form_field_text("Pool",$dhcp_config["start"]."-".$dhcp_config["end"],ico_networks);
    $tpl->table_form_field_text("{netmask}",$dhcp_config["subnet"],ico_interface);
    $tpl->table_form_field_text("{gateway}",$dhcp_config["gateway"],ico_interface);
    echo $tpl->table_form_compile();

}

function events_start():bool{
    $Interface=$_GET["events-start"];
    $tpl    = new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&interface=$Interface","");
    echo "</div>";
    return true;
}
function status():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $Interface=$_GET["status"];
    $html[]="<div id='udhcpd-$Interface-restart' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";
    $html[]="<td style='width: 350px;vertical-align: top'>";
    $html[]="<div id='udhcpd-$Interface-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width: 99%;vertical-align: top'><div id='udhcpd-$Interface-flat'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('udhcpd-$Interface-status','$page?service-status=$Interface')";
    $html[]="LoadAjaxSilent('udhcpd-$Interface-flat','$page?service-flat=$Interface')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs(){
    $Interface=$_GET["tabs"];
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $array["{status}"] = "$page?status=$Interface";
    $array["{parameters}"] = "$page?popup=$Interface";
    $array["{leases}"] = "$page?leases=$Interface";
    $array["{events}"] = "$page?events-start=$Interface";
    echo $tpl->tabs_default($array);
    return true;
}

function popup(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $Interface      = $_GET["popup"];
    $nicz           = new system_nic($Interface);
    $resolv         = new resolv_conf();
    $buttonname     = "{apply}";
    $security       = "ASDCHPAdmin";
    $jsreload       = "LoadAjax('netz-interfaces-status','fw.network.interfaces.php?status2=yes');";

    $dhcp_config=$nicz->udhcpd_conf;
    $dhcp_enabled=$nicz->udhcpd;
    if(preg_match("#^vlan([0-9]+)#",$Interface,$re)){
        $q      = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ID     = $re[1];
        $sline  = $q->mysqli_fetch_array("SELECT udhcpd_conf,enabled,udhcpd,nic FROM nics_vlan WHERE ID=$ID");
        $dhcp_config        = unserialize(base64_decode($sline["udhcpd_conf"]));
        $dhcp_enabled=intval($sline["udhcpd"]);
    }



    if(!isset($dhcp_config["lease"])){$dhcp_config["lease"]=864000;}

    if(!isset($dhcp_config["start"])){
        $zpart=explode(".",$nicz->IPADDR);
        $dhcp_config["start"]="$zpart[0].$zpart[1].$zpart[2].10";
    }
    if(!isset($dhcp_config["end"])){
        $zpart=explode(".",$nicz->IPADDR);
        $dhcp_config["end"]="$zpart[0].$zpart[1].$zpart[2].254";
    }
    if(!isset($dhcp_config["subnet"])){
        $dhcp_config["subnet"]=$nicz->NETMASK;
    }

    if(!isset($dhcp_config["DNS1"])){
        $dhcp_config["DNS1"]=$resolv->MainArray["DNS1"];
    }
    if(!isset($dhcp_config["DNS2"])){
        $dhcp_config["DNS2"]=$resolv->MainArray["DNS2"];
    }
    if(!isset($dhcp_config["domain"])){
        $dhcp_config["domain"]=$resolv->MainArray["DOMAINS1"];
    }
    if(!isset($dhcp_config["gateway"])){
        $dhcp_config["gateway"]=$nicz->IPADDR;
    }

    $form[]=$tpl->field_checkbox("udhcpd","{enable}",$dhcp_enabled,true,"");
    $form[]=$tpl->field_numeric("lease","{max_lease_time} ({seconds})",$dhcp_config["lease"],"{max_lease_time_text}");


    $form[]=$tpl->field_section("{network_attribution}","{dhcp_network_attribution_explain}");
    $form[]=$tpl->field_hidden("interface",$Interface);
    $form[]=$tpl->field_ipaddr("start", "{ipfrom}",  $dhcp_config["start"]);
    $form[]=$tpl->field_ipaddr("end", "{ipto}", $dhcp_config["end"]);
    $form[]=$tpl->field_ipaddr("subnet", "{netmask}",$dhcp_config["subnet"]);
    $form[]=$tpl->field_ipaddr("gateway", "{gateway}",  $dhcp_config["gateway"],false,null,false,"blur()");
    $form[]=$tpl->field_ipaddr("DNS1", "{DNSServer} 1", $dhcp_config["DNS1"]);
    $form[]=$tpl->field_ipaddr("DNS2", "{DNSServer} 2", $dhcp_config["DNS2"]);
    $form[]=$tpl->field_text("domain", "{ddns_domainname}",  $dhcp_config["domain"]);
    $html[]=$tpl->form_outside("{dhcp_server}: $nicz->NICNAME &raquo; {general_settings}", @implode("\n", $form),null,$buttonname,$jsreload,$security);
    echo $tpl->_ENGINE_parse_body($html);
}

function leases(){
    $Interface=$_GET["leases"];
    $tpl=new template_admin();
    $t=time();
    $thv="data-sortable=true class='text-capitalize' data-type='text'";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='width: 100%;margin-top:10px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $thv nowrap colspan='2'>{mac}</th>";
    $html[]="<th $thv nowrap>{ipaddr}</th>";
    $html[]="<th $thv nowrap>{hostname}</th>";
    $html[]="<th $thv nowrap>{expire}</th>";
    $html[]="<th $thv>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/system/network/udhcpd/lease/$Interface"));
    $TRCLASS="";
    foreach ($json->Info as $index=>$line){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $tb=explode("||",$line);
        $Mac=$tb[0];
        $IP=$tb[1];
        $Hostname=$tb[2];
        $Expire=convert_seconds($tb[3]);

        $md=md5($Mac.$IP);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><i class='".ico_computer."'></i></td>";
        $html[]="<td style='width:30%' nowrap><strong>$Mac</strong></td>";
        $html[]="<td style='width:30%' nowrap><strong>$IP</strong></td>";
        $html[]="<td style='width:30%' nowrap><strong>$Hostname</strong></td>";
        $html[]="<td style='width:30%' nowrap><strong>$Expire</strong></td>";
        $html[]="<td style='width:99%'>&nbsp</td>";
        $html[]="</tr>";

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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo $tpl->_ENGINE_parse_body($html);


}
function convert_seconds($seconds){
    if(!is_numeric($seconds)) return 0;


    // Create DateTime objects representing the start and end timestamps
    $dt1 = new DateTime("@0");
    $dt2 = new DateTime("@$seconds");

    // Calculate the difference between the two timestamps
    $diff = $dt1->diff($dt2);

    // Format the difference to display days, hours, minutes, and seconds
    return $diff->format('%a days, %h hours, %i minutes and %s seconds');
}
function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Interface=$_POST["interface"];
    $sock=new sockets();

    if(preg_match("#^vlan([0-9]+)#",$Interface,$re)){
        $ID     = $re[1];
        $q      = new lib_sqlite("/home/artica/SQLITE/interfaces.db");

        $udhcpd_conf=base64_encode(serialize($_POST));
        $udhcpd=$_POST["udhcpd"];
        $q->QUERY_SQL("UPDATE nics_vlan SET udhcpd_conf='$udhcpd_conf',udhcpd='$udhcpd' WHERE ID=$ID");
        if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
        admin_tracks("DHCP settings saved for VLAN Interface $ID");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
        $sock->REST_API("/system/network/udhcpd/interface/$Interface");
        return true;
    }

    $nicz           = new system_nic($Interface);
    $nicz->udhcpd=$_POST["udhcpd"];
    $nicz->udhcpd_conf=$_POST;
    $nicz->SaveNic();

    $sock->REST_API("/system/network/udhcpd/interface/$Interface");
    admin_tracks("DHCP settings saved for main Interface $Interface");
    return true;
}
function events_search(){
    clean_xss_deep();
    $tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $data=$sock->REST_API("/system/network/udhcpd/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }


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
    foreach ($json->Logs as $line){
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


        $line=str_replace("[DEBUG]:","<span class='label label-default'>DEBUG</span>&nbsp;",$line);
        $line=str_replace("[DEBUG] ","<span class='label label-default'>DEBUG</span>&nbsp;",$line);
        $line=str_replace("[RESTAPI]:","<span class='label label-info'>API</span>&nbsp;",$line);
        $line=str_replace("[START]:","<span class='label label-info'>START</span>&nbsp;",$line);
        $line=str_replace("[INFO]:","<span class='label label-info'>INFO</span>&nbsp;",$line);
        $line=str_replace("[ERROR]:","<span class='label label-danger'>ERROR</span>&nbsp;",$line);
        $line=str_replace("Starting ","<span class='label label-info'>START</span>&nbsp;",$line);
        $line=str_replace("WARNING:","<span class='label label-warning'>WARN</span>&nbsp;",$line);


        if(preg_match("#(fatal|Err)#i", $line)){
            $line="<span class='text-danger'>$line</span>";
        }


        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";

    }




    if($_GET["search"]==null){$_GET["search"]="*";}

    $html[]="</tbody></table>";

    echo $tpl->_ENGINE_parse_body($html);



}