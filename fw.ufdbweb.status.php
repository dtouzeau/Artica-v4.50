<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){tablestart();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SquidGuardWebUseExternalUri"])){save();exit;}
if(isset($_GET["wizard-js"])){wizard_js();exit;}
if(isset($_GET["wizard-start"])){wizard_start();exit;}
if(isset($_GET["wizard-step1"])){wizard_step_1();exit;}
if(isset($_POST["WIZARD_HOSTNAME"])){wizard_save();exit;}
if(isset($_GET["wizard-step2"])){wizard_step_2();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{banned_page_webservice}","fas fa-ban",
        "{APP_UFDB_HTTP_EXPLAIN}","$page?table=yes","error-page-status",
        "progress-ufdbweb-restart",false,"table-loader-ufdbweb-service");

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_UFDB_HTTP}",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function wizard_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{webfiltering_error_page}: {wizard}","$page?wizard-start=yes");
}
function wizard_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='wizard-webfiltering-progress'></div>
    <div id='wizard-webfiltering-page'></div>
    <script>LoadAjax('wizard-webfiltering-page','$page?wizard-step1=yes')</script>";
}

function wizard_save(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    foreach ($_POST as $index=>$value){
        $_SESSION[$index]=$value;
        $Array[$index]=$value;

    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDB_WIZARD_ERROR_PAGE",serialize($Array));

}

function wizard_step_1()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    if (intval($_SESSION["WIZARD_HTTP_PORT"]) == 0) {
        $_SESSION["WIZARD_HTTP_PORT"] = 80;
    }
    if (intval($_SESSION["WIZARD_SSL_PORT"]) == 0) {
        $_SESSION["WIZARD_SSL_PORT"] = 443;
    }

    $form[] = $tpl->field_text("WIZARD_HOSTNAME", "{hostname}", $_SESSION["WIZARD_HOSTNAME"]);
    $form[] = $tpl->field_numeric("WIZARD_HTTP_PORT", "{listen_port}", $_SESSION["WIZARD_HTTP_PORT"]);
    $form[] = $tpl->field_numeric("WIZARD_SSL_PORT", "{listen_port} (SSL)", $_SESSION["WIZARD_SSL_PORT"]);

    $explain = "{webfiltering_error_page_wizard_explain}";

    $html = $tpl->form_outside("{webfiltering_error_page}: {wizard}", $form, $explain, "{next}", "LoadAjax('wizard-webfiltering-page','$page?wizard-step2=yes')", "AsDansGuardianAdministrator", true);

    echo $tpl->_ENGINE_parse_body($html);
}

function wizard_step_2(){
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td><strong>HTTP:</strong>";
    // Pentest 2026-11-04 : nom d'hote/ports de l'assistant (donnee externe) affiche sans encodage (XSS stockee).
    $html[]="<td><strong>http://".req_html($_SESSION["WIZARD_HOSTNAME"]).":".req_html($_SESSION["WIZARD_HTTP_PORT"])."</strong>";
    $html[]="</tr>";
    $html[]="<td><strong>HTTPs:</strong>";
    $html[]="<td><strong>https://".req_html($_SESSION["WIZARD_HOSTNAME"]).":".req_html($_SESSION["WIZARD_SSL_PORT"])."</strong>";
    $html[]="</tr>";
    $html[]="</table>";

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ufdberror.compile.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ufdberror.compile.log";
    $ARRAY["CMD"]="/system/wizards/weberror-page";   // articarest wizards module
    $ARRAY["PRIV"]="AsDansGuardianAdministrator|AsSquidAdministrator";
    $ARRAY["TITLE"]="{webfiltering_error_page}";
    $ARRAY["AFTER"]="LoadAjax('webpage-error-status','$page?table2=yes');dialogInstance1.close();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=wizard-webfiltering-progress')";
    $html[]="<script>$jsrestart</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function tablestart(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='webpage-error-status'></div>
    <script>LoadAjax('webpage-error-status','$page?table2=yes');</script>
";

}
//LoadAjax('table-loader-ufdbweb-service','$page?table=yes');
function table2(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
	
	$html[]="<table style='width:100%;margin-top:10px' >";
	$html[]="<tr>";
	$html[]="<td style='width:450px;vertical-align:top'>";
	$html[]="<div id='ufdbweb-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";

    $SquidGuardWebWebServiceID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebWebServiceID"));
    $SquidGuardWebUseInternalUri=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebUseInternalUri"));
	$SquidGuardWebUseExternalUri=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebUseExternalUri"));
	$SquidGuardWebExternalUri2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUri2"));
	$SquidGuardWebExternalUri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUri"));
	$SquidGuardWebExternalUriSSL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriSSL"));
    $SquidGuardWebSSLtoSSL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebSSLtoSSL"));
    $SquidGuardDenyConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardDenyConnect"));

    if($SquidGuardWebExternalUri==null){$SquidGuardWebExternalUri=$SquidGuardWebExternalUri2;}
	if($SquidGuardWebExternalUri==null){$SquidGuardWebExternalUri="http://articatech.net/block.html";}
	if($SquidGuardWebExternalUriSSL==null){$SquidGuardWebExternalUriSSL="articatech.net/block.html";}

    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/type/6");
    $data=json_decode($resp,true);
    $results=(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"])) ? $data["rows"] : [];
    usort($results,function($a,$b){return intval(isset($a["zorder"])?$a["zorder"]:0) - intval(isset($b["zorder"])?$b["zorder"]:0);});

    $MAINS=array();
    foreach ($results as $index=>$ligne){
        if(intval(isset($ligne["enabled"])?$ligne["enabled"]:0)!==1){continue;}
        $MAINS[$ligne["ID"]]=$ligne["servicename"];
    }

    $form[] = $tpl->field_section("{main_settings}", "");
    $form[]=  $tpl->field_checkbox("SquidGuardWebSSLtoSSL","{HTTPSWtoHTTPSW}",$SquidGuardWebSSLtoSSL);
    $form[]=  $tpl->field_checkbox("SquidGuardDenyConnect","{SquidGuardDenyConnect}",$SquidGuardDenyConnect,false,"{SquidGuardDenyConnect_explain}");

    if($EnableNginx==1) {
        $form[] = $tpl->field_section("{UfdbUseInternalWebPage}", "{UfdbUseInternalPage_explain}");
        $form[] = $tpl->field_checkbox("SquidGuardWebUseInternalUri", "{enabled}", $SquidGuardWebUseInternalUri, "SquidGuardWebWebServiceID", "{UfdbUseInternalPage_explain}");
        $form[] = $tpl->field_array_hash($MAINS, "SquidGuardWebWebServiceID", "{website}", $SquidGuardWebWebServiceID);
    }
    $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_explain}");


	
	$form[]=$tpl->field_section("{UfdbUseGlobalWebPage}","{UfdbUseGlobalWebPage_explain}");
	$form[]=$tpl->field_checkbox("SquidGuardWebUseExternalUri","{enabled}",$SquidGuardWebUseExternalUri,"SquidGuardWebExternalUri,SquidGuardWebExternalUriSSL","{UfdbUseGlobalWebPage_explain}");
	$form[]=$tpl->field_text("SquidGuardWebExternalUri", "{fulluri}", $SquidGuardWebExternalUri,false);
	$form[]=$tpl->field_text("SquidGuardWebExternalUriSSL", "{fulluri} HTTPS", $SquidGuardWebExternalUriSSL,false);


    $jsrestart=$tpl->framework_buildjs(
        "/ufdb/recompile",
        "ufdbguard.compile.progress",
        "ufdb.restart.log",
        "progress-ufdbweb-restart",
        "LoadAjax('webpage-error-status','$page?table2=yes');");

    if($EnableNginx==1) {
        $tpl->form_add_button("{wizard}", "Loadjs('$page?wizard-js=yes')");
    }
	$html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",$jsrestart,"AsDansGuardianAdministrator");
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>LoadAjaxTiny('ufdbweb-status','$page?status=yes');</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){
	$sock=new sockets();
	$tpl=new template_admin();

	if($_POST["SquidGuardWebUseInternalUri"]==1){
	    $_POST["SquidGuardWebUseExternalUri"]=0;
    }

    $tpl->SAVE_POSTs();
    $sock->SET_INFO("SquidGuardWebExternalUri2",$_POST["SquidGuardWebExternalUri"]);
}

function status(){
    $tpl=new template_admin();
	$SquidGuardWebUseExternalUri=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebUseExternalUri"));
    $SquidGuardWebUseInternalUri=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebUseInternalUri"));


    if($SquidGuardWebUseInternalUri==0){
        $html[]="<div style='vertical-align:top;width:335px'>
		<div class='widget gray-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
			<i class='fas fa-parking-slash fa-4x'></i>
			<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>{UfdbUseInternalWebPage}</H2>
			<H2 class='font-bold no-margins'>{disabled}</H2>
		</div>
	</div>";

    }else{

        $html[]="<div style='vertical-align:top;width:335px'>
		<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
		<i class='fas fa-parking fa-4x'></i>
		<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>{UfdbUseInternalWebPage}</H2>
		<H2 class='font-bold no-margins'>{enabled}</H2>
			</div>
		</div>
						
					";
    }


	if($SquidGuardWebUseExternalUri==0){
		$html[]="<div style='vertical-align:top;width:335px'>
		<div class='widget gray-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
			<i class='fas fa-parking-slash fa-4x'></i>
			<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>{UfdbUseGlobalWebPage}</H2>
			<H2 class='font-bold no-margins'>{disabled}</H2>
		</div>
	</div>";
	
	}else{
		
		$html[]="<div style='vertical-align:top;width:335px'>
		<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
		<i class='fas fa-parking fa-4x'></i>
		<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>{UfdbUseGlobalWebPage}</H2>
		<H2 class='font-bold no-margins'>{enabled}</H2>
			</div>
		</div>
						
					";
	}


	
	echo $tpl->_ENGINE_parse_body($html);


}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}