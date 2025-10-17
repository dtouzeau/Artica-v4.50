<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.ntpd.inc");
if(isset($_GET["ntp-server-status"])){service_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["NTPDListenInterface"])){save_config();exit;}
if(isset($_GET["flat"])){config_flat();exit;}
if(isset($_GET["config-js"])){config_js();exit;}
if(isset($_GET["clients-js"])){clients_js();exit;}
if(isset($_GET["clients-popup"])){clients_popup();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}

page();
function page(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$ntpdv_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydVersion");
    if(!$users->AsDebianSystem){die();}

    $html=$tpl->page_header("{APP_NTPD} $ntpdv_ver",
        "fa fa-clock","{ntp_about}","$page?tabs=yes","ntp-server",
        "progress-ntpd-restart",false,"table-loader-ntpd-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NTPD} $ntpdv_ver",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);


}
function service_status_clients(){
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/clients"));
    if(!$json->Status){
        return $tpl->widget_rouge("{clients}",$json->Error,null,ico_computer);
    }
    if(!property_exists($json,"Info")){
        return $tpl->widget_rouge("{clients}","{error} 2",null,ico_computer);
    }
    $info = $json->Info ?? [];

    /* Normalize Info into an array of objects */
    if (is_string($info)) {
        $tmp = json_decode($info);
        if (is_array($tmp))    { $info = $tmp; }
        elseif (is_object($tmp)) { $info = array_values(get_object_vars($tmp)); }
    }
    elseif (is_object($info)) {
        $info = array_values(get_object_vars($info));
    }
    elseif (!is_array($info)) {
        $info = []; // anything else => empty
    }

    $c = 0;
    foreach ($info as $client) {
        if (!is_object($client)) { continue; }
        $hostname = $client->hostname ?? $client->name ?? '';
        if ($hostname === '') { continue; }
        $c++;
    }

    if($c==0){
        return $tpl->widget_grey("{clients}","0",null,ico_computer);
    }
    $page=CurrentPageName();
    $btn[]=array("name"=>"{clients}","js"=>"Loadjs('$page?clients-js=yes')","icon"=>ico_loupe,"color"=>null);
    return $tpl->widget_vert("{clients}",$c,$btn,ico_computer);
}

function service_status():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $tpl=new template_admin();
    $page=CurrentPageName();


    $jsrestart=$tpl->framework_buildjs("/chrony/restart",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart",
        "");

    $html[]=service_status_clients();
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_NTPD",$jsrestart);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td style='vertical-align: top;width:350px'><div id='ntp-server-status'></div></td>
		<td style='vertical-align: top;width:99%'><div id='NtpConfgFlat'></div></td>";

    $html[]="</tr></table>";
    $js=$tpl->RefreshInterval_js("ntp-server-status",$page,"ntp-server-status=yes");
    $html[]="<script>LoadAjax('NtpConfgFlat','$page?flat=yes');$js</script>";

	echo $tpl->_ENGINE_parse_body($html);
	
}

function save_config(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
}
function config_flat():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ntp=new ntpd();

    $sserv=$ntp->ServersList();
    foreach ($sserv as $num=>$val){
        $ServersList[$num]=$num;

    }
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

    if($HaClusterClient==1){
        return config_flat_hacluster();
    }


    if(!is_file("/etc/artica-postfix/settings/Daemons/NTPDRestrictToLocalNetwork")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NTPDRestrictToLocalNetwork", 1);}

    $NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
    $NTPDRestrictToLocalNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDRestrictToLocalNetwork"));
    $NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");
    $NTPDListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDListenInterface");
    if($NTPClientDefaultServerList==null){$NTPClientDefaultServerList="Generic";}

    $ADDR=$_SERVER["SERVER_ADDR"];



    if($NTPDListenInterface==null){$NTPDListenInterface="{loopback}";}
    $tpl->table_form_field_js("Loadjs('$page?config-js=yes')","AsSystemAdministrator");

    $tpl->table_form_field_text("{listen_interface}",$NTPDListenInterface,ico_nic);

    $tpl->table_form_field_bool("{NTPDRestrictToLocalNetwork}",$NTPDRestrictToLocalNetwork,ico_shield);
    if($NTPDUseSpecifiedServers==1){
        $tpl->table_form_field_bool("{use_specified_time_servers}",1,ico_earth);
    }else{
        $tpl->table_form_field_text("{default_ntp_servers}",count($ServersList)." {servers} ($NTPClientDefaultServerList)",ico_earth);
    }
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("PowerShell","<small><code style='text-transform: none'>w32tm /config /manualpeerlist:\"$ADDR,0x1\" /syncfromflags:manual /reliable:YES /update<br>net stop w32time<br>net start w32time<br>w32tm /resync</small></code>",ico_microsoft);

    $html[]=$tpl->table_form_compile();
    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"Loadjs('fw.system.ntp.servers.php')\"><i class='fad fa-archive'></i> {ntp_servers} </label>
			</div>"
    );
    if($HaClusterClient==1){
        $btns=null;
    }

    $ntpdv_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydVersion");
    $TINY_ARRAY["TITLE"]="{APP_NTPD} $ntpdv_ver";
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]="{ntp_about}";
    $TINY_ARRAY["URL"]="ntp-server";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function config_flat_hacluster():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->table_form_field_text("{listen_interface}", "{all}", ico_nic);
    $tpl->table_form_field_text("{use_specified_time_servers}","{lb_ipaddr}",ico_earth);
    $tpl->table_form_field_text("{NTPDRestrictToLocalNetwork}","{all}",ico_shield);


    $ntpdv_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydVersion");
    $TINY_ARRAY["TITLE"]="{APP_NTPD} $ntpdv_ver";
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]="{ntp_about}";
    $TINY_ARRAY["URL"]="ntp-server";
    $TINY_ARRAY["BUTTONS"]=null;
    $html[]=$tpl->table_form_compile();
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function clients_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{clients}","$page?clients-popup=yes",850);
}
function config_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return $tpl->js_dialog2("{parameters}","$page?config-popup=yes");
}

function config_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ntp=new ntpd();

    $sserv=$ntp->ServersList();
    foreach ($sserv as $num=>$val){
        $ServersList[$num]=$num;

    }

    if(!is_file("/etc/artica-postfix/settings/Daemons/NTPDRestrictToLocalNetwork")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NTPDRestrictToLocalNetwork", 1);}

    $NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
    $NTPDRestrictToLocalNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDRestrictToLocalNetwork"));
    $NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");
    $NTPDListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDListenInterface");
    if($NTPClientDefaultServerList==null){$NTPClientDefaultServerList="United States";}


    $jsrestart=$tpl->framework_buildjs("/chrony/reconfigure",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart",
        "LoadAjax('table-loader-ntpd-service','$page?tabs=yes');");

    $form[]=$tpl->field_interfaces("NTPDListenInterface", "{listen_interface}", $NTPDListenInterface);
    $form[]=$tpl->field_checkbox("NTPDUseSpecifiedServers","{use_specified_time_servers}",$NTPDUseSpecifiedServers);
    $form[]=$tpl->field_checkbox("NTPDRestrictToLocalNetwork","{NTPDRestrictToLocalNetwork}",$NTPDRestrictToLocalNetwork,false,"{NTPDRestrictToLocalNetwork_explain}");
    $form[]=$tpl->field_array_hash($ServersList, "NTPClientDefaultServerList", "{default_ntp_servers}", $NTPClientDefaultServerList);


    $html[]=$tpl->form_outside("", $form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('table-loader-ntpd-service','$page?tabs=yes');$jsrestart","AsSystemAdministrator");



    echo $tpl->_ENGINE_parse_body($html);
}


function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	
	$array["{status}"]="$page?status=yes";
	$array["{ntp_servers} {status}"]="fw.system.ntpd.servers.status.php?list=yes";
	$array["{events}"]="fw.system.ntpd.events.php";

	
	echo $tpl->tabs_default($array);	
	
}
function clients_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/clients"));

    $html[]="<table id='table-ntpd-servers-status' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{clients}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{queries}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{since}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;


    $datas=$json->Info;
    $ipClass=new IP();
    foreach ($datas as $line){

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $hostname=$line->hostname;
        $requests=$tpl->FormatNumber($line->requests);
        $sincesec=intval($line->sincesec);
        $sincesec=time()-$sincesec;
        $last_rx_text = distanceOfTimeInWords($sincesec, time(),true);
        $md=md5(json_encode($line));
        $ico="<i class='".ico_computer."'></i>";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>$ico&nbsp;$hostname</td>";
        $html[]="<td style='width:1%' nowrap>$requests</td>";
        $html[]="<td style='width:1%' nowrap>$last_rx_text</td>";
        $html[]="</tr>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}