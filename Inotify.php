<?php

if(posix_getuid()==0){$_GET["verbose"]=true;}

if(count($_GET)>0){
	if(isset($_GET["verbose"])){
			$GLOBALS["VERBOSE"]=true;
			$GLOBALS["DEBUG_PROCESS"]=true;
			include_once("ressources/logs.inc");
			ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	if(isset($_GET["switch-high"])){high_menus();die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	if(isset($_GET["refresh-service-status"])){refresh_service_status();die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
}



$t=time();	
header("content-type: application/x-javascript");
echo "
if(document.getElementById('IMAGE_STATUS_INFO')){
	var value=document.getElementById('IMAGE_STATUS_INFO').innerHTML;
	if(value.length<20){
		LoadAjax('IMAGE_STATUS_INFO','admin.index.right-image.php');
	}
}
		
if(document.getElementById('squid-front-end-status')){
	LoadAjaxSilent('squid-front-end-status','admin.index.loadvg.php?squid-front-end-status=yes');
}
		
		
";
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
die("DIE " .__FILE__." Line: ".__LINE__);




function refresh_service_status(){
	if(!$GLOBALS["VERBOSE"]){
		if(GET_CACHED(__FILE__, __FUNCTION__,md5($_GET["refresh-service-status"]),false,1)){return;}
	}
	
	$refresh["C-ICAP"]="APP_C_ICAP";
	
	if(preg_match("#SERVICE-STATUS-ROUND-(.+)#", $_GET["refresh-service-status"],$re)){
		$tpl=new templates();
		$key=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Prod: $key\n<br>";}
		$content=GetIniContent($key);
		if($content==null){
			if(!is_file("ressources/logs/global.status.ini")){return;}
			$bsini=new Bs_IniHandler("ressources/logs/global.status.ini");
		}else{
			$bsini=new Bs_IniHandler();
			$bsini->loadString($content);
		}
		if($bsini->_params[$key]["service_name"]==null){return null;}
		$prod=$bsini->_params[$key]["service_name"];
		$status=DAEMON_STATUS_ROUND($key,$bsini);
		$html=$tpl->_ENGINE_parse_body($status);
		SET_CACHED(__FILE__, __FUNCTION__,md5($_GET["refresh-service-status"]), $html);
		echo $html;
	}
	
}

function GetIniContent($PRODUCT){
	$array["SQUID"]["SQUID"]=true;
	$array["SQUID"]["DANSGUARDIAN"]=true;

	
	$array["SQUID"]["APP_PROXY_PAC"]=true;
	$array["SQUID"]["APP_SQUIDGUARD_HTTP"]=true;
	$array["SQUID"]["APP_UFDBGUARD"]=true;
	if(isset($array["SQUID"][$PRODUCT])){
		$filecache="ressources/logs/web/SQUID.status";
		if(is_file($filecache)){if(zfile_time_min($filecache)<1){return @file_get_contents($filecache);}}
		$sock=new sockets();
		$datas=base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes'));
		@unlink($filecache);
		@file_put_contents($filecache, $datas);
		return $datas;
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "Prod: $PRODUCT\n<br>";}
	
	$array["PP"]["C-ICAP"]="APP_C_ICAP";
	$array["PA"]["C-ICAP"]="cmd.php?cicap-ini-status=yes";
	
	if(isset($array["PA"][$PRODUCT])){
		$filecache="ressources/logs/web/{$array["PP"][$PRODUCT]}.status";
		if(is_file($filecache)){if(zfile_time_min($filecache)<1){return @file_get_contents($filecache);}}
		$sock=new sockets();
		$datas=base64_decode($sock->getFrameWork($array["PA"][$PRODUCT]));
		@unlink($filecache);
		@file_put_contents($filecache, $datas);
		return $datas;
	}
	
	
}

	function zfile_time_min($path){
		if(!is_dir($path)){
			if(!is_file($path)){return 100000;}
			}
	 		$last_modified = filemtime($path);
	 		$data1 = $last_modified;
			$data2 = time();
			$difference = ($data2 - $data1); 	 
			return round($difference/60);	 
		}


function high_menus(){
		return;
		$sock=new sockets();
		$FixedLanguage=$sock->GET_INFO("FixedLanguage");
		if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
		$tpl=new templates();
		if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/HEAD_MENUS")){
			$html=@file_get_contents("ressources/templates/{$_COOKIE["artica-template"]}/HEAD_MENUS");
			$html=str_replace("%articaver%", $users->ARTICA_VERSION, $html);
			echo $tpl->_ENGINE_parse_body($html);
			return;
		}
		
		include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
		$htmlT=new htmltools_inc();
		$lang=$htmlT->LanguageArray();
		$users=new usersMenus();
		if(!is_numeric($_SESSION["SWITCH_HIGH_MENUS"])){$_SESSION["SWITCH_HIGH_MENUS"]=1;}
		
		if($users->AsAnAdministratorGeneric){
			
			if($_SESSION["SWITCH_HIGH_MENUS"]==1){	
			$html="	
			<table style='width:80%;padding:0px;margin:0px;border:0px;margin-top:2px'>
			<tr>
			";
			
			if($_SESSION["uid"]==-100){
			$html=$html."<td><a href='#' OnClick=\"Loadjs('artica.settings.php?js=yes&func-AccountsInterface=yes');\" style='color:#CCCCCC;font-size:11px;text-decoration:underline'>Manager</a></td>
			<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>";
				
			}
			$html=$html."
				<td><a href='#' OnClick=\"javascript:logoff();\" style='color:#CCCCCC;font-size:11px;text-decoration:underline'>{logoff}</a></td>
				<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>
			<td><a href='#' OnClick=\"Loadjs('chg.language.php');\" style='color:#CCCCCC;font-size:11px;text-decoration:underline'>{language}:&nbsp;{$tpl->language}</a></td>
			<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>
			<td><a href='#' OnClick=\"Loadjs('artica.update.php?js=yes')\" style='color:#CCCCCC;font-size:11px;text-decoration:underline'>{version}:&nbsp;{$users->ARTICA_VERSION}</a></td>
			<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>
			<td><a href='#' OnClick=\"javascript:AjaxTopMenuTiny('div-high-menus','Inotify.php?switch-high=yes');\" 
			style='color:#CCCCCC;font-size:11px;'>&raquo;&raquo;</a></td>				
			</tr>
			</table>
			";
			$_SESSION["SWITCH_HIGH_MENUS"]=0;
			echo $tpl->_ENGINE_parse_body($html);
		}else{
			include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
			include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
			$os=new os_system();
			$cpunum=intval($users->CPU_NUMBER);		
			$array_load=sys_getloadavg();
			$org_load=$array_load[2];
			$load=intval($org_load);
			$hash_mem=$os->realMemory();
			$mem_used_p=$hash_mem["ram"]["percent"];
			$mem_used_kb=FormatBytes($hash_mem["ram"]["used"]);
			$total=FormatBytes($hash_mem["ram"]["total"]);
			$swapar=$os->swap();
	
			$max_vert_fonce=$cpunum;
			$max_vert_tfonce=$cpunum+1;
			$max_orange=$cpunum*0.75;
			$max_over=$cpunum*2;
			$purc1=$load/$cpunum;
			$pourc=round($purc1*100,2);
			$color="#5DD13D";
			if($load>=$max_orange){$color="#F59C44";}
			if($load>$max_vert_fonce){$color="#C5792D";}
			if($load>$max_vert_tfonce){$color="#83501F";}	
			if($load>=$max_over){$color="#640000";$text="{overloaded}";}
			$html="	
			<table style='width:80%;padding:0px;margin:0px;border:0px;margin-top:2px'>
			<tbody>
			<tr>";
			
						
			if($_SESSION["uid"]==-100){
			$html=$html."<td><a href='#' OnClick=\"Loadjs('artica.settings.php?js=yes&func-AccountsInterface=yes');\" style='color:#CCCCCC;font-size:11px;text-decoration:underline'>Manager</a></td>
			<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>";
				
			}
			
			$html=$html."<td nowrap><a href='#' OnClick=\"javascript:logoff();\" style='color:#CCCCCC;font-size:11px;text-decoration:underline'>{logoff}</a></td>
			<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>		
			<td nowrap><a href='#' OnClick=\"Loadjs('chg.language.php');\" 
			style='color:#CCCCCC;font-size:11px;'>{load}: $org_load</a></td>	
			<td style='color:white;font-size:11px;' nowrap>&nbsp;|&nbsp;</td>
			<td nowrap><a href='#' OnClick=\"Loadjs('chg.language.php');\" 
			style='color:#CCCCCC;font-size:11px;' nowrap>mem: $mem_used_p%</a></td>
			<td style='color:white;font-size:11px;' nowrap>&nbsp;|&nbsp;</td>
<td nowrap><a href='#' OnClick=\"Loadjs('chg.language.php');\" 
			style='color:#CCCCCC;font-size:11px;' nowrap>swap: {$swapar[0]}%</a></td>	
			<td style='color:white;font-size:11px;'>&nbsp;|&nbsp;</td>
			<td nowrap><a href='#' OnClick=\"javascript:AjaxTopMenuTiny('div-high-menus','Inotify.php?switch-high=yes');\" 
			style='color:#CCCCCC;font-size:11px;'>&raquo;&raquo;</a></td>						
			</tR>
			</tbody>
			</table>
			";	
			$_SESSION["SWITCH_HIGH_MENUS"]=1;
			echo $tpl->_ENGINE_parse_body($html);
			
		}

	}
	
}

	


