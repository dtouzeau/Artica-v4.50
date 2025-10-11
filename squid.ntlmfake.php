<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 


	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<H1>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H1>";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["auth_param_ntlm_children"])){params_save();exit;}
	
	
	
status();


function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$EnableFakeAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth"));
	
	
	if($EnableFakeAuth==0){
		$html="<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{enable_feature} {ntlm_fake_auth}","Loadjs('squid.ntlmfake.enable.php')",40)."
						<div style='margin-top:20px;font-size:20px'>{ntlm_fake_auth_explain}</div>
			</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}

	
	
	$t=time();
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}
	
	
	
	$SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
	if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
	if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=0;}
	if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
	
	if(!is_numeric($SquidClientParams["auth_param_ntlmgroup_children"])){$SquidClientParams["auth_param_ntlmgroup_children"]=15;}
	if(!is_numeric($SquidClientParams["auth_param_ntlmgroup_startup"])){$SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
	if(!is_numeric($SquidClientParams["auth_param_ntlmgroup_idle"])){$SquidClientParams["auth_param_ntlmgroup_idle"]=1;}
	
	
	
	if(!is_numeric($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
	if(!is_numeric($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
	if(!is_numeric($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}
	
	if(intval($SquidClientParams["authenticate_cache_garbage_interval"])==0){$SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
	if(intval($SquidClientParams["authenticate_ttl"])==0){$SquidClientParams["authenticate_ttl"]=14400;}
	if(intval($SquidClientParams["authenticate_ip_ttl"])==0){$SquidClientParams["authenticate_ip_ttl"]=$SquidClientParams["authenticate_ttl"];}
	
	
	
	if($SquidClientParams["authenticate_ttl"]>$SquidClientParams["authenticate_cache_garbage_interval"]){
		$SquidClientParams["authenticate_cache_garbage_interval"]=$SquidClientParams["authenticate_ttl"];
	}
	if(intval($SquidClientParams["credentialsttl"])==0){
		$SquidClientParams["credentialsttl"]=$SquidClientParams["authenticate_ttl"];
	}
	
	
	
	
	$ttl_interval[30]="30 {seconds}";
	$ttl_interval[60]="1 {minute}";
	$ttl_interval[300]="5 {minutes}";
	$ttl_interval[600]="10 {minutes}";
	$ttl_interval[900]="15 {minutes}";
	$ttl_interval[1800]="30 {minutes}";
	
	$ttl_interval[3600]="1 {hour}";
	$ttl_interval[7200]="2 {hours}";
	$ttl_interval[14400]="4 {hours}";
	$ttl_interval[18000]="5 {hours}";
	$ttl_interval[86400]="1 {day}";
	$ttl_interval[172800]="2 {days}";
	$ttl_interval[259200]="3 {days}";
	$ttl_interval[432000]="5 {days}";
	$ttl_interval[604800]="1 {week}";
	
	$start_up[1]=1;
	$start_up[2]=2;
	$start_up[3]=3;
	$start_up[4]=4;
	$start_up[5]=5;
	$start_up[10]=10;
	$start_up[15]=15;
	$start_up[20]=20;
	$start_up[25]=25;
	$start_up[30]=30;
	$start_up[35]=35;
	$start_up[40]=40;
	$start_up[45]=45;
	$start_up[50]=50;
	$start_up[55]=55;
	$start_up[60]=60;
	$start_up[65]=65;
	$start_up[70]=70;
	$start_up[80]=80;
	$start_up[85]=85;
	$start_up[90]=90;
	$start_up[100]=100;
	$start_up[150]=150;
	$start_up[200]=200;
	$start_up[300]=300;
	$start_up[400]=300;
	$start_up[500]=500;
	$start_up[600]=600;
	$start_up[700]=700;
	$start_up[800]=800;
	$start_up[900]=900;
	$start_up[1000]=1000;
	$start_up[1500]=1500;
	
	
	$CPUS=$users->CPU_NUMBER;
	$CPUS_TEXT=" ($CPUS)";
	
	$html="	
	<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{disable_feature} {ntlm_fake_auth}","Loadjs('squid.ntlmfake.disable.php')",40)."
						<div style='margin-top:20px;font-size:20px'>{ntlm_fake_auth_explain}</div>
			</center>
	<div style='font-size:42px;margin-top:20px'>{authentication_modules}</div>
	
	
	<div style='font-size:16px;font-weight:bold;text-align:center;color:#E71010' id='$t-multi'></div>
	<div style='width:98%' class=form>
	<div class=text-info style='font-size:14px;'>{SquidClientParams_text}</div>
	<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:22px' widht=1% nowrap>{max_processes}:</span></td>
	<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlm_children-$t",$SquidClientParams["auth_param_ntlm_children"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{preload_processes}:</span></td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlm_startup-$t",$SquidClientParams["auth_param_ntlm_startup"],null,null,0,"font-size:22px")."</td>
	
					<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{prepare_processes}:</span></td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlm_idle-$t",$SquidClientParams["auth_param_ntlm_idle"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr style='height:70px'>
		<td colspan=3 style='font-size:32px'>{groups_checking}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{max_processes}:</span></td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlmgroup_children-$t",$SquidClientParams["auth_param_ntlmgroup_children"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{preload_processes}:</span></td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlmgroup_startup-$t",$SquidClientParams["auth_param_ntlmgroup_startup"],null,null,0,"font-size:22px")."</td>
	
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{prepare_processes}:</span></td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlmgroup_idle-$t",$SquidClientParams["auth_param_ntlmgroup_idle"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr style='height:70px'>
		<td colspan=3 style='font-size:32px'>{sessions_cache}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{authenticate_cache_garbage_interval}:</span></td>
		<td width=99%>". Field_array_Hash($ttl_interval,"authenticate_cache_garbage_interval-$t",$SquidClientParams["authenticate_cache_garbage_interval"],null,null,0,"font-size:22px")."</td>
			<td>&nbsp;</td>
	</tr>
		<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{authenticate_ttl_title}:</span></td>
		<td width=99%>". Field_array_Hash($ttl_interval,"authenticate_ttl-$t",$SquidClientParams["authenticate_ttl"],null,null,0,"font-size:22px")."</td>
			<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{authenticate_ip_ttl_title}:</span></td>
		<td width=99%>". Field_array_Hash($ttl_interval,"authenticate_ip_ttl-$t",$SquidClientParams["authenticate_ip_ttl"],null,null,0,"font-size:22px")."</td>
		<td>".help_icon("{authenticate_ip_ttl}")."</td>
					</tr>
					<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{credentialsttl}:</span></td>
		<td width=99%>". Field_array_Hash($ttl_interval,"credentialsttl-$t",$SquidClientParams["credentialsttl"],null,null,0,"font-size:22px")."</td>
					<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}", "Save$t()","42")."</td>
	</tr>
	</table>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
   	Loadjs('squid.ntlmfake.enable.php')
}	

function Save$t(){
		
	var XHR = new XHRConnection();
	XHR.appendData('auth_param_ntlm_children',document.getElementById('auth_param_ntlm_children-$t').value);
	XHR.appendData('auth_param_ntlm_startup',document.getElementById('auth_param_ntlm_startup-$t').value);
	XHR.appendData('auth_param_ntlm_idle',document.getElementById('auth_param_ntlm_idle-$t').value);
	XHR.appendData('authenticate_cache_garbage_interval',document.getElementById('authenticate_cache_garbage_interval-$t').value);
	XHR.appendData('credentialsttl',document.getElementById('credentialsttl-$t').value);
	XHR.appendData('authenticate_ttl',document.getElementById('authenticate_ttl-$t').value);
	XHR.appendData('authenticate_ip_ttl',document.getElementById('authenticate_ip_ttl-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}		
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
}

function  params_save(){
	
	$sock=new sockets();
	$SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
	
	foreach ($_POST as $num=>$ligne){
		$SquidClientParams[$num]=$ligne;
	
	}
	$sock->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
}
?>