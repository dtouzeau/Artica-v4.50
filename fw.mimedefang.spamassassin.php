<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["NotTrustLocalNet"])){Save();exit;}



page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_MIMEDEFANG_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangVersion");
	$title=$tpl->_ENGINE_parse_body("{APP_VALVUAD} &raquo;&raquo; {Anti-Spam Engine}");
	$js="LoadAjax('table-spamassassin','$page?tabs=yes');";
	
	$MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	
	
	if($MimeDefangSpamAssassin==0){
		
		$error=$tpl->FATAL_ERROR_SHOW_128("{this_feature_is_disabled}");
		$html="
		<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>
		<p>{SPAMASSASSIN_TEXT}</p>
		
		</div>
		
		</div>$error	<script>
	$.address.state('/');
	$.address.value('/postfix-policies-antispam');
	$.address.title('".html_entity_decode($title)."');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin($title,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
		
		return;
	}

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>
	<p>{SPAMASSASSIN_TEXT}</p>

	</div>

	</div>



	<div class='row'><div id='progress-mimedefangspam-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-spamassassin'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/postfix-policies-antispam');
	$.address.title('".html_entity_decode($title)."');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin($title,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{parameters}"]="$page?parameters=yes";
	$array["{rules}"]="fw.spamassassin.rules.php";
	$array["{escrap_rules}"]="fw.spamassassin.escrap.php";
	$array["{subject_rules}"]="fw.spamassassin.subjects.php";
	$array["{rules_on_urls}"]="fw.spamassassin.urls.php";
	$array["{body_rules}"]="fw.spamassassin.body.php";
	
	
	
	$array["{whitelists}"]="fw.spamassassin.white.php";
	//$array["{statistics} {messages}"]="$page?stats-mailstats=yes";
	//$array["{statistics} {volumes}"]="$page?stats-mailvolume=yes";
	echo $tpl->tabs_default($array);
}

function parameters(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$times[10080]=$tpl->javascript_parse_text("7 {days}");
	$times[14400]=$tpl->javascript_parse_text("10 {days}");
	$times[21600]=$tpl->javascript_parse_text("15 {days}");
	$times[43200]=$tpl->javascript_parse_text("1 {month}");
	$times[129600]=$tpl->javascript_parse_text("3 {months}");
	
	
	$EnableSpamassassinWrongMX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamassassinWrongMX"));
	$EnableSpamassassinDnsEval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamassassinDnsEval"));
	$EnableSpamassassinURIDNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamassassinURIDNSBL"));
	$EnableSpamAssassinFreeMail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamAssassinFreeMail"));
	$EnableDecodeShortURLs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDecodeShortURLs"));
	$enable_dkim_verification=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("enable_dkim_verification"));
	$SpamAssassinUrlScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinUrlScore"));
	$SpamAssassinScrapScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinScrapScore"));
	$SpamAssassinSubjectsScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinSubjectsScore"));
	$SpamAssassinBodyScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinBodyScore"));
	$SpamAssassinWhiteListScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinWhiteListScore"));
	$SpamAssassinUseBayes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinUseBayes"));
	$SpamAssassinBayesAutoLearn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinBayesAutoLearn"));
	$ScoreWithSpamVirus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ScoreWithSpamVirus"));
	
	if($ScoreWithSpamVirus==0){$ScoreWithSpamVirus=20;}
	$XSpamStatusHeaderScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XSpamStatusHeaderScore"));
	if($XSpamStatusHeaderScore==0){$XSpamStatusHeaderScore=4;}
	
	
	$SpamAssassinRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinRequiredScore"));
	if($SpamAssassinRequiredScore==0){$SpamAssassinRequiredScore=8;}
	
	$block_with_required_score=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssBlockWithRequiredScore"));
	if($block_with_required_score==0){$block_with_required_score=15;}
	

	
	$NotTrustLocalNet=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NotTrustLocalNet"));
	$MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
	$MimeDefangQuarteMail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuarteMail"));
	$MimeDefangQuartDest=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuartDest"));
	$MimeDefangSpamMaxSize=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamMaxSize"));

	if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	if($SpamAssassinScrapScore==0){$SpamAssassinScrapScore=6;}
	if($SpamAssassinUrlScore==0){$SpamAssassinUrlScore=9;}
	if($SpamAssassinSubjectsScore==0){$SpamAssassinSubjectsScore=3;}
	if($SpamAssassinBodyScore==0){$SpamAssassinBodyScore=3;}
	
	
	
	if($SpamAssassinWhiteListScore>=0){$SpamAssassinWhiteListScore=-10;}
	if($MimeDefangSpamMaxSize<100){$MimeDefangSpamMaxSize=409600;}
	
	$MimeDefangSpamMaxSize=round($MimeDefangSpamMaxSize/1024);
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.progress.log";
	$ARRAY["CMD"]="mimedefang.php?reload=yes";
	$ARRAY["TITLE"]="{reloading_service}";
	//$ARRAY["AFTER"]="LoadAjax('table-spamassassin','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$mimedefang_reload="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedefangspam-restart');";	
	
	
	$form[]=$tpl->field_checkbox("NotTrustLocalNet","{NotTrustLocalNet}",$NotTrustLocalNet,false,"{NotTrustLocalNet_explain}");
	$form[]=$tpl->field_checkbox("SpamAssassinUseBayes","{use_bayes}",$SpamAssassinUseBayes,false,null);
	$form[]=$tpl->field_checkbox("SpamAssassinBayesAutoLearn","{auto_learn}",$SpamAssassinBayesAutoLearn,false,null);
	
	
	$form[]=$tpl->field_numeric("MimeDefangSpamMaxSize","{MaxSize} (KB)",$MimeDefangSpamMaxSize,"{SpamAssassinMaxSize_text}");
	$form[]=$tpl->field_numeric("SpamAssassinUrlScore","{SpamAssassinUrlScore}",$SpamAssassinUrlScore,"{SpamAssassinUrlScore_text}");
	$form[]=$tpl->field_numeric("SpamAssassinScrapScore","{SpamAssassinScrapScore}",$SpamAssassinScrapScore,"{SpamAssassinScrapScore_text}");
	$form[]=$tpl->field_numeric("SpamAssassinSubjectsScore","{SpamAssassinSubjectsScore}",$SpamAssassinSubjectsScore,"{SpamAssassinSubjectsScore_text}");
	$form[]=$tpl->field_numeric("SpamAssassinBodyScore","{SpamAssassinBodyScore}",$SpamAssassinBodyScore,"{SpamAssassinBodyScore_text}");
	$form[]=$tpl->field_numeric("ScoreWithSpamVirus","{ScoreWithSpamVirus}",$ScoreWithSpamVirus,"{ScoreWithSpamVirus_text}");
	
	
	
	$form[]=$tpl->field_section("{global_scores}");
	$form[]=$tpl->field_numeric("SpamAssassinWhiteListScore","{SpamAssassinWhiteListScore}",$SpamAssassinWhiteListScore,null);
	$form[]=$tpl->field_numeric("XSpamStatusHeaderScore", "{X-Spam-Status-header}", $XSpamStatusHeaderScore,"{X-Spam-Status-header-text}");
	$form[]=$tpl->field_numeric("SpamAssBlockWithRequiredScore", "{block_with_required_score}", $block_with_required_score,"{block_with_required_score_text}");
	
	$form[]=$tpl->field_section("{quarantine}");
	$form[]=$tpl->field_numeric("SpamAssassinRequiredScore", "{required_score_quarantine}", $SpamAssassinRequiredScore,"{required_score_text}");
	$form[]=$tpl->field_array_hash($times, "MimeDefangMaxQuartime", "{retention} ({quarantine})", $MimeDefangMaxQuartime);
	$form[]=$tpl->field_checkbox("MimeDefangQuarteMail","{forward_quarantine_messages}",$MimeDefangQuarteMail,false,"{forward_quarantine_messages_text}");
	$form[]=$tpl->field_email("MimeDefangQuartDest", "{quarantine_email_address}", $MimeDefangQuartDest);
	$form[]=$tpl->field_section("{plugins}");
	$form[]=$tpl->field_checkbox("EnableSpamassassinWrongMX","WrongMX",$EnableSpamassassinWrongMX,false,"{WrongMXPlugin}");
	$form[]=$tpl->field_checkbox("EnableSpamassassinURIDNSBL","URIDNSBL",$EnableSpamassassinURIDNSBL,false,"{URIDNSBL_explain}");
	$form[]=$tpl->field_checkbox("EnableSpamAssassinFreeMail","FreeMail",$EnableSpamAssassinFreeMail,false,"{EnableSpamAssassinFreeMail_explain}");
	$form[]=$tpl->field_checkbox("EnableDecodeShortURLs","{enable_DecodeShortURLs}",$EnableDecodeShortURLs,false,"{DecodeShortURLs_explain}");
	$form[]=$tpl->field_checkbox("enable_dkim_verification","{enable_dkim_verification}",$enable_dkim_verification,false,"{dkim_about}<br>{dkim_about2}");
	$html[]=$tpl->form_outside(null, $form,null,"{apply}",$mimedefang_reload,"AsPostfixAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$tpl=new template_admin();
	
	if(isset($_POST["MimeDefangSpamMaxSize"])){$_POST["MimeDefangSpamMaxSize"]=$_POST["MimeDefangSpamMaxSize"]*1024;}
	
	$tpl->SAVE_POSTs();
}


function table(){
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}