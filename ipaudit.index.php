<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	session_start();
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["page"])){page();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["IpAuditRetention"])){save();exit;}
tabs();


function tabs(){
	$fontsize=26;
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$IpAuditEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditEnabled"));
	

	if($IpAuditEnabled==0){
		$html="<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{enable_feature}:{APP_IPAUDIT}","Loadjs('ipaudit.enable.php')",40).
				"<div style='margin-top:20px;font-size:20px'>{APP_IPAUDIT_TEXT}</div>
						<p>&nbsp;</p>
				<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/_jmH7_a3luA\" 
						frameborder=\"0\" allowfullscreen></iframe>		
			</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	$time=time();
	$array["page"]="{parameters}";
	$array["realtime"]="{realtime}";
	
	$CountOfTabs=count($array);
	
	if($CountOfTabs>9){
		$fontsize="16";
	}
	
	if($CountOfTabs>10){
		$fontsize="14";
	}
	
	foreach ($array as $num=>$ligne){
		if($num=="realtime"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ipaudit.table.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		if($num=="template"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"privoxy.template.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}	
		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_ipaudit_tabs",1493)."<script>LeftDesign('logs-white-256-opac20.png');</script>";
	
	
	}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$IpAuditVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditVersion");
	
	$html="
	<div style='font-size:30px'>{APP_IPAUDIT} v$IpAuditVersion</div>
	<div style='font-size:20px;margin-bottom:30px'>{APP_IPAUDIT_TEXT}</div>				
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign=top' style='width:440px;vertical-align:top'><div id='ipaudit-status'></div></td>
		<td valign='top'><div id='ipaudit-options'></div>
	</tr>
	</table>
	</div>

	<script>
		LoadAjax('ipaudit-status','$page?status=yes');
		LoadAjax('ipaudit-options','$page?popup=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$script=null;
	$sock=new sockets();
	$sock->getFrameWork('ipaudit.php?status=yes');
	$ini=new Bs_IniHandler("ressources/logs/web/ipaudit.status");
	$status=DAEMON_STATUS_ROUND("APP_IPAUDIT",$ini,null,0);
	
	$html="$status<div style='margin-top:10px;text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('ipaudit-status','$page?status=yes');")."</div>";

	echo $tpl->_ENGINE_parse_body($html);

}



function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$InfluxAdminRetention[2]="2 {days}";
	$InfluxAdminRetention[7]="7 {days}";
	$InfluxAdminRetention[15]="15 {days}";
	$InfluxAdminRetention[30]="1 {month}";
	$IpAuditRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditRetention"));
	if($IpAuditRetention==0){$IpAuditRetention=7;}
	$field_ret=Field_array_Hash($InfluxAdminRetention,"IpAuditRetention","$IpAuditRetention","blur()",null,0,"font-size:22px");

	$html="<center style='margin:50px;padding:50px;width:86%'>
				".button("{disable_feature}","Loadjs('ipaudit.disable.php')",40)."
			</center>
						
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{retention_time}:</td>		
		<td style='font-size:22px;font-weight:bold'>$field_ret</td>
	</tr>										
	<tr style='height:80px;'>
		<td colspan=2 align='right' style='padding-top:30px'><hr>". button("{apply}","Save$t()",40)."</td>
	</tr>	
	</table>
<script>
var xSave$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	
}	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('IpAuditRetention', document.getElementById('IpAuditRetention').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);  			
}
</script>
				
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("IpAuditRetention",$_POST["IpAuditRetention"]);
}
