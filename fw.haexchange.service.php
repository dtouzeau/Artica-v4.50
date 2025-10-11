<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["HaClusterMaxConn"])){Save();exit;}

page();
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_HAPROXY_EXCHANGE} v$version</h1>
	<p>{APP_HAPROXY_EXCHANGE_TEXT} </p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-haexchnage-restart'></div>
	<div class='ibox-content'>

	<div id='table-haexchnage'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/ha-exchange');
	LoadAjax('table-haexchnage','$page?table=yes');
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_HAPROXY_EXCHANGE} v$version &raquo;&raquo; {service_status}",$html);
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
	$page=CurrentPageName();
	$ERR=null;
	$sock->getFrameWork('haexchange.php?status=yes');
    $statsfile="/usr/share/artica-postfix/ressources/logs/web/haexchange.status";
	$ini=new Bs_IniHandler($statsfile);
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";
	$ARRAY["CMD"]="haexchange.php?restart=yes";
	$ARRAY["TITLE"]="{restart}";
	$ARRAY["AFTER"]="LoadAjax('table-haexchnage','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-haexchnage-restart')";

    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$sql="SELECT count(*) as tcount FROM haexchange WHERE enabled=1";
	$ligne=$q->mysqli_fetch_array($sql);
	if(intval($ligne["tcount"])==0){
		$ERR="<div class='alert alert-danger'>{HAPROXY_NOBACKENDS_DEFINED}</div>";
			
	}
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";
	$ARRAY["CMD"]="haexchange.php?reload=yes";
	$ARRAY["TITLE"]="{reloading}";
	$ARRAY["AFTER"]="LoadAjax('table-haexchnage','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsreload="Loadjs('fw.progress.php?content=$prgress&mainid=progress-haexchnage-restart')";
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress.txt";
	$ARRAY["CMD"]="haexchange.php?stop=yes";
	$ARRAY["TITLE"]="{stopping_service}";
	$ARRAY["AFTER"]="LoadAjax('table-haexchnage','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsstop="Loadjs('fw.progress.php?content=$prgress&mainid=progress-haexchnage-restart')";
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress.txt";
	$ARRAY["CMD"]="haexchange.php?start=yes";
	$ARRAY["TITLE"]="{starting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-haexchnage','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsstart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-haexchnage-restart')";
	
	
	$html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td valign='top' style='width:350px'>". $tpl->SERVICE_STATUS($ini, "APP_HAPROXY_EXCHANGE",$jsrestart)."</td>
		<td valign='top'>$ERR
		<table style='width:100%'>
		<tr>
		    <td valign='top'>
		<div id='other-status'>
			<table style='width:100%'>
			<tr>
			<td><button class='btn btn-primary btn-lg' type='button' OnClick=\"$jsrestart\" style='width:250px;margin:10px;margin-top:0px;margin-bottom:20px'>{restart}</button></td>
			</tr>
			<tr>
			<td><button class='btn btn-primary btn-lg' type='button' OnClick=\"$jsreload\" style='width:250px;margin:10px;margin-bottom:20px'>{reload}</button></td>
			</tr>
			<tr>
			<td><button class='btn btn-danger btn-lg' type='button' OnClick=\"$jsstop\" style='width:250px;margin:10px;margin-bottom:20px'>{stop_service}</button></td>
			</tr>
			<tr>
			<td><button class='btn btn-warning btn-lg' type='button' OnClick=\"$jsstart\" style='width:250px;margin:10px;margin-bottom:20px'>{start_service}</button></td>
			</tr>							
			</table>
        </div>
        </td>
        <td valign='top'>";

    $HaProxyMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaProxyCPUS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyCPUS"));
    $HaExchangeCertif=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeCertif"));
    $HaExchangeInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeInterface"));
    $HaExchangeOutInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeOutInterface"));
    $HaExchangeBalance=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeBalance"));
    if($HaProxyMaxConn<2000){$HaProxyMaxConn=2000;}
    if($HaExchangeBalance==null){$HaExchangeBalance="roundrobin";}


    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
            $sock=new sockets();
            $CPU_NUMBER=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
        }else{
            $CPU_NUMBER=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
        }
    }


    for($i=1;$i<$CPU_NUMBER+1;$i++){
        $s=null;
        if($i>1){$s="s";}
        $CPUz[$i]="$i {cpu}{$s}";
    }
    $form[]=$tpl->field_interfaces("HaExchangeInterface","nooloopNoDef:{listen_interface}",$HaExchangeInterface);
    $form[]=$tpl->field_interfaces("HaExchangeOutInterface","{outgoing_interface}",$HaExchangeOutInterface);

    $algo["source"]="{strict-hashed-ip}";
    $algo["roundrobin"]="{round-robin}";
    $algo["leastconn"]="{leastconn}";
    $form[]=$tpl->field_array_hash($algo,"HaExchangeBalance","{method}",$HaExchangeBalance);


    $form[]=$tpl->field_numeric("HaClusterMaxConn","{maxconn}",$HaProxyMaxConn,"{haproxy_maxconn}");
    $form[]=$tpl->field_array_hash($CPUz,"HaProxyCPUS","nonull:{SquidCpuNumber}",$HaProxyCPUS,false,"{haproxy_nbproc}");
    $form[]=$tpl->field_certificate("HaExchangeCertif","{ssl_certificate}",$HaExchangeCertif);


    $html[]=$tpl->form_outside("{parameters}",$form,null,"{apply}",$jsreload,"AsSquidAdministrator",true);

    $html[]="</td></tr></table>";
    $html[]="</td>
	</tr>
	</table>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}