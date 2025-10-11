<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_POST["ModSecurityDoS"])){Save();exit;}
if(isset($_POST["ModSecurityAction"])){Save();exit;}
if(isset($_POST["ModSecurityHTTPBL"])){Save();exit;}
if(isset($_POST["ModSecurityRetentionDays"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["statistics-parameters"])){statistics_engine();exit;}
if(isset($_GET["protocols-js"])){protocols_js();exit;}
if(isset($_GET["protocols-popup"])){protocols_popup();exit;}
if(isset($_POST["PROTO_GET"])){protocols_save();exit;}

if(isset($_GET["params-main-js"])){params_main_js();exit;}
if(isset($_GET["params-main-popup"])){params_main_popup();exit;}

if(isset($_GET["params-honeypot-js"])){params_honeypopup_js();exit;}
if(isset($_GET["params-honeypot-popup"])){params_honeypopup_popup();exit;}

if(isset($_GET["params-dos-js"])){params_dos_js();exit;}
if(isset($_GET["params-dos-popup"])){params_dos_popup();exit;}

if(isset($_GET["params-decisionip-js"])){params_decisionip_js();exit;}
if(isset($_GET["params-decisionip-popup"])){params_decisionip_popup();exit;}
if(isset($_POST["DecisionIPCheck"])){params_decisionip_save();exit;}
if(isset($_GET["params-statistics-js"])){params_statistics_js();exit;}



page();

function protocols_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{allow}: {method}","$page?protocols-popup=yes");
    return true;
}
function params_main_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{parameters}","$page?params-main-popup=yes");
    return true;
}
function params_honeypopup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("Honey Pot HTTP Blacklist","$page?params-honeypot-popup=yes");
    return true;
}
function params_dos_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{dos_protection}","$page?params-dos-popup=yes");
    return true;
}
function params_decisionip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{DecisionIPCheck}","$page?params-decisionip-popup=yes");
    return true;
}
function params_statistics_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{statistics_parameters}","$page?statistics-parameters=yes");
    return true;
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $ModSecurityPatternCount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternCount"));
    $ModSecurityPatternVersion=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternVersion"));
	$title=$tpl->_ENGINE_parse_body("Web Firewall");

    $html=$tpl->page_header("$title <H3>$ModSecurityPatternCount {rules} v$ModSecurityPatternVersion</h3>",
        "fas fa-shield","{ModSecurityExplain}","$page?tabs=yes","waf","progress-waf");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: $title",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function protocols_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $available = explode(" ", "GET HEAD POST OPTIONS PUT PATCH DELETE CHECKOUT COPY DELETE LOCK MERGE MKACTIVITY MKCOL MOVE PROPFIND PROPPATCH PUT UNLOCK TRACE CONNECT");

    $ModSecurityProtocols = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityProtocols"));
    if (!is_array($ModSecurityProtocols)) {
        $ModSecurityProtocols = array();
    }
    if (count($ModSecurityProtocols) == 0) {
        $ModSecurityProtocols = array("GET" => 1, "HEAD" => 1, "POST" => 1, "OPTIONS" => 1);
    }

    foreach ($available as $proto) {
        $proto = trim($proto);
        if ($proto == null) {
            continue;
        }
        $value = 0;
        if (isset($ALREADY[$proto])) {
            continue;
        }
        if (isset($ModSecurityProtocols[$proto])) {
            $value = intval($ModSecurityProtocols[$proto]);
        }
        $form[] = $tpl->field_checkbox("PROTO_$proto", $proto, $value);
        $ALREADY[$proto] = true;

    }
    $html[] = $tpl->form_outside(null, $form, "{modsecurity_allow_protocol}", "{apply}", "LoadAjaxSilent('modsec-params','$page?parameters=yes');dialogInstance1.close();", "AsWebMaster", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function protocols_save():bool{
    $tpl=new template_admin();
    $MAIN=array();
    $tpl->CLEAN_POST();
    foreach ($_POST as $key=>$val){
        if(!preg_match("#^PROTO_([A-Z]+)#",$key,$re)){continue;}
        $MAIN[$re[1]]=$val;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ModSecurityProtocols",serialize($MAIN));
    return admin_tracks_post("Save Reverse-proxy Web application Protocol to scan protocols");
}

function tabs(){
	$tpl=new template_admin();
	$array["{parameters}"]="fw.modsecurity.php?parameters-start=yes";
    $array["{default_rules}"]="fw.modsecurity.defrules.php";
    echo $tpl->tabs_default($array);
}
function parameters_start():bool{
    $page=CurrentPageName();
    echo "<div id='modsec-params' style='margin-top:10px'></div><script>LoadAjaxSilent('modsec-params','$page?parameters=yes');</script>";
    return true;
}

function statistics_engine(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ModSecurityRetentionDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityRetentionDays"));
    if($ModSecurityRetentionDays==0){$ModSecurityRetentionDays=7;}

    $ModSecurityMaxTempFolderSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxTempFolderSize"));
    if($ModSecurityMaxTempFolderSize==0){$ModSecurityMaxTempFolderSize=5;}

    $ModSecurityMaxModSecSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxModSecSize"));
    if($ModSecurityMaxModSecSize==0){$ModSecurityMaxModSecSize=500;}

    $ModSecuritySecRotate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecuritySecRotate"));



    for($i=1;$i<366;$i++){
        $sday="{day}";
        if($i>1){$sday="{days}";}
        $st="$i $sday";
        if($i==7){$st="7 - 1 {week}";}
        if($i==14){$st="14 - 2 {weeks}";}
        if($i==30){$st="30 - 1 {month}";}
        if($i==60){$st="60 - 2 {months}";}
        if($i==90){$st="90 - 3 {months}";}
        if($i==365){$st="365 - 1 {year}";}
        $retention_days[$i]=$st;
    }
    for($i=1;$i<21;$i++){
        $tempsize[$i]="{$i}GB";
    }


    $severity_icon[0]="<span class='label label-danger'>{emergency}</span>";
    $severity_icon[1]="<span class='label label-danger'>{alert}</span>";
    $severity_icon[2]="<span class='label label-danger'>{critic}</span>";
    $severity_icon[3]="<span class='label label-danger'>{error}</span>";
    $severity_icon[4]="<span class='label label-warning'>{warning}</span>";
    $severity_icon[5]="<span class='label label-info'>{notice}</span>";
    $severity_icon[6]="<span class='label label'>Info</span>";

    $ModSecuritySaveReports=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecuritySaveReports"));
    if($ModSecuritySaveReports==null){
        $ModSecuritySaveReportsArr[0]=1;
        $ModSecuritySaveReportsArr[1]=1;
        $ModSecuritySaveReportsArr[2]=1;
        $ModSecuritySaveReportsArr[3]=1;
        $ModSecuritySaveReportsArr[4]=1;
        $ModSecuritySaveReportsArr[5]=0;
        $ModSecuritySaveReportsArr[6]=0;
    }else{
        $ModSecuritySaveReportsArr=unserialize($ModSecuritySaveReports);
    }


    $form[]=$tpl->field_array_hash($retention_days,"ModSecurityRetentionDays","nonull:{retention_days}",$ModSecurityRetentionDays,false,"{SuricataPurges}");

    $form[]=$tpl->field_array_hash($tempsize,"ModSecurityMaxTempFolderSize","nonull:{working_directory} ({maxsize})",$ModSecurityMaxTempFolderSize,false,"{MaxTempFolderSize}");

    $form[]=$tpl->field_section("{realtime_events_squid}","");
    $form[]=$tpl->field_numeric("ModSecurityMaxModSecSize",
        "{remove_if_files_exceed} (MB)", $ModSecurityMaxModSecSize,null);
    $form[]=$tpl->field_checkbox("ModSecuritySecRotate",
        "{rotate_logs}", $ModSecuritySecRotate,false);



    $form[]=$tpl->field_section("{reports_storage}","{report_storage_level}");
    $form[]=$tpl->field_checkbox("modsec_report_0","{emergency}",$ModSecuritySaveReportsArr[0]);
    $form[]=$tpl->field_checkbox("modsec_report_1","{alert}",$ModSecuritySaveReportsArr[1]);
    $form[]=$tpl->field_checkbox("modsec_report_2","{critic}",$ModSecuritySaveReportsArr[2]);
    $form[]=$tpl->field_checkbox("modsec_report_3","{error}",$ModSecuritySaveReportsArr[3]);
    $form[]=$tpl->field_checkbox("modsec_report_4","{warning}",$ModSecuritySaveReportsArr[4]);
    $form[]=$tpl->field_checkbox("modsec_report_5","{notice}",$ModSecuritySaveReportsArr[5]);
    $form[]=$tpl->field_checkbox("modsec_report_6","{info}",$ModSecuritySaveReportsArr[6]);

    $html[]=$tpl->form_outside("", $form,null,"{apply}",null,"AsWebMaster",true);
    echo $tpl->_ENGINE_parse_body($html);

}

function params_honeypopup_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ModSecurityHTTPBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityHTTPBL"));
    $SecHttpBlKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlKey"));
    $SecHttpBlSE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSE"));
    $SecHttpBlSus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSus"));
    $SecHttpBlHar=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlHar"));
    $SecHttpBlSpam=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSpam"));

    $form[]=$tpl->field_checkbox("ModSecurityHTTPBL","{enable_feature}",$ModSecurityHTTPBL,"SecHttpBlKey,SecHttpBlSE,SecHttpBlSus,SecHttpBlHar,SecHttpBlSpam");
    $form[]=$tpl->field_text("SecHttpBlKey","{API_KEY}",$SecHttpBlKey);

    $form[]=$tpl->field_checkbox("SecHttpBlSE","{xapian_packages}",$SecHttpBlSE);
    $form[]=$tpl->field_checkbox("SecHttpBlSus","{Suspicious}",$SecHttpBlSus);
    $form[]=$tpl->field_checkbox("SecHttpBlHar","{harvester}",$SecHttpBlHar);
    $form[]=$tpl->field_checkbox("SecHttpBlSpam","{comment_spammer}",$SecHttpBlSpam);

    $html[]=$tpl->form_outside(null, $form,null,"{apply}",service_restart(),"AsWebMaster",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_decisionip_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DecisionIPCheck=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DecisionIPCheck"));
    $form[]=$tpl->field_checkbox("DecisionIPCheck","{enable_feature}",$DecisionIPCheck);
    $html[]=$tpl->form_outside(null, $form,"{DecisionIPExplain}","{apply}",service_restart(),"AsWebMaster",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_decisionip_save():bool{
    $DecisionIPCheck=intval($_POST["DecisionIPCheck"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DecisionIPCheck",$DecisionIPCheck);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return admin_tracks("Turn Participate in the DecisionIP network to $DecisionIPCheck");
}

function params_dos_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ModSecurityDoS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoS"));
    $ModSecurityDoSRqs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSRqs"));
    $ModSecurityDoSTT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSTT"));
    $ModSecurityDoSBK=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSBK"));

    $do_reput_block=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("do_reput_block"));
    $reput_block_duration=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reput_block_duration"));
    if($reput_block_duration==0){$reput_block_duration=300;}

    $form[]=$tpl->field_checkbox("ModSecurityDoS","{enable_feature}",$ModSecurityDoS,"ModSecurityDoSRqs,ModSecurityDoSTT,ModSecurityDoSBK");
    $form[]=$tpl->field_numeric("ModSecurityDoSRqs","{MX_REQUESTS}",$ModSecurityDoSRqs);
    $form[]=$tpl->field_numeric("ModSecurityDoSTT","{during} ({seconds})",$ModSecurityDoSTT);
    $form[]=$tpl->field_numeric("ModSecurityDoSBK","{deny} {during} ({seconds})",$ModSecurityDoSBK);

    $form[]=$tpl->field_section("{do_reput_block}");
    $form[]=$tpl->field_checkbox("do_reput_block","{enable_feature}",$do_reput_block,"reput_block_duration");
    $form[]=$tpl->field_numeric("reput_block_duration","{during} ({seconds})",$reput_block_duration);
    $html[]=$tpl->form_outside(null, $form,null,"{apply}",service_restart(),"AsWebMaster",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function params_main_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ModSecurityDisableOWASP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDisableOWASP"));
    $enforce_bodyproc_urlencoded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("enforce_bodyproc_urlencoded"));
    $crs_validate_utf8_encoding=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("crs_validate_utf8_encoding"));


    $ModSecurityAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
    $ModSecurityParanoiaLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityParanoiaLevel"));
    $ModSecurityMaxArgs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxArgs"));
    $ModSecurityMaxArgName=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxArgName"));
    $ModSecurityMaxArgValue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxArgValue"));
    $ModSecurityDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDebug"));




    if($ModSecurityAction==null){$ModSecurityAction="auditlog,pass";}
    if($ModSecurityParanoiaLevel==0){$ModSecurityParanoiaLevel=2;}


    $sModSecurityAction["auditlog,pass"]="{alert_and_pass}";
    $sModSecurityAction["auditlog,deny,status:405"]="{alert_and_block}";

    $sModSecurityParanoiaLevel[1]="{level} 1";
    $sModSecurityParanoiaLevel[2]="{level} 2";
    $sModSecurityParanoiaLevel[3]="{level} 3";
    $sModSecurityParanoiaLevel[4]="{level} 4";


    $ModSecurityDebugS[1]="{errors}";
    $ModSecurityDebugS[2]="{warning}";
    $ModSecurityDebugS[3]="{notices}";
    $ModSecurityDebugS[4]="{informational}";
    $ModSecurityDebugS[5]="{detailed}";
    $ModSecurityDebugS[6]="{everything}";
    $ModSecurityDebugS[9]="{debug}";
    $ModSecurityEnableOWASP=1;

    if($ModSecurityDisableOWASP==1){ $ModSecurityEnableOWASP=0;}
    $form[]=$tpl->field_checkbox("ModSecurityEnableOWASP","{enable} {signatures} (OWASP)",$ModSecurityEnableOWASP);
    $form[]=$tpl->field_array_hash($sModSecurityAction,"ModSecurityAction","nonull:{default_action}",$ModSecurityAction);
    $form[]=$tpl->field_array_hash($sModSecurityParanoiaLevel,"ModSecurityParanoiaLevel","nonull:{OPT_SPAM_RATE_LIMIT}",$ModSecurityParanoiaLevel);
    $form[]=$tpl->field_array_hash($ModSecurityDebugS,"ModSecurityDebug","nonull:{debug_mode}",$ModSecurityDebug);
    $form[]=$tpl->field_numeric("ModSecurityMaxArgs","{maximum_number_of_arguments}",$ModSecurityMaxArgs);
    $form[]=$tpl->field_numeric("ModSecurityMaxArgName","{maximum_number_of_argument_name}",$ModSecurityMaxArgName);
    $form[]=$tpl->field_numeric("ModSecurityMaxArgValue","{maximum_number_of_argument_value}",$ModSecurityMaxArgValue);

    $form[]=$tpl->field_checkbox("enforce_bodyproc_urlencoded","{enforce_bodyproc_urlencoded}",$enforce_bodyproc_urlencoded);
    $form[]=$tpl->field_checkbox("crs_validate_utf8_encoding","{crs_validate_utf8_encoding}",$crs_validate_utf8_encoding);



    $html[]=$tpl->form_outside(null, $form,null,"{apply}",service_restart(),"AsWebMaster",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function parameters(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $ModSecurityDisableOWASP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDisableOWASP"));
    $ModSecurityHTTPBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityHTTPBL"));
    $ModSecurityDoS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoS"));
    $ModSecurityDoSRqs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSRqs"));
    $ModSecurityDoSTT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSTT"));
    $ModSecurityDoSBK=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSBK"));
    $enforce_bodyproc_urlencoded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("enforce_bodyproc_urlencoded"));
    $crs_validate_utf8_encoding=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("crs_validate_utf8_encoding"));
    if($ModSecurityDoSRqs==0){$ModSecurityDoSRqs=100;}
    if($ModSecurityDoSTT==0){$ModSecurityDoSTT=60;}
    if($ModSecurityDoSBK==0){$ModSecurityDoSBK=600;}

    $DecisionIPCheck=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DecisionIPCheck"));


    $SecHttpBlKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlKey"));
    $SecHttpBlSE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSE"));
    $SecHttpBlSus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSus"));
    $SecHttpBlHar=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlHar"));
    $SecHttpBlSpam=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSpam"));

    $do_reput_block=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("do_reput_block"));
    $reput_block_duration=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reput_block_duration"));
    if($reput_block_duration==0){$reput_block_duration=300;}

	$ModSecurityAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
	$ModSecurityParanoiaLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityParanoiaLevel"));
    $ModSecurityMaxArgs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxArgs"));
    $ModSecurityMaxArgName=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxArgName"));
    $ModSecurityMaxArgValue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxArgValue"));
    $ModSecurityDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDebug"));




	if($ModSecurityAction==null){$ModSecurityAction="auditlog,pass";}
	if($ModSecurityParanoiaLevel==0){$ModSecurityParanoiaLevel=2;}


	$sModSecurityAction["auditlog,pass"]="{alert_and_pass}";
    $sModSecurityAction["auditlog,deny,status:405"]="{alert_and_block}";

    $sModSecurityParanoiaLevel[1]="{level} 1";
    $sModSecurityParanoiaLevel[2]="{level} 2";
    $sModSecurityParanoiaLevel[3]="{level} 3";
    $sModSecurityParanoiaLevel[4]="{level} 4";

    $ModSecurityDebugS[0]="{default}";
    $ModSecurityDebugS[1]="{errors}";
    $ModSecurityDebugS[2]="{warning}";
    $ModSecurityDebugS[3]="{notices}";
    $ModSecurityDebugS[4]="{informational}";
    $ModSecurityDebugS[5]="{detailed}";
    $ModSecurityDebugS[6]="{everything}";
    $ModSecurityDebugS[9]="{debug}";
    $ModSecurityEnableOWASP=1;

    if($ModSecurityDisableOWASP==1){ $ModSecurityEnableOWASP=0;}


    $ModSecurityRetentionDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityRetentionDays"));
    if($ModSecurityRetentionDays==0){$ModSecurityRetentionDays=7;}

    $ModSecurityMaxTempFolderSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxTempFolderSize"));
    if($ModSecurityMaxTempFolderSize==0){$ModSecurityMaxTempFolderSize=5;}

    $ModSecurityMaxModSecSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityMaxModSecSize"));
    if($ModSecurityMaxModSecSize==0){$ModSecurityMaxModSecSize=500;}

    $ModSecuritySecRotate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecuritySecRotate"));



    for($i=1;$i<366;$i++){
        $sday="{day}";
        if($i>1){$sday="{days}";}
        $st="$i $sday";
        if($i==7){$st="7 - 1 {week}";}
        if($i==14){$st="14 - 2 {weeks}";}
        if($i==30){$st="30 - 1 {month}";}
        if($i==60){$st="60 - 2 {months}";}
        if($i==90){$st="90 - 3 {months}";}
        if($i==365){$st="365 - 1 {year}";}
        $retention_days[$i]=$st;
    }
    for($i=1;$i<21;$i++){
        $tempsize[$i]="{$i}GB";
    }




    $tpl->table_form_field_js("Loadjs('$page?params-main-js=yes')");

    $tpl->table_form_field_bool("{enable} {signatures} (OWASP)",$ModSecurityEnableOWASP,ico_database);
    $tpl->table_form_field_text("{OPT_SPAM_RATE_LIMIT}",$sModSecurityParanoiaLevel[$ModSecurityParanoiaLevel],ico_params);
    $tpl->table_form_field_text("{debug}",$ModSecurityDebugS[$ModSecurityDebug],ico_bug);

    if($ModSecurityMaxArgs==0){
        $ModSecurityMaxArgs="{unlimited}";
    }
    if($ModSecurityMaxArgName==0){
        $ModSecurityMaxArgName="{unlimited}";
    }
    if ($ModSecurityMaxArgValue==0){
        $ModSecurityMaxArgValue="{unlimited}";
    }

    $tpl->table_form_field_text("{urls}","{maximum_number_of_arguments} $ModSecurityMaxArgs/$ModSecurityMaxArgName/$ModSecurityMaxArgValue",ico_max);
    $tpl->table_form_field_bool("{enable} {signatures} (OWASP)",$ModSecurityEnableOWASP,ico_database);
    $tpl->table_form_field_bool("{enforce_bodyproc_urlencoded}",$enforce_bodyproc_urlencoded);
    $tpl->table_form_field_bool("{crs_validate_utf8_encoding}",$crs_validate_utf8_encoding);




    $tpl->table_form_field_js("Loadjs('$page?params-decisionip-js=yes')");
    $tpl->table_form_field_bool("{DecisionIPCheck}",$DecisionIPCheck,ico_firewall);

    $tpl->table_form_field_js("Loadjs('$page?params-honeypot-js=yes')");
    if($ModSecurityHTTPBL==0){
        $tpl->table_form_field_text("Honey Pot HTTP Blacklist","{inactive2}",ico_shield_disabled);
    }else{
        if($SecHttpBlSE==1){
            $tt[]="{xapian_packages}";
        }
        if($SecHttpBlSus==1){
            $tt[]="{Suspicious}";
        }
        if($SecHttpBlHar==1){
            $tt[]="{harvester}";
        }
        if($SecHttpBlSpam==1){
            $tt[]="{comment_spammer}";
        }
        $tpl->table_form_field_text("Honey Pot HTTP Blacklist",@implode(", ",$tt),ico_shield);
    }
    $tpl->table_form_field_js("Loadjs('$page?params-dos-js=yes')");
    if($ModSecurityDoS==0){
        $tpl->table_form_field_text("{dos_protection}","{inactive2}",ico_shield_disabled);
    }else{
        $do_reput_block_text="";
        if($do_reput_block==1){
            $do_reput_block_text="{and} {do_reput_block} {during} $reput_block_duration {seconds}";
        }

        $tpl->table_form_field_text("{dos_protection}","{MX_REQUESTS} $ModSecurityDoSRqs {during} $ModSecurityDoSTT {seconds}, {deny} {during} $ModSecurityDoSBK {seconds}$do_reput_block_text",ico_shield);
    }

    $tpl->table_form_field_js("Loadjs('$page?params-statistics-js=yes')");



    $ModSecuritySaveReports=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecuritySaveReports"));
    if($ModSecuritySaveReports==null){
        $ModSecuritySaveReportsArr[0]=1;
        $ModSecuritySaveReportsArr[1]=1;
        $ModSecuritySaveReportsArr[2]=1;
        $ModSecuritySaveReportsArr[3]=1;
        $ModSecuritySaveReportsArr[4]=1;
        $ModSecuritySaveReportsArr[5]=0;
        $ModSecuritySaveReportsArr[6]=0;
    }else{
        $ModSecuritySaveReportsArr=unserialize($ModSecuritySaveReports);
    }

    $tpl->table_form_field_text("{statistics}","{retention_days} $retention_days[$ModSecurityRetentionDays], {maxsize} {$ModSecurityMaxTempFolderSize}GB",ico_statistics);

    $html[]=$tpl->table_form_compile();
	echo $tpl->_ENGINE_parse_body($html);
    $bts=array();
    $TINY_ARRAY["TITLE"]="{WAF_LONG} {parameters}";
    $TINY_ARRAY["ICO"]="fab fa-free-code-camp";
    $TINY_ARRAY["EXPL"]="{ModSecurityExplain}";
    $TINY_ARRAY["URL"] = "waf";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
    return true;
	
}

function service_restart():string{
    $page=CurrentPageName();
    return " dialogInstance1.close();LoadAjaxSilent('modsec-params','$page?parameters=yes');";
}

function Save():bool{
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ModSecuritySaveReports=array();

    if(isset($_POST["ModSecurityEnableOWASP"])) {
        if ($_POST["ModSecurityEnableOWASP"] == 1) {
            $_POST["ModSecurityDisableOWASP"] = 0;
        } else {
            $_POST["ModSecurityDisableOWASP"] = 1;
        }
        unset($_POST["ModSecurityEnableOWASP"]);
    }

    foreach ($_POST as $key=>$value){
        if(preg_match("#modsec_report_([0-9]+)#",$key,$re)){
            $ModSecuritySaveReports[$re[1]]=$value;
            unset($_POST[$key]);
        }

    }

    if(count($ModSecuritySaveReports)>1) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ModSecuritySaveReports",serialize($ModSecuritySaveReports));
    }

	$tpl->SAVE_POSTs();
    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/hupall");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }
   if(!$json->Status){
       echo $tpl->post_error("Framework return false!");
       return false;
   }



    return admin_tracks_post("Saving Reverse-Proxy Web application firewall main parameters");
}
function ServiceStatus():string{
    $sock=new sockets();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $data=$sock->REST_API_NGINX("/reverse-proxy/service/status");
    $json=json_decode($data);
    $page=CurrentPageName();

    $service_restart=$tpl->framework_buildjs("nginx:/reverse-proxy/restarthup",
        "nginx.restart.progress","nginx.restart.progress.txt",
        "progress-nginx-restart","LoadAjax('table-nginx','$page?table=yes');");

    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->widget_rouge("{error}","Framework return false!");
        return false;
    }
    $ini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($ini, "APP_NGINX",$service_restart);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    $html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td>
	<div class=\"ibox\" style='border-top:0px'>
    	<div class=\"ibox-content\" style='border-top:0px'>". ServiceStatus(). "</div>
    </div></td></tr>";
	
	$html[]="</table></td>";

	$curl=new ccurl("http://127.0.0.1:1842/nginx_status/",true,"127.0.0.1");
	$curl->NoLocalProxy();
	$curl->interface_force("127.0.0.1");
	$curl->NoHTTP_POST=true;
	$curl->get();

	$tbl=explode("\n",$curl->data);
	$ActiveConnections=0;
	$requests=0;
	foreach ($tbl as $index=>$ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
	    if(preg_match("#Active connections:\s+([0-9]+)#i",$ligne,$re)){
            $ActiveConnections=$re[1];
            continue;
        }
	    if(preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
            $requests=$re[3];

        }
    }

	

	
	$html[]="<td style='width:99%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:20px'>";
	$html[]="<div class=\"col-lg-3\">";
	
	$html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-ethernet fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {connections}</span>
	<h2 class=\"font-bold\">".FormatNumber($ActiveConnections)."</h2>
	</div>
	</div>
	</div>";
	
	
	$html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-tachometer fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {requests}</span>
	<h2 class=\"font-bold\">".FormatNumber($requests)."</h2>
	</div>
	</div>
	</div>";	
			
			
			
	$html[]="</div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	
	$html[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}