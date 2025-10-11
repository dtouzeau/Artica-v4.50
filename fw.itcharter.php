<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["main-status"])){main_status();exit;}
if(isset($_GET["itcharter-status"])){itcharter_status();exit;}
if(isset($_GET["itcharter-config-js"])){itcharter_config_js();exit;}
if(isset($_GET["itcharter-db-js"])){itcharter_db_js();exit;}
if(isset($_GET["itcharter-db-popup"])){itcharter_db_popup();exit;}
if(isset($_POST["ITChartListenInterface"])){itcharter_config_save();exit;}

if(isset($_GET["itcharter-config-popup"])){itcharter_config();exit;}
if(isset($_GET["itcharter-config-static"])){itcharter_config_static();exit;}
if(isset($_POST["ITChartVerbose"])){itcharter_config_save();exit;}
if(isset($_GET["redis-status"])){redis_status();exit;}
if(isset($_GET["main-install"])){main_install();exit;}


page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{it_charters}",
        "fa fa-file-signature","{IT_charter_explain}","$page?tabs=yes","it-charters","progress-itcharter-restart",false,"table-loader-itchart-pages");

	if(isset($_GET["main-page"])){$tpl=new template_admin("{it_charters}",$html);
        echo $tpl->build_firewall();return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $EnableITChart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableITChart"));
    if($EnableITChart==0){
        $array["{install_the_feature}"]="$page?main-install=yes";
        echo $tpl->tabs_default($array);
        return true;
    }





	$array["{status}"]="$page?main-status=yes";
	$array["{it_charters}"]="fw.itcharter.table.php";
    $array["{sessions}"]="fw.itcharter.sessions.php";
	$array["{events}"]="fw.itcharter.events.php";

	
	echo $tpl->tabs_default($array);
	
	
}
function main_install(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $config["SECURITY"]="AsSquidAdministrator";
    $config["TOKENS_REQUIRE_INSTALLED"]["APP_REDIS_SERVER_INSTALLED"]="{APP_REDIS}";
    $config["SQUIDEnable"]["APP_REDIS_SERVER_INSTALLED"]="{proxy_service}";
    $config["TITLE"]="{it_charters}";
    $config["EXPLAIN"]="{IT_charter_explain}";
    $config["TOKEN"]="EnableITChart";
    $config["LICENSE"]=true;
    $config["PROGRESS_FILE"]=PROGRESS_DIR."/ichart.progress";
    $config["LOG_FILE"]=PROGRESS_DIR."/ichart.install.log";
    $config["CMD_ON"]="/itcharter/install";
    $config["CMD_OFF"]="/itcharter/uninstall";
    $config["AFTER"]="LoadAjax('table-loader-itchart-pages','$page?tabs=yes');";
    $html[]=$tpl->widget_install("{IT_charter_explain}",true,$config);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function itcharter_config_static(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!class_exists("Redis")) {return false;}
    $ClusterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterEnabled"));
    $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterMaster"));
    $redis=new Redis();

    $redis_server='127.0.0.1';
    $redis_port=6123;
    if($ClusterEnabled==1){
        if(strpos($ClusterMaster,":")>0){
            $ff=explode(":",$ClusterMaster);
            $redis_server=$ff[0];
            $redis_port=$ff[1];
        }else{$redis_server=$ClusterMaster;}
    }


    try {
        $redis->connect($redis_server,$redis_port);
    } catch (Exception $e) {
        echo $tpl->FATAL_ERROR_SHOW_128($e->getMessage());
        die();
    }
    $redis->close();

    $ITChartVerbose=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartVerbose"));
    $ITChartDatabaseSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartDatabaseSize"));
    $IChartRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IChartRecursive"));
    $ITChartListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartListenInterface"));
    if($ITChartListenInterface==null){$ITChartListenInterface="lo";}
    if($ITChartDatabaseSize==0){$ITChartDatabaseSize=50;}

    $NetworksExclude=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartNetworkExclude")));
    $ITChartAllowSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartAllowSSL"));
    $Redirect=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartRedirectURL"));
    if(count($NetworksExclude)==0){$NetworksExclude=array("127.0.0.0/8");}
    if($Redirect==null){
        $proto="http";
        $WebErrorPageListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
        if($WebErrorPageListenPort ==0){
            $WebErrorPageListenPort=9025;
        }
        $UfdbUseInternalServiceEnableSSL =  intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
	    $UfdbUseInternalServiceHTTPSPort =  intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
        if ($UfdbUseInternalServiceHTTPSPort == 0 ){        $UfdbUseInternalServiceHTTPSPort = 9026;}
	    if($UfdbUseInternalServiceEnableSSL==1){
            $WebErrorPageListenPort=$UfdbUseInternalServiceHTTPSPort;
            $proto="https";
        }
        $Redirect="$proto://{$_SERVER["SERVER_NAME"]}:$WebErrorPageListenPort";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ITChartRedirectURL",$Redirect);
    }


    $tpl->table_form_field_js("Loadjs('$page?itcharter-config-js=yes')","AsDansGuardianAdministrator");
    $tpl->table_form_field_bool("{verbose}",$ITChartVerbose,ico_bug);
    $tpl->table_form_field_bool("{catch_ssl_connections}",$ITChartAllowSSL,ico_ssl);
    $tpl->table_form_field_text("{redirect_queries_to}",$Redirect,ico_retweet);

    $c=0;
    foreach ($NetworksExclude as $line){
        if($line==null){continue;}
        $c++;
        $tpl->table_form_field_text("{exclude}",$line,ico_networks);
    }
    if($c==0){
        $tpl->table_form_field_text("{exclude}","{none}",ico_networks);
    }
    $tpl->table_form_field_bool("{AD_LDAP_RECURSIVE}",$IChartRecursive,ico_max);

    $tpl->table_form_section("{database}");
    $tpl->table_form_field_js("Loadjs('$page?itcharter-db-js=yes')","AsDansGuardianAdministrator");
    $tpl->table_form_field_text("{squidguard_database_size}",$ITChartDatabaseSize." MB",ico_database);
    $tpl->table_form_field_text("{listen}","$ITChartListenInterface:6123",ico_interface);

    $ClusterEnabled=intval($redis->get("ITChartClusterEnabled"));
    $ClusterMaster=trim($redis->get("ITChartClusterMaster"));
    $AsCluster=false;
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    if($PowerDNSEnableClusterMaster==1){
        $AsCluster=true;
        $tpl->table_form_field_js("","AsDansGuardianAdministrator");
        $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
        $tpl->table_form_field_text("{cluster}","$ClusterMaster:6123",ico_server);
    }else{
        if($ClusterEnabled==1){
            $AsCluster=true;
            $tpl->table_form_field_text("{cluster}","$ClusterMaster:6123",ico_server);
        }
    }
    if(!$AsCluster){
        $tpl->table_form_field_js("","AsDansGuardianAdministrator");
        $tpl->table_form_field_bool("{cluster}",0,ico_server);
    }



    echo $tpl->table_form_compile();

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ichart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/itchart.log";
    $ARRAY["CMD"]="/itcharter/reconfigure";
    $ARRAY["TITLE"]="{compile2}";
    $ARRAY["AFTER"]="LoadAjax('itcharters-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-itcharter-restart')";

    $uninstall=$tpl->framework_buildjs("/itcharter/uninstall","ichart.progress",
        "ichart.install.log","progress-itcharter-restart",
        "LoadAjax('table-loader-itchart-pages','$page?tabs=yes');",
        null,"{APP_ITCHARTER}: {disable_feature}","AsSquidAdministrator");
    $topbuttons[] = array($uninstall, ico_trash, "{disable_feature}");
    $topbuttons[] = array($jsRestart, ico_refresh, "{reconfigure}");

    $TINY_ARRAY["TITLE"]="{it_charters}";
    $TINY_ARRAY["ICO"]="fa fa-file-signature";
    $TINY_ARRAY["EXPL"]="{IT_charter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$headsjs</script>";

    return true;
}
function itcharter_config_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{settings}","$page?itcharter-config-popup=yes");
}
function itcharter_db_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{database}","$page?itcharter-db-popup=yes");
}
function itcharter_db_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ITChartListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartListenInterface"));
    if($ITChartListenInterface==null){$ITChartListenInterface="lo";}
    $form[]=$tpl->field_interfaces_choose("ITChartListenInterface","{listen_interface}",$ITChartListenInterface);
    $ITChartDatabaseSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartDatabaseSize"));
    if($ITChartDatabaseSize==0){$ITChartDatabaseSize=50;}

    $form[]=$tpl->field_numeric("ITChartDatabaseSize","{squidguard_database_size} (MB)",$ITChartDatabaseSize);
    $restart=$tpl->framework_buildjs("/itcharter/uninstall","ichart.progress","ichart.install.log","itchart-progress-install",
        "dialogInstance2.close();LoadAjax('itcharter-config','$page?itcharter-config-static=yes');");

    $html[]="<div id='itchart-progress-install' style='margin-left: 10px;'></div>";
    $html[]=$tpl->form_outside(null,$form,"" ,"{apply}",
        $restart,"AsSquidAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function itcharter_config():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ITChartVerbose=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartVerbose"));


    $IChartRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IChartRecursive"));
    $NetworksExclude=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartNetworkExclude")));
    $ITChartAllowSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartAllowSSL"));
    $Redirect=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartRedirectURL"));
    if(count($NetworksExclude)==0){$NetworksExclude=array("127.0.0.0/8");}
    if($Redirect==null){$Redirect="http://itchart.mycompany.tld";}

    $form[]=$tpl->field_checkbox("ITChartVerbose","{verbose}",$ITChartVerbose);
    $form[]=$tpl->field_checkbox("ITChartAllowSSL","{catch_ssl_connections}",$ITChartAllowSSL);

    $form[]=$tpl->field_textareacode("ITChartNetworkExclude","{exclude}",@implode("\n",$NetworksExclude));
    $form[]=$tpl->field_text("ITChartRedirectURL","{redirect_queries_to} (http)",$Redirect);
    $form[]=$tpl->field_checkbox("IChartRecursive","{AD_LDAP_RECURSIVE}",$IChartRecursive);



    $restart=$tpl->framework_buildjs("/itcharter/install","ichart.progress","ichart.install.log","itchart-progress-install",
        "dialogInstance2.close();LoadAjax('itcharter-config','$page?itcharter-config-static=yes');");

    $html[]="<div id='itchart-progress-install' style='margin-left: 10px;'></div>";
    $html[]=$tpl->form_outside(null,$form,"{IT_charter_explain2}" ,"{apply}",
        $restart,"AsSquidAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function itcharter_config_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $redis_server='127.0.0.1';
    $redis_port=6123;

    if(isset($_POST["ITChartRedirectURL"])) {
        $ITChartRedirectURL = $_POST["ITChartRedirectURL"];
        $urls = parse_url($ITChartRedirectURL);
        if (!isset($urls["scheme"])) {
            $urls["scheme"] = "http";
        }
        if (!isset($urls["host"])) {
            $urls["host"] = "itchart.mycompany.tld";
        }
        $zuri[] = $urls["scheme"] . "://";
        $zuri[] = $urls["host"];
        if (isset($urls["port"])) {
            $zuri[] = ":{$urls["port"]}";
        }
        $_POST["ITChartRedirectURL"] = @implode("", $zuri);
        $_POST["ITChartRedirectURLArray"] = serialize($urls);
    }



    $redis=new Redis();
    try {
        $redis->connect($redis_server,$redis_port);
    } catch (Exception $e) {
        echo "jserror:".$tpl->javascript_parse_text($e->getMessage());
        die();
    }

    foreach ($_POST as $key=>$val){
        $key=str_replace("_",".",$key);

        if(strpos($key,".")>0){
            if(!$redis->set($key,$val)){
                echo "jserror:$key:$val ".$redis->getLastError();
                die();
            }
            continue;
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$val);
    }
    return true;
}
function itcharter_status():bool{
        $tpl=new template_admin();
        $page=CurrentPageName();
        if(!class_exists("Redis")) {
            echo $tpl->widget_rouge("{REDIS_PHP_EXTENSION_NOT_LOADED}", "{error}");
            return false;
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/status"));
        $bsini=new Bs_IniHandler($json->Info);
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ichart.restart.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ichart.restart.log";
        $ARRAY["CMD"]="/itcharter/restart";
        $ARRAY["TITLE"]="{restarting_service}";
        $prgress=base64_encode(serialize($ARRAY));
        $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-itcharter-restart')";
        $final[]=$tpl->SERVICE_STATUS($bsini, "APP_ITCHARTER",$jsRestart);
        echo $tpl->_ENGINE_parse_body($final);
        echo "<script>LoadAjax('redis-status','$page?redis-status=yes');</script>";
        return true;
}
function redis_status(){
    $redis=new Redis();
    $tpl=new template_admin();
    try {
        $redis->connect('127.0.0.1','6123');
    } catch (Exception $e) {
        echo $tpl->widget_rouge($e->getMessage(),"{error}");
        die();
    }

    $MAIN=$redis->info();
    $perc=$MAIN["used_memory_human"];
    echo $tpl->widget_vert("{memory_use}","{$perc}");

}
function main_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div style='margin-top:20px'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>
            <div id='itcharter-status' style='padding-left:10px'></div>
            <div id='redis-status' style='padding-left:10px'></div>
    </td>";
    $html[]="<td style='vertical-align:top;width:90%'><div id='itcharter-config'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("itcharter-status",$page,"itcharter-status=yes");
    $html[]="LoadAjax('itcharter-config','$page?itcharter-config-static=yes');";
    $html[]="</script>";
    echo @implode("\n",$html);
}
function SquidTemplateSimple(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
	
}

