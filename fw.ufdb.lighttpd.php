<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["UfdbWebInterface"])){save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_UFDBWEBSIMPLE}</h1>
	<p>{APP_UFDBWEBSIMPLE_EXPLAIN}</p>
	</div>

	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-ufdblight-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-ufdblight-service','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$UfdbWebInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebInterface"));
	$UfdbWebHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebHTTPPort"));
	$UfdbWebHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebHTTPSPort"));
	$UfdbWebCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebCertificate"));
	$UfdbWebInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebInterface"));
	$UfdbWebTemplate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebTemplate"));
	$UfdbWebHTTPSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebHTTPSEnabled"));
	$UfdbWebTemplate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebTemplate"));
	$UFDBGUARD_TITLE_1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebTitle1"));
	$UFDBGUARD_PARA1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebParagraph1"));
	$UFDBGUARD_TITLE_2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebTitle2"));
	$UFDBGUARD_PARA2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbWebParagraph2"));
	if($UfdbWebTemplate==0){$UfdbWebTemplate=1;}
	
	
	if($UfdbWebHTTPPort==0){$UfdbWebHTTPPort=9020;}
	if($UfdbWebHTTPSPort==0){$UfdbWebHTTPSPort=9025;}
	
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	
	$form[]=$tpl->field_interfaces("UfdbWebInterface", "{listen_interface}", $UfdbWebInterface);
	$form[]=$tpl->field_numeric("UfdbWebHTTPPort", "{listen_port}", $UfdbWebHTTPPort);
	$form[]=$tpl->field_checkbox("UfdbWebHTTPSEnabled","{use_ssl}",$UfdbWebHTTPSEnabled,"UfdbWebCertificate,UfdbWebHTTPSPort");
	$form[]=$tpl->field_numeric("UfdbWebHTTPSPort", "{listen_port} (SSL)", $UfdbWebHTTPSPort);
	$form[]=$tpl->field_array_hash($sslcertificates, "UfdbWebCertificate", "{certificate}", $UfdbWebCertificate);
	$form[]=$tpl->field_section("{template}");
	
	if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
	if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
	if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
	if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
	
	
	
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL("SELECT TemplateName,ID FROM templates_manager");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$TemplateName=utf8_encode($ligne["TemplateName"]);
		$ID=$ligne["ID"];
		$XTPLS[$ID]=$TemplateName;
	}
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ufdbweb.enable.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/ufdbweb.enable.progress.log";
	$ARRAY["CMD"]="ufdbguard.php?ufdbweb-simple-restart=yes";
	$ARRAY["TITLE"]="{reconfigure}";
	$ARRAY["AFTER"]="blur();";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
	
	$form[]=$tpl->field_array_hash($XTPLS, "UfdbWebTemplate", "{template}", $UfdbWebTemplate);
	$form[]=$tpl->field_text("UfdbWebTitle1", "{titletext} 1", $UFDBGUARD_TITLE_1);
	$form[]=$tpl->field_textarea("UfdbWebParagraph1", "{parapgraph} 1", $UFDBGUARD_PARA1,"450px","250px");
	$form[]=$tpl->field_text("UfdbWebTitle2", "{titletext} 2", $UFDBGUARD_TITLE_2);
	$form[]=$tpl->field_textarea("UfdbWebParagraph2", "{parapgraph} 2", $UFDBGUARD_PARA2,"450px","250px");
	$html=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsDansGuardianAdministrator");
	echo $html;
	
	
}

function save(){
	$sock=new sockets();
	while (list ($num, $vl) = each ($_POST) ){
		$vl=url_decode_special_tool($vl);
		$sock->SaveConfigFile($vl, $num);
	}
}