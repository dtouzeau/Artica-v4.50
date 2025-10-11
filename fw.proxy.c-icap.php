<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["clamav-widget"])){clamav_widget();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["explainthis"])){explainthis();exit;}
if(isset($_POST["MinSpareThreads"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["main-status"])){main_status();exit;}
if(isset($_GET["main-status-start"])){main_status_start();exit;}
if(isset($_GET["sandbox"])){sandbox_page();exit;}
if(isset($_GET["ext-sandbox-js"])){sandbox_kaspersky_extensions();exit;}
if(isset($_GET["sandbox-kaspersky-extensions"])){sandbox_kaspersky_extensions_list();exit;}
if(isset($_GET["sandbox-kaspersky-exten"])){sandbox_kaspersky_exten();exit;}
if(isset($_POST["EnableKasperskySandbox"])){sandbox_kaspersky_save();exit;}
if(isset($_GET["upload-sandbbox-js"])){sandbox_upload_js();exit;}
if(isset($_GET["upload-sandbbox-popup"])){sandbox_upload_popup();exit;}
if(isset($_GET["file-uploaded"])){sandbox_uploaded_js();exit;}
if(isset($_GET["error-page"])){error_page();exit;}
if(isset($_GET["error-page-view"])){error_page_view();exit;}
if(isset($_POST["CICAPWebErrorPage"])){error_page_save();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $CicapVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");

    $html=$tpl->page_header("{SERVICE_WEBAVEX} v$CicapVersion",
    "fas fa-tachometer-alt","{ACTIVATE_ICAP_AV_TEXT}","$page?tabs=yes","antivirus-proxy",
    "progress-c-icap-restart",false,"table-loader-c-icap");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{http_antivirus_for_proxy}");
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}
function main_status_start(){
    $page=CurrentPageName();
    echo "<div id='cicap-main-status-start'></div>
    <script>LoadAjax('cicap-main-status-start','$page?main-status=yes')</script>
    ";
}
function sandbox_kaspersky_extensions() {
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("Kaspersky SandBox {extensions}","$page?sandbox-kaspersky-extensions=yes");
}
function sandbox_upload_js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("SandBox {upload}","$page?upload-sandbbox-popup=yes");
}
function sandbox_upload_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $html[]="";
    $html[]="<p>{upload_sb_ask}</p>";
    $html[]="<div class='center' style='margin: 30px'>".$tpl->button_upload("{upload_a_file}",$page)."</div>";
    $html[]="<div id='import-results' style='margin: 30px'></div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function sandbox_uploaded_js(){
    $filename   = $_GET["file-uploaded"];
    $fileencode = urlencode($filename);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?sandbox-file=$fileencode");
    admin_tracks("$filename was upload to SandBox detection");
    header("content-type: application/x-javascript");
    echo "dialogInstance4.close();";
}
function error_page_view(){
    $CICAPWebErrorPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPWebErrorPage"));
    if(strlen($CICAPWebErrorPage)<20){
        $CICAPWebErrorPage=@file_get_contents(dirname(__FILE__)."/VIRUS.html");

    }
echo $CICAPWebErrorPage;
}
function error_page_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CICAPWebErrorPage",$_POST["CICAPWebErrorPage"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/template");
    admin_tracks("Saving the Web error page content for the ICAP Antivirus service");
}

function error_page(){
    $tpl        = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $t=time();

    $CICAPWebErrorPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPWebErrorPage"));
    if(strlen($CICAPWebErrorPage)<20){
        $CICAPWebErrorPage=@file_get_contents(dirname(__FILE__)."/VIRUS.html");

    }

    $html[]="<div id='mytinme$t'></div>";
	$form[]=$tpl->field_textareacode("CICAPWebErrorPage",null, $CICAPWebErrorPage);
	echo $tpl->form_outside("{WEB_ERROR_PAGE}", $form,null,"{apply}","AsSquidAdministrator",false);
    $html[]="<script>";

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"s_PopUpFull('$page?error-page-view=yes','1024','900');\"><i class='fa fa-file-dashed-line'></i> {view_html} </label>
			</div>"
    );

    $TINY_ARRAY["TITLE"]="{SERVICE_WEBAVEX} {WEB_ERROR_PAGE}";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{ACTIVATE_ICAP_AV_TEXT}";
    $TINY_ARRAY["URL"]="antivirus-proxy";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}


function sandbox_kaspersky_extensions_list(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $TRCLASS    = null;
    $security   ="AsSquidAdministrator";
    $CountOfKasperskySandboxMime    = 0;
    $text_class = null;

    include_once(dirname(__FILE__)."/ressources/class.mimes-types.inc");
    $mimes=mimestypes_array();

    $fields[]="{extensions}";
    $fields[]="{BannedMimetype}";
    $fields[]="{enabled}";

    $html[]=$tpl->table_head($fields,"table-$t");
    $KasperskySandboxMime=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));

    if(is_array($KasperskySandboxMime)) {
        $CountOfKasperskySandboxMime=count($KasperskySandboxMime);
    }
    if ($CountOfKasperskySandboxMime == 0) {
        $KasperskySandboxMime = mimesandboxdefaults();
    }

    foreach ($mimes as $ext=>$mime){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $token=md5("$ext$mime");
        $enabled=0;
        if(isset($KasperskySandboxMime[$token])){$enabled=1;}

        $enabled=$tpl->icon_check($enabled,"Loadjs('$page?sandbox-kaspersky-exten=$token')",null,$security);

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" width='1%' nowrap>$ext</td>";
        $html[]="<td class=\"$text_class\">$mime</td>";
        $html[]="<td class=\"$text_class\" width='1%'>$enabled</td>";
        $html[]="</tr>";
    }

    $html[]=$tpl->table_footer("table-$t",count($fields),true);
    echo $tpl->_ENGINE_parse_body($html);

}

function sandbox_kaspersky_exten(){
    $ptoken=$_GET["sandbox-kaspersky-exten"];
    include_once(dirname(__FILE__)."/ressources/class.mimes-types.inc");
    $KasperskySandboxMime   = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));
    $mimes                  = mimestypes_array();
    foreach ($mimes as $ext=>$mime){
        $token=md5("$ext$mime");
        $MAINS[$token]=$mime;
    }
    if(isset($KasperskySandboxMime[$ptoken])){
        unset($KasperskySandboxMime[$ptoken]);
        admin_tracks("Removed scanning $mime in Kaspersky SandBox");

    }else{
        admin_tracks("Added scanning $mime in Kaspersky SandBox");
        $KasperskySandboxMime[$ptoken]=$MAINS[$ptoken];
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KasperskySandboxMime",serialize($KasperskySandboxMime));

}


function sandbox_page(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $security="AsSquidAdministrator";
    $CountOfKasperskySandboxMime    = 0;

    //$tpl->CLUSTER_CLI=true;

    $jsCompile = "blur();";
    $EnableKasperskySandbox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKasperskySandbox"));
    $KasperskySandboxAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxAddr"));
    $KasperskySandboxMime=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));

    if(is_array($KasperskySandboxMime)) {
        $CountOfKasperskySandboxMime = count($KasperskySandboxMime);
    }
    if($CountOfKasperskySandboxMime==0){
        $KasperskySandboxMime=mimesandboxdefaults();
        $CountOfKasperskySandboxMime = count($KasperskySandboxMime);
    }

    $form[]=$tpl->field_section("Kaspersky Sandbox");
    $form[]=$tpl->field_checkbox("EnableKasperskySandbox","{enable_feature}",$EnableKasperskySandbox,false);
    $form[]=$tpl->field_text("KasperskySandboxAddr","{sandbox_server_address}",$KasperskySandboxAddr);
    $form[]=$tpl->field_info("extension_list", " {extension_list}",

        array("VALUE"=>null,
            "BUTTON"=>true,
            "BUTTON_CAPTION"=>"$CountOfKasperskySandboxMime {extensions}",
            "BUTTON_JS"=>"Loadjs('$page?ext-sandbox-js=yes')"

        ),null);

    $html[]=$tpl->form_add_button("{upload}","Loadjs('$page?upload-sandbbox-js=yes')");
    $html[]=$tpl->form_outside("SandBox: {parameters}", @implode("\n", $form),null,"{apply}",$jsCompile,$security);
    echo $tpl->_ENGINE_parse_body($html);
}
function sandbox_kaspersky_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function main_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();

    $json=json_decode($sock->REST_API("/cicap/info"));
    $USED_SERVERS=$json->Info->UsedServers;
    $FREE_SERVERS=$json->Info->free_servers;
    $BYTES_IN=intval($json->Info->bytes_in);
    $BYTES_OUT=intval($json->Info->bytes_out);
    $REQUESTS=$json->Info->requests;
    $REQUESTS=$tpl->FormatNumber($REQUESTS);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID=1");
    $CICAP_IN_DB=intval($ligne["enabled"]);

    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:15%;padding-left:15px;vertical-align: top'>";
    $html[]="<div id='c-icap-status2'></div>";
    $html[]=$tpl->button_autnonome("{refresh}", "LoadAjax('cicap-main-status-start','$page?main-status=yes')", "fas fa-sync-alt",null,335);
    $html[]="</td>";
    $html[]="<td style='width:95%;padding-left:15px;vertical-align: top'>";


    $REQUEST_STATUS=$tpl->widget_h("green","fas fa-tachometer-alt-average",$REQUESTS,"{requests}");
    $USED_SERVERS_STATUS=$tpl->widget_h("grey","fas fa-microchip",0,"{processes}");
    $BYTES_IN_STATUS=$tpl->widget_h("grey","fas fa-chart-line",0,"{bandwidth}");

    if($USED_SERVERS>0){
        $USED_SERVERS_STATUS=$tpl->widget_h("lazur","fas fa-microchip","{$USED_SERVERS}/{$FREE_SERVERS}","{processes}");
    }
    if($BYTES_IN>0){
        $BYTES_IN_STATUS=$tpl->widget_h("green","fas fa-chart-line","{$BYTES_IN}Kbs","{bandwidth}");
    }

    if($CICAP_IN_DB==0){
        $BYTES_IN_STATUS=$tpl->widget_h("grey","fas fa-chart-line","{disabled}","{bandwidth}");
        $REQUEST_STATUS=$tpl->widget_h("grey","fas fa-tachometer-alt-average","{disabled}","{requests}");
        $USED_SERVERS_STATUS=$tpl->widget_h("grey","fas fa-microchip","{disabled}","{processes}");
        $SANDBOX_STATUS=$tpl->widget_h("grey","far fa-times-circle","{disabled}","{sandbox_connector}");
    }

    $html[]="<div id='cicapclam-status'></div>";
    $html[]="<div id='sandbox-status'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;vertical-align: top'>";
    $html[]=$REQUEST_STATUS;
    $html[]="</td>";
    $html[]="<td style='width:33%;vertical-align: top;padding-left:10px'>";
    $html[]=$USED_SERVERS_STATUS;
    $html[]="</td>";
    $html[]="<td style='width:33%;vertical-align: top;padding-left:10px'>";
    $html[]=$BYTES_IN_STATUS;
    $html[]="</td>";
    $html[]="</tr>";
    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
    if($EnableClamavInCiCap==1){
        $html[]="<tr>";
        $html[]="<td style='width:33%;vertical-align: top'>";
        $qpos=new postgres_sql();
        $threatsNum=$qpos->COUNT_ROWS_LOW("webfilter");
        if($threatsNum>0){
            $THREATS=$tpl->widget_h("yellow","fa-solid fa-virus-slash","{$threatsNum}","{threats}",);

        }else{
            $THREATS=$tpl->widget_h("grey","fa-solid fa-virus-slash","{$threatsNum}","{threats}",);
        }
        $ClamAVSignatures=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVSignatures"));
        $NumberOfSignatures=intval($ClamAVSignatures["{NumberOfSignatures}"]);
        if($NumberOfSignatures>0){
            $NumberOfSignatures=$tpl->FormatNumber($NumberOfSignatures);
            $SIG=$tpl->widget_h("green","fa-solid fa-flask-vial","{$NumberOfSignatures}","{signatures}");

        }else{
            $SIG=$tpl->widget_h("grey","fa-solid fa-flask-vial","0","{signatures}");
        }
        $CLAMVER= $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
        if($CLAMVER<>null){

            $CLAM_VER=$tpl->widget_h("green","fab fa-medrt","{$CLAMVER}","{version}",);

        }else{
            $CLAM_VER=$tpl->widget_h("grey","fab fa-medrt","??","{version}",);
        }

        $html[]="<tr>";
        $html[]="<td style='width:33%;vertical-align: top'>";
        $html[]=$THREATS;
        $html[]="</td>";
        $html[]="<td style='width:33%;vertical-align: top;padding-left:10px'>";
        $html[]=$SIG;
        $html[]="</td>";
        $html[]="<td style='width:33%;vertical-align: top;padding-left:10px'>";
        $html[]=$CLAM_VER;
        $html[]="</td>";
        $html[]="</tr>";
    }



    $html[]="</table>";

    $jsrestart=$tpl->framework_buildjs("/cicap/restart",
        "c-icap.restart.progress","c-icap.restart.log","progress-c-icap-restart",
        "LoadAjax('cicap-main-status-start','$page?main-status=yes')");

    $jsreconfigure=$jsrestart;



    $C_ICAP_MEMCACHED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_MEMCACHED"));

    if($C_ICAP_MEMCACHED==0){
        $html[]="<div style='margin:30px'>";
        $html[]=$tpl->div_error("{C_ICAP_MEMCACHED_EXPLAIN}");
        $html[]="</div>";
    }

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    ;

    $html[]="<script>";

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('cicap-main-status-start','$page?main-status=yes')\"><i class='fas fa-sync-alt'></i> {refresh} </label>
			<label class=\"btn btn btn-warning\" OnClick=\"$jsrestart\">
				<i class='fas fa-sync-alt'></i> {restart} </label>
			<label class=\"btn btn btn-primary\" OnClick=\"$jsreconfigure\">
				<i class='fa fa-save'></i> {reconfigure} </label>				
			</div>"
    );


    $CicapVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");


    $TINY_ARRAY["TITLE"]="{SERVICE_WEBAVEX} v$CicapVersion";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{ACTIVATE_ICAP_AV_TEXT}";
    $TINY_ARRAY["URL"]="antivirus-proxy";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="LoadAjaxSilent('cicapclam-status','$page?clamav-widget=yes')";
    //$html[]="LoadAjaxSilent('sandbox-status','$page?sandbox-status=yes')";
    $html[]="LoadAjax('c-icap-status2','$page?status=yes')";
    $html[]="$jstiny";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function clamav_widget(){
    $tpl=new template_admin();
    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
    $page=CurrentPageName();
    $leftbarr="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";
    $jsinstallClamav=$tpl->framework_buildjs(
        "cicap.php?install-clamav=yes",
        "cicap.install.progress",
        "cicap.install.log",
        "progress-c-icap-restart",
        "LoadAjax('cicap-main-status-start','$page?main-status=yes');$leftbarr;"
    );

    $jsuninstallClamav=$tpl->framework_buildjs(
        "cicap.php?uninstall-clamav=yes",
        "cicap.install.progress",
        "cicap.install.log",
        "progress-c-icap-restart",
        "LoadAjax('cicap-main-status-start','$page?main-status=yes');$leftbarr"
    );

    $jsreconfigure=$tpl->framework_buildjs(
        "/cicap/restart",
        "c-icap.restart.progress",
        "c-icap.restart.progress.txt",
        "progress-c-icap-restart",
        "LoadAjax('cicap-main-status-start','$page?main-status=yes')"
    );


    if($EnableClamavInCiCap==0) {
        $button["ico"]=ico_cd;
        $button["name"] = "{install}";
        $button["js"] = $jsinstallClamav;
        $STATUS = $tpl->widget_h("grey", "fas fa-virus", "{not_installed}", "{APP_CLAMAV}",$button);
        echo $tpl->_ENGINE_parse_body($STATUS);
        return true;
    }
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1) {
        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne = $q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID=1");
        $CICAP_IN_DB = intval($ligne["enabled"]);

        if ($CICAP_IN_DB == 0) {
            $button["ico"] = "fa fa-link";
            $button["name"] = "{ACTIVATE_ICAP_AV}";
            $button["js"] = "Loadjs('fw.proxy.icap.center.php?enabled=1');";
            $STATUS = $tpl->widget_h("yellow", "fas fa-virus", "{installed}/{disconnected}", "{APP_CLAMAV}", $button);
            echo $tpl->_ENGINE_parse_body($STATUS);
            return true;
        }
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/srvclamav"));


    if(!$json->Status){
        $button["ico"]="fas fa-sync-alt";
        $button["name"] = "{reconfigure}";
        $button["js"] = $jsreconfigure;

        $button2["ico"]=ico_cd;
        $button2["name"] = "{uninstall}";
        $button2["js"] = $jsuninstallClamav;

        $STATUS = $tpl->widget_h("red", "fas fa-virus", "{failed}", $json->Error,$button,$button2);
        echo $tpl->_ENGINE_parse_body($STATUS);
        return true;
    }

    $button["ico"]=ico_cd;
    $button["name"] = "{uninstall}";
    $button["js"] = $jsuninstallClamav;

    $button2["ico"] = "fa fa-unlink";
    $button2["name"] = "{connector} [OFF]";
    $button2["js"] = "Loadjs('fw.proxy.icap.center.php?enabled=1');";

    $STATUS = $tpl->widget_h("green", "fas fa-virus", "{installed}/{connected}", "{APP_CLAMAV}",$button,$button2);
    echo $tpl->_ENGINE_parse_body($STATUS);
    return true;
}

function SANDBOX_STATUS(){
    return false;

    $C_ICAP_RECORD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_RECORD"));
    $C_ICAP_RECORD_ENABLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPEnableSandBox"));

    $jsuninstallSandbox=$tpl->framework_buildjs(
        "cicap.php?uninstall-sandbox=yes",
        "cicap.install.progress",
        "cicap.install.log",
        "LoadAjax('cicap-main-status-start','$page?main-status=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');"
    );

    $jsinstallSandbox=$tpl->framework_buildjs(
        "cicap.php?install-sandbox=yes",
        "cicap.install.progress",
        "cicap.install.log",
        "LoadAjax('cicap-main-status-start','$page?main-status=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');"
    );

    if($C_ICAP_RECORD==0){
        //fas fa-box-check
        $SANDBOX_STATUS=$tpl->widget_h("grey","far fa-times-circle","{not_installed}","{sandbox_connector}");
    }else{
        if($C_ICAP_RECORD_ENABLED==0){
            $SANDBOX_STATUS=$tpl->widget_h("grey","far fa-box","{disabled}","{sandbox_connector}");
        }else{
            $SANDBOX_STATUS=$tpl->widget_h("green","fas fa-box-check","{running}","{sandbox_connector}");
        }
    }

    $bt_sandbox=$tpl->button_autnonome("SandBox [X]", "blur()",
        "far fa-box","AsSquidAdministrator",0,"btn-default");

    if($C_ICAP_RECORD==1){
        $bt_sandbox=$tpl->button_autnonome("SandBox [OFF]", $jsinstallSandbox,
            "far fa-box","AsSquidAdministrator",0,"btn-default");
        if($C_ICAP_RECORD_ENABLED==1){
            $bt_sandbox=$tpl->button_autnonome("SandBox [ON]", $jsuninstallSandbox,
                "fas fa-box-check","AsSquidAdministrator",0,"btn-primary");
        }
    }

}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?main-status-start=yes";
    $array["{parameters}"]="$page?table=yes";
    $array["{WEB_ERROR_PAGE}"]="$page?error-page=yes";
    echo $tpl->tabs_default($array);
}

function table(){
    $tpl=new template_admin();
    $ci=new cicap();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;

    $MaxCICAPWorkTimeMin=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MaxCICAPWorkTimeMin");
    $MaxCICAPWorkSize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MaxCICAPWorkSize");
    if(!is_numeric($MaxCICAPWorkTimeMin)){$MaxCICAPWorkTimeMin=1440;}
    if(!is_numeric($MaxCICAPWorkSize)){$MaxCICAPWorkSize=5000;}

    $CICAPListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface"));
    $CicapNotifyViruses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapNotifyViruses"));
    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    if($CICAPListenInterface==null){$CICAPListenInterface="lo";}

    $ClamavTemporaryDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavTemporaryDirectory");
    if($ClamavTemporaryDirectory==null){$ClamavTemporaryDirectory="/home/clamav";}

    $ClamaVDetectPUA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamaVDetectPUA"));
    $PhishingScanURLs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhishingScanURLs"));



    $tcp=new networking();
    $ips=$tcp->ALL_IPS_GET_ARRAY();
    $ips[null]="{all}";
    $ips["127.0.0.1"]="127.0.0.1";

    $notifyVirHTTPServer=false;
    if($ci->main_array["CONF"]["ViralatorMode"]==1){
        if(preg_match('#https://(.*?)/exec#',$ci->main_array["CONF"]["VirHTTPServer"],$re)){
            if(trim($re[1])==null){$notifyVirHTTPServer=true;}
            if(trim($re[1])=="127.0.0.1"){$notifyVirHTTPServer=true;}
            if(trim($re[1])=="localhost"){$notifyVirHTTPServer=true;}
        }}

    if($notifyVirHTTPServer==true){
        $color="color:#d32d2d;font-weight:bolder";
    }


    for($i=1;$i<13;$i++){
        $f[$i]=$i;
    }


    $jsCompile=$tpl->framework_buildjs("/cicap/restart",
        "c-icap.restart.progress","c-icap.restart.log","progress-c-icap-restart",
        "LoadAjax('c-icap-status','$page?status=yes');");



    $security="AsSquidAdministrator";

    $form[]=$tpl->field_interfaces("CICAPListenInterface","nodef:{listen_interface}",$CICAPListenInterface);
    $form[]=$tpl->field_numeric("Timeout","{Timeout} ({seconds})",$ci->main_array["CONF"]["Timeout"],"{Timeout_text}");
    $form[]=$tpl->field_numeric("MaxKeepAliveRequests","{MaxKeepAliveRequests}",$ci->main_array["CONF"]["MaxKeepAliveRequests"],"{MaxKeepAliveRequests_text}");
    $form[]=$tpl->field_numeric("KeepAliveTimeout","{KeepAliveTimeout} ({seconds})",$ci->main_array["CONF"]["KeepAliveTimeout"],"{KeepAliveTimeout_text}");
    $form[]=$tpl->field_numeric("MaxServers","{MaxServers}",$ci->main_array["CONF"]["MaxServers"],"{MaxServers_text}");
    $form[]=$tpl->field_numeric("MinSpareThreads","{MinSpareThreads}",$ci->main_array["CONF"]["MinSpareThreads"],"{MinSpareThreads_text}");
    $form[]=$tpl->field_numeric("MaxSpareThreads","{MaxSpareThreads}",$ci->main_array["CONF"]["MaxSpareThreads"],"{MaxSpareThreads_text}");
    $form[]=$tpl->field_numeric("ThreadsPerChild","{ThreadsPerChild}",$ci->main_array["CONF"]["ThreadsPerChild"],"{ThreadsPerChild_text}");
    $form[]=$tpl->field_numeric("MaxRequestsPerChild","{MaxRequestsPerChild}",$ci->main_array["CONF"]["MaxRequestsPerChild"],"{MaxRequestsPerChild_text}");
    $form[]=$tpl->field_array_hash($f, "DebugLevel", "{debug_mode}", $ci->main_array["CONF"]["DebugLevel"],false,"{log level_text}");

    $form[]=$tpl->field_numeric("MaxCICAPWorkTimeMin","{max_time_in_tmp} ({minutes})",$MaxCICAPWorkTimeMin,false,"{max_time_in_tmp_explain}");
    $form[]=$tpl->field_numeric("MaxCICAPWorkSize","{max_tempdir_size} (MB)",$MaxCICAPWorkSize,false,"{max_tempdir_size_explain}");
    $form[]=$tpl->field_checkbox("CicapNotifyViruses","{be_alerted_on_viruses}",$CicapNotifyViruses,false,"{be_alerted_on_viruses_text}");
    $form[]=$tpl->field_checkbox("IcapForwardSSL","{transfert_ssl_data}",$IcapForwardSSL,false,"");
    $form[]=$tpl->field_checkbox("ViralatorMode","{ViralatorMode}",$ci->main_array["CONF"]["ViralatorMode"],false,"{ViralatorMode_text}");
    $form[]=$tpl->field_browse_directory("VirSaveDir", "{VirSaveDir}", $ci->main_array["CONF"]["VirSaveDir"],'{VirSaveDir_text}');
    $form[]=$tpl->field_text("VirHTTPServer", "{VirHTTPServer}", $ci->main_array["CONF"]["VirHTTPServer"],true,"{VirHTTPServer_text}");


    $form[]=$tpl->field_section("{http_antivirus_for_proxy}");
    $form[]=$tpl->field_checkbox("ClamaVDetectPUA","{ClamaVDetectPUA}",$ClamaVDetectPUA,false,"{ClamaVDetectPUA_explain}");
    $form[]=$tpl->field_checkbox("PhishingScanURLs","{srv_clamav.PhishingScanURLs}",$PhishingScanURLs,false,"{srv_clamav.PhishingScanURLs_text}");


    $form[]=$tpl->field_numeric("srv_clamav.SendPercentData","{srv_clamav.SendPercentData} (%)",
        $ci->main_array["CONF"]["srv_clamav.SendPercentData"],"{srv_clamav.SendPercentData}_text}");


    $form[]=$tpl->field_numeric("srv_clamav.StartSendPercentDataAfter","{srv_clamav.StartSendPercentDataAfter} (MB)",
        $ci->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"],"{srv_clamav.StartSendPercentDataAfter_text}");

    $form[]=$tpl->field_numeric("srv_clamav.MaxObjectSize","{srv_clamav.MaxObjectSize} (MB)",
        $ci->main_array["CONF"]["srv_clamav.MaxObjectSize"],"{srv_clamav.MaxObjectSize_text}");

    $form[]=$tpl->field_numeric("srv_clamav.ClamAvMaxFilesInArchive","{srv_clamav.ClamAvMaxFilesInArchive} ({files})",
        $ci->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"],"{srv_clamav.ClamAvMaxFilesInArchive}");

    $form[]=$tpl->field_numeric("srv_clamav.ClamAvMaxFileSizeInArchive","{srv_clamav.ClamAvMaxFileSizeInArchive} (MB)",
        $ci->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"],"{srv_clamav.ClamAvMaxFileSizeInArchive}");

    $form[]=$tpl->field_numeric("srv_clamav.ClamAvMaxRecLevel","{srv_clamav.ClamAvMaxRecLevel} (MB)",
        $ci->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"],"{srv_clamav.ClamAvMaxRecLevel}");



    $form[]=$tpl->field_browse_directory("ClamavTemporaryDirectory", "{temp_dir}", $ClamavTemporaryDirectory,'{temp_dir}');


    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:15%;padding-left:15px;vertical-align: top'>";
    $html[]="<div id='c-icap-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:85%;vertical-align: top'>";
    $html[]=$tpl->form_outside("{icap_service} ({listen_port} 1345)", @implode("\n", $form),"{daemon_settings_text}","{apply}",$jsCompile,$security);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{SERVICE_WEBAVEX} {parameters}";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{ACTIVATE_ICAP_AV_TEXT}";
    $TINY_ARRAY["URL"]="antivirus-proxy";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>LoadAjax('c-icap-status','$page?status=yes')";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function Save(){
    $reconfigure_squid=false;
    $sock=new sockets();
    //ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


    if(isset($_POST["ClamaVDetectPUA"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClamaVDetectPUA", $_POST["ClamaVDetectPUA"]);
        unset($_POST["ClamaVDetectPUA"]);
    }
    if(isset($_POST["PhishingScanURLs"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PhishingScanURLs", $_POST["PhishingScanURLs"]);
        unset($_POST["PhishingScanURLs"]);
    }



    if(isset($_POST["EnableClamavInCiCap"])){
        $ci=new cicap();
        if($ci->EnableClamavInCiCap<>$_POST["EnableClamavInCiCap"]){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavInCiCap",$_POST["EnableClamavInCiCap"]);
            $reconfigure_squid=true;
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
        }
    }
    if(isset($_POST["EnableSquidGuardInCiCAP"])){
        if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidGuardInCiCAP")<>$_POST["EnableSquidGuardInCiCAP"]){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidGuardInCiCAP",$_POST["EnableSquidGuardInCiCAP"]);
            $reconfigure_squid=true;

        }
    }

    if(isset($_POST["IcapForwardSSL"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IcapForwardSSL", $_POST["IcapForwardSSL"]);
        unset($_POST["IcapForwardSSL"]);
    }

    if(isset($_POST["CicapNotifyViruses"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapNotifyViruses", $_POST["CicapNotifyViruses"]);
        unset($_POST["CicapNotifyViruses"]);
    }


    if(isset($_POST["CICAPListenInterface"])){
        if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface")<>$_POST["CICAPListenInterface"]){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CICAPListenInterface",$_POST["CICAPListenInterface"]);
            writelogs("CICAPListenInterface -> `{$_POST["CICAPListenInterface"]}`",__FUNCTION__,__FILE__,__LINE__);
            $reconfigure_squid=true;
        }

    }

    if($reconfigure_squid){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");

    }


    $ci=new cicap();

    foreach ($_POST as $num=>$line){
        $line=url_decode_special_tool($line);
        if(preg_match('#^srv_clamav_(.+)#',$num,$re)){
            $num="srv_clamav.{$re[1]}";
        }

        writelogs("Save $num => $line",__FUNCTION__,__FILE__,__LINE__);
        $ci->main_array["CONF"][$num]=$line;
    }

    admin_tracks("Local ICAP service settings changes");
    $ci->Save();


    NotifyServers();
}
function NotifyServers(){

}

function status(){
    $tpl=new template_admin();

    $page=CurrentPageName();
    VERBOSE("REST_API -> /cicap/status",__LINE__);
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($data->Info);

    $jsrestart=$tpl->framework_buildjs("/cicap/restart",
        "c-icap.restart.progress","c-icap.restart.log","progress-c-icap-restart",
    "LoadAjax('c-icap-status','$page?status=yes');");




    $f[]=$tpl->SERVICE_STATUS($ini, "APP_C_ICAP",$jsrestart);


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/status"));
    $ini=new Bs_IniHandler();


    $clamd_restart=$tpl->framework_buildjs("/clamd/restart",
        "clamd.restart","clamd.restart.logs","progress-c-icap-restart");



    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
        return false;

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            return false;
        } else {
            $ini->loadString($json->Info);
            $f[]=$tpl->SERVICE_STATUS($ini, "APP_CLAMAV",$clamd_restart);
        }
    }

    $f[]="<script>";
    $f[]="function CronosClamavStatus(){";
    $f[]="\tif(!document.getElementById('c-icap-status2')){return;}";
    $f[]="\tLoadAjaxSilent('c-icap-status2','$page?status=yes')";
    $f[]="}";
    $f[]="\t setTimeout(\"CronosClamavStatus()\",5000);";
    $f[]="</script>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}