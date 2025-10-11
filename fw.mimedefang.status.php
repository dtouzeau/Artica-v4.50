<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["MimeDefangAutoWhiteList"])){Save();exit;}


page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_MIMEDEFANG_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangVersion");
	$title=$tpl->_ENGINE_parse_body("{APP_VALVUAD} &raquo;&raquo; {service_status}");
	$js="LoadAjax('table-mimedefang','$page?tabs=yes');";
	
	

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>
	<p>{MIMEDEFANG_TEXT}</p>

	</div>

	</div>



	<div class='row'><div id='progress-mimedefang-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-mimedefang'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('postfix-policies-service-status');
	$.address.title('Artica: {APP_VALVUAD} {status}');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: {APP_VALVUAD} {status}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	$array["{status}"]="$page?table=yes";
	$array["{parameters}"]="$page?parameters=yes";
	$array["{statistics} {messages}"]="$page?stats-mailstats=yes";
	$array["{statistics} {volumes}"]="$page?stats-mailvolume=yes";
	echo $tpl->tabs_default($array);
}

function parameters(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MimeDefangArchiver=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangArchiver",true));
	$MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangClamav"));
	$MimeDefangDisclaimer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangDisclaimer"));
	$MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	$MimeDefangAutoWhiteList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));
	$MimeDefangFilterExtensions=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangFilterExtensions"));
	$MimeDefangAutoCompress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoCompress"));
	$MimeDefangForged=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangForged"));
	$DebugMimeFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugMimeFilter"));
	$MimeDefangNoTrustMyNets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangNoTrustMyNets"));
	$Param=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangServiceOptions"));

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/mimedefang.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/mimedefang.progress.log";
	$ARRAY["CMD"]="mimedefang.php?progress=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["REFRESH-MENU"]="yes";
	$ARRAY["AFTER"]="LoadAjax('table-mimedefang','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$mimedefang_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedefang-restart');";	
	
	$t=time();
	if(!is_numeric($Param["DEBUG"])){$Param["DEBUG"]=0;}
	if(!is_numeric($Param["MX_REQUESTS"])){$Param["MX_REQUESTS"]=200;}
	if(!is_numeric($Param["MX_MINIMUM"])){$Param["MX_MINIMUM"]=2;}
	if(!is_numeric($Param["MX_MAXIMUM"])){$Param["MX_MAXIMUM"]=10;}
	if(!is_numeric($Param["MX_MAX_RSS"])){$Param["MX_MAX_RSS"]=30000;}
	if(!is_numeric($Param["MX_MAX_AS"])){$Param["MX_MAX_AS"]=90000;}
	if(!is_numeric($Param["MX_TMPFS"])){$Param["MX_TMPFS"]=0;}
	if(!is_numeric($Param["MX_BUSY"])){$Param["MX_BUSY"]=600;}
	
	
	
	$form[]=$tpl->field_checkbox("MimeDefangAutoWhiteList","{smtp_AutoWhiteList}",$MimeDefangAutoWhiteList,false,"{smtp_AutoWhiteList_text}");
	$form[]=$tpl->field_checkbox("MimeDefangSpamAssassin","{enable_spamasssin}",$MimeDefangSpamAssassin,false,"{enable_spamasssin_text}");
	$form[]=$tpl->field_checkbox("MimeDefangForged","{checking_forged_messages}",$MimeDefangForged,false,"{checking_forged_messages_text}");
	$form[]=$tpl->field_checkbox("MimeDefangClamav","{enable_antivirus}",$MimeDefangClamav,false,"{ACTIVATE_ANTIVIRUS_SERVICE_TEXT}");
	
	
	$form[]=$tpl->field_checkbox("MimeDefangArchiver","{backupemail_behavior}",$MimeDefangArchiver,false,"{enable_APP_MAILARCHIVER_text}");
	$form[]=$tpl->field_checkbox("MimeDefangDisclaimer","{enable_disclaimer}",$MimeDefangDisclaimer,false,"{disclaimer_text}");
	$form[]=$tpl->field_checkbox("MimeDefangAutoCompress","{automated_compression}",$MimeDefangAutoCompress,false,"{auto-compress_text}");
	$form[]=$tpl->field_checkbox("MimeDefangFilterExtensions","{title_mime}",$MimeDefangFilterExtensions,false,"{mimedefang_attachments_text}");
    $form[]=$tpl->field_checkbox("MimeDefangNoTrustMyNets","{not_trust_mynets}",$MimeDefangNoTrustMyNets,false,"{not_trust_mynets_text}");


	
	$form[]=$tpl->field_section("{service_parameters}");
	
	$form[]=$tpl->field_checkbox("MX_DEBUG","{debug}",$DebugMimeFilter,false,null);
	$form[]=$tpl->field_numeric("MX_TMPFS","{workingdir_in_memory} (MB)",$Param["MX_TMPFS"],"{workingdir_in_memory_text}");
	$form[]=$tpl->field_numeric("MX_REQUESTS","{max_requests}",$Param["MX_REQUESTS"],"{MX_REQUESTS_TEXT}");
	$form[]=$tpl->field_numeric("MX_MINIMUM","{MX_MINIMUM}",$Param["MX_MINIMUM"],"{MX_MINIMUM_TEXT}");
	$form[]=$tpl->field_numeric("MX_MAXIMUM","{MX_MAXIMUM}",$Param["MX_MAXIMUM"],"");
	$form[]=$tpl->field_numeric("MX_MAX_RSS","{MX_MAX_RSS}",$Param["MX_MAX_RSS"],"{MX_MAX_RSS_TEXT}");
	$form[]=$tpl->field_numeric("MX_BUSY","{MX_BUSY} ({seconds})",$Param["MX_BUSY"],"{MX_BUSY_TEXT}");
	$form[]=$tpl->field_numeric("MX_MAX_AS","{MX_MAX_AS}",$Param["MX_MAX_AS"],"{MX_MAX_AS_TEXT}");
	$html[]=$tpl->form_outside("{services}", $form,null,"{apply}",$mimedefang_restart,"AsPostfixAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$Param=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangServiceOptions"));
	$sock=new sockets();
	foreach ($_POST as $key=>$val){
		if(preg_match("#^MX_#", $key)){
			$Param[$key]=$val;
			continue;
		}
		
		$sock->SET_INFO($key, $val);
		
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($Param)), "MimeDefangServiceOptions");
	$sock->SET_INFO("DebugMimeFilter", $_POST["MX_DEBUG"]);
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		echo $tpl->FATAL_ERROR_SHOW_128("{license_error}");
		return;
		
	}
	
	$sock->getFrameWork("mimedefang.php?status=yes");
	$ini->loadFile(PROGRESS_DIR."/mimedefang.status");
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/mimedefang.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/mimedefang.progress.log";
	$ARRAY["CMD"]="mimedefang.php?progress=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-mimedefang','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$mimedefang_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedefang-restart');";

    $lib_mem=new lib_memcached();
	$MIMEDEFANG_WORKERS=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MIMEDEFANG_WORKERS"));
    $MIMEDEFANG_MSGS=FormatNumber(intval($lib_mem->getKey("MIMEDEFANG_COUNTER")));
	$WOKERNUM=0;
	foreach ($MIMEDEFANG_WORKERS as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^Max workers.*?([0-9]+)#", $line,$re)){$MAX_WORKERS=$re[1];continue;}
		if(preg_match("#^Worker.*?:(.+)#",$line,$re)){
			$WKS=trim($re[1]);
			VERBOSE("$line --> $WKS", __LINE__);
			if($WKS<>"stopped"){$WOKERNUM++;}
			continue;
		}
		VERBOSE("$line --> NOT FOUND", __LINE__);
	
	}
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px' valign='top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td valign='top'>
	<div class=\"ibox\" style='border-top:0px'>
    	<div class=\"ibox-content\" style='border-top:0px'>". 
    	$tpl->SERVICE_STATUS($ini, "APP_MIMEDEFANG",$mimedefang_restart).
    	$tpl->SERVICE_STATUS($ini, "APP_MIMEDEFANGX",$mimedefang_restart);
	
	
	$html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-envelope fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {processed_messages}</span>
	<h2 class=\"font-bold\">$MIMEDEFANG_MSGS</h2>
	</div>
	</div>
	</div>";
	
	
	$html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-cogs fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {workers} ($WOKERNUM/$MAX_WORKERS)</span>
	<h2 class=\"font-bold\">$WOKERNUM</h2>
	</div>
	</div>
	</div>";
    	
    	
    	
    $html[]="</div>
    </div></td></tr>";
	
	$html[]="</table></td>";
	


	$lib_mem=new lib_memcached();




	$MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	$MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangClamav"));
	$ClamAVDaemonInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonInstalled"));
	if($ClamAVDaemonInstalled==0){$MimeDefangClamav=0;}
	
	if($MimeDefangSpamAssassin==0){
		$spam=$tpl->widget_h("gray","fas fa-location-slash","{disabled}","{Anti-Spam Engine}");
		
	}else{
		$spam=$tpl->widget_h("green","fas fa-location-slash","{enabled}","{Anti-Spam Engine}");
		$CurrentSPAMASSDBArtica=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurrentSPAMASSDBArtica"));
        $MimeDefangAutoWhiteList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));
        $SMTP_SUM_AUTOWHITE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMTP_SUM_AUTOWHITE"));
        $SMTP_SUM_AUTOWHITE=FormatNumber($SMTP_SUM_AUTOWHITE);
		$time=0;
		$RulesNumber=0;
		foreach ($CurrentSPAMASSDBArtica as $index=>$MAIN){
			if(!isset($MAIN["TIME"])){continue;}
			if(intval($MAIN["TIME"])>$time){$time=$MAIN["TIME"];}
			$RulesNumber=$RulesNumber+intval($MAIN["RULES"]);
		}
		if($time==0){
			$spamDB=$tpl->widget_h("gray","fas fa-database","{not_updated}","{artica_antispam_rules}");
		}else{
			$RulesNumber=FormatNumber($RulesNumber);
			$date=$tpl->time_to_date($time,true);
			$spamDB=$tpl->widget_h("green","fas fa-database",$RulesNumber."<br><small style='color:white;font-size:10px'>{updated}: $date</small>","{artica_antispam_rules}");
		}

		if($MimeDefangAutoWhiteList==1){
            $AutoWhiteList=$tpl->widget_h("green","fas fa-database",$SMTP_SUM_AUTOWHITE,"{smtp_AutoWhiteList}");
        }else{
            $AutoWhiteList=$tpl->widget_h("gray","fas fa-database",$SMTP_SUM_AUTOWHITE,"{smtp_AutoWhiteList} ({disabled})");
        }

		
	}
	if($MimeDefangClamav==0){
		$av=$tpl->widget_h("gray","fab fa-medrt","{disabled}","{antivirus_engine}");
	}else{
		$av=$tpl->widget_h("green","fab fa-medrt","{enabled}","{antivirus_engine}");
	}
	
	$SMTP_SUM_QUAR=intval($sock->GET_INFO("SMTP_SUM_QUAR"));
	$SMTP_SUM_QUARSIZE=FormatBytes(intval($sock->GET_INFO("SMTP_SUM_QUARSIZE"))/1024);
	
	$SMTP_SUM_BACKUP=intval($sock->GET_INFO("SMTP_SUM_BACKUP"));
	$SMTP_SUM_BACKUPSIZE=FormatBytes(intval($sock->GET_INFO("SMTP_SUM_BACKUPSIZE"))/1024);

	
	
	if($SMTP_SUM_QUAR==0){
		$quarantine=$tpl->widget_h("gray","fas fa-archive","-","{quarantine}");
	}else{
		$quarantine=$tpl->widget_h("green","fas fa-archive","$SMTP_SUM_QUAR<br><span style='font-size:10px'>$SMTP_SUM_QUARSIZE</span>","{quarantine}");
	}
	if($SMTP_SUM_BACKUP==0){
		$backup=$tpl->widget_h("gray","fas fa-hdd","-","{backuped_emails}");
	}else{
		$backup=$tpl->widget_h("green","fas fa-hdd","$SMTP_SUM_BACKUP<br><span style='font-size:10px'>$SMTP_SUM_BACKUPSIZE</span>","{backuped_emails}");
	}
	

	
	$html[]="<td style='width:99%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:20px;vertical-align:top'>";
	$html[]="<div class=\"col-lg-10\">";
	$html[]=$spam.$spamDB.$AutoWhiteList.$quarantine.$backup.$av;
	
			
			
			
	$html[]="</div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	
	$html[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}
function stats_mailqueue(){
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailqueue-day.png";
	$f[]="postfix_mailqueue-week.png";
	$f[]="postfix_mailqueue-month.png";
	$f[]="postfix_mailqueue-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	
}

function stats_mailstats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailstats-day.png";
	$f[]="postfix_mailstats-week.png";
	$f[]="postfix_mailstats-month.png";
	$f[]="postfix_mailstats-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function stats_mailvolume(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailvolume-day.png";
	$f[]="postfix_mailvolume-week.png";
	$f[]="postfix_mailvolume-month.png";
	$f[]="postfix_mailvolume-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}