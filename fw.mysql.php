<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["MysqlBinAllAdresses"])){save();exit;}
if(isset($_POST["MySQLKeyBufferSize"])){save();exit;}

if(isset($_GET["mysql-params-generic"])){params_generic_js();exit;}
if(isset($_GET["mysql-params-generic-popup"])){params_generic_popup();exit;}

if(isset($_GET["mysql-params-tuning"])){params_tuning_js();exit;}
if(isset($_GET["mysql-params-tuning-popup"])){params_tuning_popup();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");

    $html=$tpl->page_header("{APP_MYSQL} v$APP_MYSQL_VERSION",
        ico_database,"{APP_MYSQL_ABOUT}","$page?tabs=yes","mysql-service",
        "progress-mysql-restart",false,"table-loader-mysql-service");

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_MYSQL} ",$html);
		echo $tpl->build_firewall();
		return true;
	}

	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{parameters}"]="$page?table=yes";
    $array["{databases}"]="fw.mysql.databases.php";
	$array["{privileges}"]="fw.mysql.privs.php";
	$array["{events}"]="fw.mysql.events.php?insidetab=yes";
	
	echo $tpl->tabs_default($array);

}

function params_generic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?mysql-params-generic-popup=yes");
}
function params_tuning_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?mysql-params-tuning-popup=yes");
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $IPClass=new IP();

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:450px;vertical-align:top'>";
    $html[]="<div id='mysql-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";

    $MysqlBinAllAdresses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MysqlBinAllAdresses"));
    $MySQLSkipNameResolve=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSkipNameResolve"));
    $MySQLSkipExternalLocking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSkipExternalLocking"));
    $MySQLSkipCharacterSetClientHandshake=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSkipCharacterSetClientHandshake"));

    $tpl->table_form_field_js("Loadjs('$page?mysql-params-generic=yes')");
    $tpl->table_form_field_bool("{bind_all_addresses}",$MysqlBinAllAdresses,ico_nic);
    $tpl->table_form_field_bool("{skip-name-resolve}",$MySQLSkipNameResolve,ico_timeout);
    $tpl->table_form_field_bool("{skip-external-locking}",$MySQLSkipExternalLocking,ico_timeout);
    $tpl->table_form_field_bool("{skip-character-set-client-handshake}",$MySQLSkipCharacterSetClientHandshake,ico_timeout);

    $MySQLKeyBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLKeyBufferSize"));
    $MySQLMyisamSortBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMyisamSortBufferSize"));
    $MySQLSortBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSortBufferSize"));
    $MySQLQueryCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLQueryCacheSize"));
    $MySQLJoinBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLJoinBufferSize"));
    $MySQLReadBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLReadBufferSize"));
    $MySQLReadRndBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLReadRndBufferSize"));
    $MySQLMaxAllowedPackets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMaxAllowedPackets"));
    $MySQLMaxConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMaxConnections"));
    $MySQLNetBufferLength=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLNetBufferLength"));
    $MySQLThreadCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLThreadCacheSize"));
    $MySQLWaitTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLWaitTimeOut"));
    $MySQLOpenFilesLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLOpenFilesLimit"));

    $tpl->table_form_field_js("Loadjs('$page?mysql-params-tuning=yes')");
    if($MySQLMaxConnections==0){$MySQLMaxConnections=150;}
    $tpl->table_form_field_text("{max_connections}","$MySQLMaxConnections",ico_network_chart);

    if ($MySQLKeyBufferSize>0){
        $tpl->table_form_field_text("{key_buffer_size}","$MySQLKeyBufferSize MB",ico_timeout);
    }

    if($MySQLQueryCacheSize>0){
        $tpl->table_form_field_text("{query_cache_size}","$MySQLQueryCacheSize MB",ico_timeout);
    }
    if($MySQLSortBufferSize>0){
        $tpl->table_form_field_text("{sort_buffer_size}","$MySQLSortBufferSize MB",ico_timeout);
    }
    if($MySQLJoinBufferSize>0){
        $tpl->table_form_field_text("{join_buffer_size}","$MySQLJoinBufferSize MB",ico_timeout);
    }
    if($MySQLJoinBufferSize>0){
        $tpl->table_form_field_text("{read_buffer_size}","$MySQLReadBufferSize MB",ico_timeout);
    }
    if($MySQLReadRndBufferSize>0){
        $tpl->table_form_field_text("{read_rnd_buffer_size}","$MySQLReadRndBufferSize MB",ico_timeout);
    }
    if($MySQLMyisamSortBufferSize>0){
        $tpl->table_form_field_text("{myisam_sort_buffer_size}","$MySQLMyisamSortBufferSize MB",ico_timeout);
    }
    if($MySQLNetBufferLength>0){
        $tpl->table_form_field_text("{net_buffer_length}","$MySQLNetBufferLength",ico_timeout);
    }
    if($MySQLMaxAllowedPackets>0){
        $tpl->table_form_field_text("{max_allowed_packet}","$MySQLMaxAllowedPackets MB",ico_timeout);
    }

    if($MySQLThreadCacheSize>0){
        $tpl->table_form_field_text("{thread_cache_size}","$MySQLThreadCacheSize",ico_timeout);
    }
    if($MySQLWaitTimeOut>0){
        $tpl->table_form_field_text("{wait_timeout}","$MySQLWaitTimeOut {seconds}",ico_timeout);
    }
    if($MySQLOpenFilesLimit>0){
        $tpl->table_form_field_text("{open_files_limit}","$MySQLOpenFilesLimit {files}",ico_timeout);
    }
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjaxTiny('mysql-status','$page?status=yes');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function params_tuning_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $security = "AsDatabaseAdministrator";

    $MySQLKeyBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLKeyBufferSize"));
    $MySQLMyisamSortBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMyisamSortBufferSize"));
    $MySQLSortBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSortBufferSize"));
    $MySQLQueryCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLQueryCacheSize"));
    $MySQLJoinBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLJoinBufferSize"));
    $MySQLReadBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLReadBufferSize"));
    $MySQLReadRndBufferSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLReadRndBufferSize"));
    $MySQLMaxAllowedPackets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMaxAllowedPackets"));
    $MySQLMaxConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMaxConnections"));
    $MySQLNetBufferLength=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLNetBufferLength"));
    $MySQLThreadCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLThreadCacheSize"));
    $MySQLWaitTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLWaitTimeOut"));
    $MySQLOpenFilesLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLOpenFilesLimit"));
    $MySQLMaxConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMaxConnections"));
    if($MySQLMaxConnections==0){$MySQLMaxConnections=151;}
    $form[]=$tpl->field_numeric("MySQLMaxConnections","{max_connections}",$MySQLMaxConnections,"{max_connections}");
    $form[]=$tpl->field_numeric("MySQLKeyBufferSize","{key_buffer_size} (MB)",$MySQLKeyBufferSize,"{key_buffer_size_text}");
    $form[]=$tpl->field_numeric("MySQLQueryCacheSize","{query_cache_size} (MB)",$MySQLQueryCacheSize,"{query_cache_size_text}");
    $form[]=$tpl->field_numeric("MySQLSortBufferSize","{sort_buffer_size} (MB)",$MySQLSortBufferSize,"{sort_buffer_size_text}");
    $form[]=$tpl->field_numeric("MySQLJoinBufferSize","{join_buffer_size} (MB)",$MySQLJoinBufferSize,"{join_buffer_size_text}");
    $form[]=$tpl->field_numeric("MySQLReadBufferSize","{read_buffer_size} (MB)",$MySQLReadBufferSize,"{read_buffer_size_text}");
    $form[]=$tpl->field_numeric("MySQLReadRndBufferSize","{read_rnd_buffer_size} (MB)",$MySQLReadRndBufferSize,"{read_rnd_buffer_size_text}");
    $form[]=$tpl->field_numeric("MySQLMyisamSortBufferSize","{myisam_sort_buffer_size} (MB)",$MySQLMyisamSortBufferSize,"{myisam_sort_buffer_size_text}");
    $form[]=$tpl->field_numeric("MySQLNetBufferLength","{net_buffer_length}",$MySQLNetBufferLength,"{net_buffer_length_text}");
    $form[]=$tpl->field_numeric("MySQLMaxAllowedPackets","{max_allowed_packet} (MB)",$MySQLMaxAllowedPackets,"{max_allowed_packet}");

    $form[]=$tpl->field_numeric("MySQLThreadCacheSize","{thread_cache_size}",$MySQLThreadCacheSize,"{thread_cache_size_text}");
    $form[]=$tpl->field_numeric("MySQLWaitTimeOut","{wait_timeout} ({seconds})",$MySQLWaitTimeOut,"{wait_timeout_text}");
    $form[]=$tpl->field_numeric("MySQLOpenFilesLimit","{open_files_limit} ({files})",$MySQLOpenFilesLimit,"{open_files_limit_explain}");

    $jsrestart="dialogInstance1.close();".js_restart();
    $html[]=$tpl->form_outside(null, $form,"","{apply}",$jsrestart,$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function params_generic_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsDatabaseAdministrator";
    $MysqlBinAllAdresses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MysqlBinAllAdresses"));
    $MySQLSkipNameResolve=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSkipNameResolve"));
    $MySQLSkipExternalLocking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSkipExternalLocking"));
    $MySQLSkipCharacterSetClientHandshake=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSkipCharacterSetClientHandshake"));
    $MySQLMaxConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLMaxConnections"));
    if($MySQLMaxConnections==0){$MySQLMaxConnections=151;}

    $jsrestart="dialogInstance1.close();".js_restart();
    $form[]=$tpl->field_numeric("MySQLMaxConnections","{max_connections}",$MySQLMaxConnections,"{max_connections}");
    $form[]=$tpl->field_checkbox("MysqlBinAllAdresses","{bind_all_addresses}",$MysqlBinAllAdresses);
    $form[]=$tpl->field_checkbox("MySQLSkipNameResolve","{skip-name-resolve}",$MySQLSkipNameResolve,false,"{skip-name-resolve_text}");
    $form[]=$tpl->field_checkbox("MySQLSkipExternalLocking","{skip-external-locking}",$MySQLSkipExternalLocking,false,"{skip-external-locking_text}");
    $form[]=$tpl->field_checkbox("MySQLSkipCharacterSetClientHandshake","{skip-character-set-client-handshake}",$MySQLSkipCharacterSetClientHandshake,false,"{skip-character-set-client-handshake_text}");
    $html[]=$tpl->form_outside(null, $form,"","{apply}",$jsrestart,$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function save():bool{
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
    return admin_tracks_post("Change MySQL parameters");
}

function status(){
	$users=new usersMenus();
	$tpl=new template_admin();
	
	$page=CurrentPageName();
	$datas=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("mysql.php?status=yes");
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/mysql.status");

    $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_MYSQL} v$APP_MYSQL_VERSION";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{APP_MYSQL_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    echo $tpl->SERVICE_STATUS($ini, "APP_MYSQL_ARTICA",js_restart())."<script>$jstiny</script>";
}


function js_restart():string{
	$page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("API:mysql/service/restart",
        "mysql.restart.progress","mysql.restart.progress.log","progress-mysql-restart",
    "LoadAjaxTiny('mysql-status','$page?status=yes');");

}

function Charsets(){
	
$f[]="big5";
$f[]="latin2";
$f[]="dec8";
$f[]="cp850";
$f[]="latin1";
$f[]="hp8";
$f[]="koi8r";
$f[]="swe7";
$f[]="ascii";
$f[]="ujis";
$f[]="sjis";
$f[]="cp1251";
$f[]="hebrew";
$f[]="tis620";
$f[]="euckr";
$f[]="latin7";
$f[]="koi8u";
$f[]="gb2312";
$f[]="greek";
$f[]="cp1250";
$f[]="gbk";
$f[]="cp1257";
$f[]="latin5";
$f[]="armscii8";
$f[]="utf8";
$f[]="ucs2";
$f[]="cp866";
$f[]="keybcs2";
$f[]="macce";
$f[]="macroman";
$f[]="cp852";
$f[]="cp1256";
$f[]="geostd8";
$f[]="binary";
$f[]="cp932";
$f[]="eucjpms";
	foreach ($f as $data){

		$newar[trim($data)]=strtoupper(trim($data));
	}
	ksort($newar);
	$newar[null]="--";
	return $newar;
}