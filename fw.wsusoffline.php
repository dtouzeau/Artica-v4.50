<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["wsusofflineStorageDir"])){save();exit;}
if(isset($_GET["execute-now"])){execute_now();exit;}
if(isset($_GET["execute-popup"])){execute_now_popup();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_WSUSOFFLINE}</h1>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:250px'><div id='WSUSOFFLINE-STATE'></div></td>
		<td valign='middle' style='padding-left:20px'><p>{APP_WSUSOFFLINE_EXPLAIN}</p></td>
	</tr>
	</table>
	
	</div>
	
</div>
			
                            
			
<div class='row'><div id='progress-wsusoffline-restart'></div>
			<div class='ibox-content'>
       	
			 	<div id='table-loader'></div>
                                    
			</div>
</div>
					
			
			
<script>
function LoadWSUSOfflineStatus(){
	if(!document.getElementById('WSUSOFFLINE-STATE') ){return;}
	LoadAjaxSilent('WSUSOFFLINE-STATE','fw.wsusoffline.php?status=yes');		
}
LoadAjax('table-loader','$page?table=yes');
setTimeout(\"LoadWSUSOfflineStatus()\",1000);
			
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function execute_now(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		$tpl->popup_error("{ERROR_NO_PRIVS2}");exit();
	}
	
	$tpl->js_dialog("{execute_now}", "$page?execute-popup=yes");
}
function execute_now_popup(){
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wsusoffline.reconfigure.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/wsusoffline.reconfigure.log";
	$ARRAY["CMD"]="wsusoffline.php?execute=yes";
	$ARRAY["TITLE"]="{execute_now}";
	$ARRAY["AFTER"]="BootstrapDialog1.close();LoadWSUSOfflineStatus()";
	$prgress=base64_encode(serialize($ARRAY));
	$jsexecute="Loadjs('fw.progress.php?content=$prgress&mainid=progress-wsusofflineExec-restart');";
	echo "<div id='progress-wsusofflineExec-restart'></div><script>$jsexecute</script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	
	$wsusofflineStorageDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineStorageDir"));
	if($wsusofflineStorageDir==null){$wsusofflineStorageDir="/usr/share/wsusoffline/client";}
	$wsusofflineLimitRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineLimitRate"));
	$wsusofflineSched=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineSched"));
	
	if($wsusofflineLimitRate==0){$wsusofflineLimitRate=850;}
	if($wsusofflineSched==0){$wsusofflineSched=2;}
	
	
	$wsusoffline=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusoffline"));
	$langs=array("deu"=>"German","enu"=>"English","ara"=>"Arabic","chs"=>"Chinese (Simplified)","cht"=>"Chinese (Traditional)","csy"=>"Czech","dan"=>"Danish","nld"=>"Dutch","fin"=>"Finnish","fra"=>"French","ell"=>"Greek","heb"=>"Hebrew","hun"=>"Hungarian","ita"=>"Italian","jpn"=>"Japanese","kor"=>"Korean","nor"=>"Norwegian","plk"=>"Polish","ptg"=>"Portuguese","ptb"=>"Portuguese (Brazil)","rus"=>"Russian","esn"=>"Spanish","sve"=>"Swedish","trk"=>"Turkish");
	$products=array("w60"=>"Windows Server 2008, 32-bit","w60-x64"=>"Windows Server 2008, 64-bit","w61"=>"Windows 7, 32-bit","w61-x64"=>"Windows 7 / Server 2008 R2, 64-bit","w62-x64"=>"Windows Server 2012, 64-bit","w63"=>"Windows 8.1, 32-bit","w63-x64"=>"Windows 8.1 / Server 2012 R2, 64-bit","w100"=>"Windows 10, 32-bit","w100-x64"=>"Windows 10 / Server 2016, 64-bit","o2k7"=>"Office 2007, 32-bit","o2k10"=>"Office 2010, 32-bit","o2k10-x64"=>"Office 2010, 32/64-bit","o2k13"=>"Office 2013, 32-bit","o2k13-x64"=>"Office 2013, 32/64-bit","o2k16"=>"Office 2016, 32-bit","o2k16-x64"=>"Office 2016, 32/64-bit",);	
	$options=array("includesp"=>"Service Packs","includecpp"=>"Visual C++ runtime libraries","includedotnet"=>".NET Frameworks","includewddefs"=>"Antivirus Virus definitions");
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	$wsusofflineUseLocalProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineUseLocalProxy"));
	
	


	
	$schedules[1]="{each} 1 {hour}";
	$schedules[2]="{each} 2 {hours}";
	$schedules[4]="{each} 4 {hours}";
	$schedules[8]="{each} 8 {hours}";
	$schedules[24]="{each_day} 01:00";
	$schedules[25]="{each_day} 02:00";
	$schedules[26]="{each_day} 03:00";
	$schedules[27]="{each_day} 04:00";
	
	$form[]=$tpl->field_browse_directory("wsusofflineStorageDir", "{storage_directory}", $wsusofflineStorageDir);
	$form[]=$tpl->field_numeric("wsusofflineLimitRate", "{limit_rate} (kBytes/s)", $wsusofflineLimitRate);
	if($SQUIDEnable==1){
		$form[]=$tpl->field_checkbox("wsusofflineUseLocalProxy", "{use_local_proxy}", $wsusofflineUseLocalProxy);
		
	}
	$form[]=$tpl->field_array_hash($schedules,"wsusofflineSched", "{schedule}", $wsusofflineSched);
	
	$form[]=$tpl->field_section("{languages}");
	$form[]="<table style='width:100%'>";
	$c=0;
	$form[]="<tr>";
	$form[]="<td><table>";
	while (list($key,$val)=each($langs)){
		$value=0;
		if(isset($wsusoffline["LANGS"][$key])){$value=1;}
		$form[]="<tr><td>".$tpl->field_checkbox("LANG_$key",$val,$value)."</td></tr>";
		$c++;
		if($c>3){
			$form[]="</tr></table></td><td><table><tr>";
			$c=0;
		}
		
	}
	$form[]="</tr></table></tr></table>";
	$form[]=$tpl->field_section("{products}");
	$form[]="<table style='width:100%'>";
	$c=0;
	$form[]="<tr>";
	$form[]="<td><table>";
	while (list($key,$val)=each($products)){
		$value=0;
		if(isset($wsusoffline["PRODUCTS"][$key])){$value=1;}
		$form[]="<tr><td>".$tpl->field_checkbox("PROD_$key",$val,$value)."</td></tr>";
		$c++;
		if($c>3){
			$form[]="</tr></table></td><td><table><tr>";
			$c=0;
		}
	
	}
	$form[]="</tr></table></tr></table>";
	
	$form[]=$tpl->field_section("{options}");
	$form[]="<table style='width:100%'>";
	$c=0;
	$form[]="<tr>";
	$form[]="<td><table>";
	while (list($key,$val)=each($options)){
		$value=0;
		if(isset($wsusoffline["OPTIONS"][$key])){$value=1;}
		$form[]="<tr><td>".$tpl->field_checkbox("OPT_$key",$val,$value)."</td></tr>";
		$c++;
		if($c>3){
			$form[]="</tr></table></td><td><table><tr>";
			$c=0;
		}
	
	}
	$form[]="</tr></table></tr></table>";
	
	$tpl->form_add_button("{execute_now}", "Loadjs('$page?execute-now=yes')");
	$html=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$js,"AsSquidAdministrator");
	echo $html."
	<script>
	LoadWSUSOfflineStatus()
	</script>		
	";
	
}

function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$sock=new sockets();
	$sock->SET_INFO("wsusofflineStorageDir", $_POST["wsusofflineStorageDir"]);
	$sock->SET_INFO("wsusofflineLimitRate", $_POST["wsusofflineLimitRate"]);
	$sock->SET_INFO("wsusofflineSched", $_POST["wsusofflineSched"]);
	if(isset($_POST["wsusofflineUseLocalProxy"])){
		$sock->SET_INFO("wsusofflineUseLocalProxy",$_POST["wsusofflineUseLocalProxy"]);
	}
	
	
	while (list($key,$val)=each($_POST)){
		if($val==0){continue;}
		
		if(preg_match("#LANG_(.+)#", $key,$re)){
			$wsusoffline["LANGS"][$re[1]]=true;
			continue;
		}
		if(preg_match("#PROD_(.+)#", $key,$re)){
			$wsusoffline["PRODUCTS"][$re[1]]=true;
			continue;
		}		
		if(preg_match("#OPT_(.+)#", $key,$re)){
			$wsusoffline["OPTIONS"][$re[1]]=true;
			continue;
		}		
	}
	
	
	$sock->SaveConfigFile(serialize($wsusoffline), "wsusoffline");
	$sock->getFrameWork("wsusoffline.php?reconfigure=yes");
	
	
	
}


function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sizetext=null;
	$sock->getFrameWork("wsusoffline.php?status=yes");
	
	$dirsizes=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineSizes"));
	
	$CUR=intval($dirsizes["CUR"]);
	$PART=intval($dirsizes["PART"]);
	
	if($CUR>0){
		$sizetext="<br>{size}:<strong>".FormatBytes($CUR/1024)."</strong>/".FormatBytes($PART/1024);
	}
	
	
	if(!is_file(PROGRESS_DIR."/wsusoffline.state")){
	
	echo $tpl->_ENGINE_parse_body("
	
		<div class=\"widget style1 yellow-bg\">
			<div class=\"row vertical-align\">
				<div class=\"col-xs-3\">
					<i class=\"fa fa-clock fa-3x\"></i>
				</div>
				<div class=\"col-xs-9 text-right\">
					<h3 class=\"font-bold\">{scheduled}</h3>
					<p>{artica_agent_task_ordered}$sizetext</p>
				</div>
			</div>
		</div>

	");
	
	
	
	return;
	}
	
	$array=unserialize(@file_get_contents(PROGRESS_DIR."/wsusoffline.state"));
	if(preg_match("#uptime=(.+)#", $array["PIDTIME"],$re)){
		$array["PIDTIME"]=$re[1];
	}
	
	if(isset($array["PROGRESS"])){
		$array["PIDTIME"]=$array["PIDTIME"]." {$array["PROGRESS"]}%";
	}
	
	$title='{running}';
	if(isset($array["PROC"])){$title=$array["PROC"];}
	
	echo $tpl->_ENGINE_parse_body("
	
		<div class=\"widget style1 navy-bg\">
			<div class=\"row vertical-align\">
				<div class=\"col-xs-3\">
					<i class=\"fa fa-thumbs-up fa-3x\"></i>
				</div>
				<div class=\"col-xs-9 text-right\">
					<h3 class=\"font-bold\">$title</h3>
					<p>{since} {$array["PIDTIME"]}$sizetext</p>
				</div>
			</div>
		</div>

	");
	
}

