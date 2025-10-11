<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["mikrotik-status-ports"])){mikrotik_status_ports();exit;}
if(isset($_GET["mikrotik-status-fw"])){mikrotik_status_fw();exit;}

if(isset($_GET["radius-config"])){radius_config();exit;}
if(isset($_POST["MikrotikInterface"])){Save();exit;}
if(isset($_POST["radiusserver"])){SaveRadius();exit;}
if(isset($_POST["SquidExternLDAPAUTH"])){SaveRemoteLDAP();exit;}
if(isset($_GET["remote-ldap-status"])){remote_ldap_status();exit;}
if(isset($_GET["mikrotik-rules"])){mikrotik_rules();exit;}
if(isset($_GET["mikrotik-rules-js"])){mikortik_rules_js();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\">
	<h1 class=ng-binding>{your_proxy} &raquo;&raquo; {mikrotik_compliance}</h1>
	<p>{APP_MIKROTIK_SUPPORT_EXPLAIN}</p>
	</div>
	</div>



	<div class='row'><div id='progress-mikrotik-restart'></div>
	<div class='ibox-content'>
	<div id='table-loader-squid-mikrotik'></div>
	</div>

	<script>
	$.address.state('/');
	$.address.value('/mikrotik');
	LoadAjax('table-loader-squid-mikrotik','$page?tabs=yes');
	</script>";


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function mikortik_rules_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $tpl->js_dialog1("MikroTiK {rules}","$page?mikrotik-rules=yes",1025);



}

function mikrotik_rules(){
    $tpl=new template_admin();
    $MikrotikInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikInterface");
    if($MikrotikInterface==null){$MikrotikInterface="eth0";}
    $NetStatus=new NetStatus($MikrotikInterface);
    $IPADDR=$NetStatus->IPADDR;
    $rand1=rand(50,100);
    $rand2=rand(50,100);
    $ipf=explode(".",$IPADDR);
    $ipfx="{$ipf[0]}.{$ipf[1]}.{$ipf[2]}";
    $example1="$ipfx.$rand1";
    $example2="$ipfx.$rand2";
    $MACAddr=$NetStatus->MacAddr;

    $f[]="/ip firewall address-list";
    $f[]="add address=$example1 comment=\"Direct mode - example\" list=SRC_NOT_TO_PROXY";
    $f[]="add address=$example2 comment=\"Direct mode - example\" list=SRC_NOT_TO_PROXY";
    $f[]="/ip firewall mangle";
    $f[]="add action=mark-routing chain=prerouting dst-port=80,443 new-routing-mark=ArticaProxy passthrough=no protocol=tcp src-address-list=!SRC_NOT_TO_PROXY src-mac-address=!$MACAddr";
    $f[]="/ip route";
    $f[]="add check-gateway=ping distance=1 gateway=$IPADDR routing-mark=ArticaProxy";

    $form[]=$tpl->field_textareacode("nonenone",null,@implode("\n",$f));

    echo $tpl->form_outside(null, @implode("\n", $form),"{mikrotik_cmd_line_explain}",null,null);



}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();


    $array["{status}"]="$page?status=yes";

    echo $tpl->tabs_default($array);
}

function mikrotik_status_ports(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $HTTP_PORT_IN_CONF=false;
    $HTTPS_PORT_IN_CONF=false;
    $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?iptables-save=yes");
    $HTTP_PORT_INTERFACE=null;
    $HTTP_PORT_INT=0;

    $HTTPS_PORT_INTERFACE=null;
    $HTTPS_PORT_INT=0;

    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        if(preg_match("#^http_port\s+(.+?):([0-9]+).*?name=MikrotikPortHTTP#",$line,$re)){
            $HTTP_PORT_IN_CONF=true;
            $HTTP_PORT_INTERFACE=$re[1];
            $HTTP_PORT_INT=intval($re[2]);
        }
        if(preg_match("#^https_port\s+(.+?):([0-9]+).*?name=MikrotikPortSSL#",$line,$re)){
            $HTTPS_PORT_IN_CONF=true;
            $HTTPS_PORT_INTERFACE=$re[1];
            $HTTPS_PORT_INT=intval($re[2]);
        }
    }

    if(!$HTTP_PORT_IN_CONF){
        echo $tpl->widget_rouge("HTTP Port","{not_configured}");
        return;
    }else{
        if($HTTP_PORT_INTERFACE=="0.0.0.0"){$HTTP_PORT_INTERFACE="127.0.0.1";}
        $fsock = @fsockopen($HTTP_PORT_INTERFACE, $HTTP_PORT_INT, $errno, $errstr, 2);
        if ( ! $fsock ){
            echo $tpl->widget_rouge("$HTTP_PORT_INTERFACE:$HTTP_PORT_INT {failed}","{error} $errno<br>$errstr");
            return;
        }

    }

    $HTTP_LINE="$HTTP_PORT_INTERFACE:$HTTP_PORT_INT";

    if(!$HTTPS_PORT_IN_CONF){
        echo $tpl->widget_rouge("HTTPS Port","{not_configured}");
        return;
    }


    if($HTTPS_PORT_INTERFACE=="0.0.0.0"){$HTTPS_PORT_INTERFACE="127.0.0.1";}
    $fsock = @fsockopen($HTTPS_PORT_INTERFACE, $HTTPS_PORT_INT, $errno, $errstr, 2);
    if ( ! $fsock ){
        echo $tpl->widget_rouge("$HTTPS_PORT_INTERFACE:$HTTPS_PORT_INT {failed}","{error} $errno<br>$errstr");return;
    }

    echo $tpl->widget_vert("http: $HTTP_LINE<br>https $HTTPS_PORT_INTERFACE:$HTTPS_PORT_INT","{success}");






}

function mikrotik_status_fw(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    exec("/usr/sbin/ip route show table tproxy 2>&1",$results);
    $TABLE_TPROXY=false;
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#local default dev#",$line)){
            $TABLE_TPROXY=true;
        }
    }

    if(!$TABLE_TPROXY){
        echo $tpl->widget_rouge("{routing_table}","{missing}");
        return;
    }

    $results=array();
    $IPRULE=false;
    exec("/usr/sbin/ip rule show  2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#fwmark 0x1 lookup (tproxy|100)#",$line)){
            $IPRULE=true;
        }
    }
    if(!$IPRULE){
        echo $tpl->widget_rouge("{routing_rules}","{missing}");
        return;
    }

    $results=array();
    $FIREWALL_TPROXY=false;

    $results=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_SAVE_DUMP"));
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#PREROUTING.*?-j TPROXY.*?--on-port#",$line,$re)){
            $FIREWALL_TPROXY=true;
            break;
        }
    }

    if(!$FIREWALL_TPROXY){
        echo $tpl->widget_rouge("Firewall {rules}","{missing}");
        return;
    }else{
        echo $tpl->widget_vert("Firewall {rules}","OK");
    }
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";

    $ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');

    foreach ($ayDscp as $a=>$b){
        if($a==0){continue;}
        $DSCP[$a]=$a;
    }



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress.log";
    $ARRAY["CMD"]="mikrotik.php?build=yes";
    $ARRAY["AFTER"]="LoadAjax('mikrotik-status-port','$page?mikrotik-status-ports=yes');LoadAjax('mikrotik-status-fw','$page?mikrotik-status-fw=yes');";
    $ARRAY["TITLE"]="{reconfigure}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mikrotik-restart')";

    $MikrotikInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikInterface");
    $SquidMikrotikMaskerade=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikMaskerade"));
    $MikrotikSSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLCertificate"));
    $SquidMikroTikTOS=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikroTikTOS"));

    if($MikrotikInterface==null){$MikrotikInterface="eth0";}
    $HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikHTTPPort"));
    $HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLPort"));

    $form[]=$tpl->field_interfaces("MikrotikInterface","{listen_interface}",$MikrotikInterface);
    $form[]=$tpl->field_numeric("MikrotikHTTPPort","{listen_port} (HTTP)",$HTTP_PORT);
    $form[]=$tpl->field_numeric("MikrotikSSLPort","{listen_port} (HTTPs)",$HTTPS_PORT);
    $form[]=$tpl->field_certificate("MikrotikSSLCertificate","{ssl_certificate}",$MikrotikSSLCertificate);
    $form[]=$tpl->field_array_hash($DSCP,"SquidMikroTikTOS","nonull:DSCP",$SquidMikroTikTOS,false);

    $tpl->form_add_button("MikroTiK {rules}","Loadjs('$page?mikrotik-rules-js=yes')");

    $form[]=$tpl->field_checkbox("SquidMikrotikMaskerade","MASQUERADE",$SquidMikrotikMaskerade);



    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align:top'>
        <div id='mikrotik-status-port'></div>
        <div id='mikrotik-status-fw'></div>
    </td>";
    $html[]="<td style='width:98%;vertical-align:top'>";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",$jsrestart,$security);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('mikrotik-status-port','$page?mikrotik-status-ports=yes');";
    $html[]="LoadAjax('mikrotik-status-fw','$page?mikrotik-status-fw=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
}


