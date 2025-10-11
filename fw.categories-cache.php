<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["CategoriesCacheMaxMemory"])){Save();exit;}
if(isset($_GET["categories-cache-status"])){status();exit;}
if(isset($_POST["remove_database"])){remove_database();exit;}
if(isset($_POST["import_database"])){import_database();exit;}
if(isset($_GET["remove-database-ask"])){remove_database_ask();exit;}
if(isset($_GET["import-database-ask"])){import_database_ask();exit;}
if(isset($_GET["categories-cache-line"])){front_status();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_CATEGORIES_CACHE}","fad fa-memory","{APP_CATEGORIES_CACHE_TEXT}","$page?table=yes","categories-cache","progress-catcache-restart");

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}


function front_status(){
        $page=CurrentPageName();
        $tpl=new template_admin();
        $users=new usersMenus();
        $IPClass=new IP();
        $sock=new sockets();
        $ldap=new clladp();





        $security="AsSystemAdministrator";

        $RedisBindInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindInterface");
        if($RedisBindInterface==null){$RedisBindInterface="lo";}

        if(!class_exists("Redis")) {
            echo $tpl->div_error("{REDIS_PHP_EXTENSION_NOT_LOADED}");
            return false;
        }
        $redis = new Redis();
        try{
            $redis->connect('/var/run/categories-cache/categories-cache.sock');
            $data=$redis->info();
            } catch (Exception $e) {
            echo $tpl->div_error($e->getMessage());
            return false;
        }


        $uptime_in_seconds=$data["uptime_in_seconds"];
        $timeStart=time()-$uptime_in_seconds;
        $w_used_memory_human=$tpl->widget_h("green","fa-database",$data["used_memory_human"],"{memory_database}");
        $total_connections_received=$tpl->widget_h("green","far fa-ethernet",$tpl->FormatNumber($data["total_connections_received"]),"{connections}");
        $tb=explode(",",$data["db0"]);
        foreach ($tb as $ll){
            $ss=explode("=",$ll);
            $XKEYS[trim($ss[0])]=trim($ss[1]);
        }
    $entries=intval($XKEYS["keys"]);


    if($entries==0) {
        $total_keys = $tpl->widget_h("grey", "fa fas fa-database", "{none}", "{records}");
    }else{
        $total_keys = $tpl->widget_h("green", "fa fas fa-database", $tpl->FormatNumber($entries), "{records}");
    }
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;vertical-align:top'>$w_used_memory_human</td>";
    $html[]="<td style='width:33%;vertical-align:top;padding-left:10px'>$total_connections_received</td>";
    $html[]="<td style='width:33%;vertical-align:top;padding-left:10px'>$total_keys</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function table(){


	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$page=CurrentPageName();

    $CategoriesCacheMaxMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheMaxMemory"));
    $CategoriesCacheMaxDBSize = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheMaxDBSize"));
    $CategoriesCacheListenNet=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheListenNet"));
    $CategoriesCacheListenAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheListenAddr"));
    $CategoriesCacheListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheListenPort"));
    if($CategoriesCacheListenPort==0){$CategoriesCacheListenPort=2214;}

    if($CategoriesCacheMaxMemory==0){$CategoriesCacheMaxMemory=500;}
    if($CategoriesCacheMaxDBSize==0){$CategoriesCacheMaxDBSize=400;}
    $priv           = "AsSystemAdministrator";

    $jsRestart      = restart_js();

    $form[]=$tpl->field_checkbox("CategoriesCacheListenNet","{ListenAddress}",$CategoriesCacheListenNet,"CategoriesCacheListenAddr,CategoriesCacheListenPort");
    $form[]=$tpl->field_interfaces("CategoriesCacheListenAddr", "nooloopNoDef:{interface}", $CategoriesCacheListenAddr);
    $form[]=$tpl->field_numeric("CategoriesCacheListenPort","{listen_port}",$CategoriesCacheListenPort);

    $form[]         = $tpl->field_section("{limits} - {performance}");
    $form[]         = $tpl->field_numeric("CategoriesCacheMaxMemory","{memory_limit} (MB)", $CategoriesCacheMaxMemory);
    $form[]         = $tpl->field_numeric("CategoriesCacheMaxDBSize","{max_size_db} (MB)", $CategoriesCacheMaxDBSize,"{CategoriesCacheMaxDBSize}");
    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsRestart,$priv,false);


//restart_service_each
	$html="<table style='width:100%'>
	<td style='vertical-align:top;width:240px'><div id='categories-cache-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%;padding-left:15px'>
	    <div id='categories-cache-line' style='margin-bottom: 10px'></div>
	    $myform
	</td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('categories-cache-status','$page?categories-cache-status=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("ksrn.php?restart-categories-cache=yes","categories-cache.progress","categories-cache.log","progress-catcache-restart","LoadAjaxSilent('categories-cache-status','$page?categories-cache-status=yes')");

}

function remove_database_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("ksrn.php?remove-categories-cache=yes","categories-cache.progress","categories-cache.log","progress-catcache-restart","LoadAjaxSilent('categories-cache-status','$page?categories-cache-status=yes')");

    $tpl->js_confirm_delete("{REMOVE_DATABASE}<hr>{rebuild_database_warn}","remove_database",$tpl->javascript_parse_text("{database}"),$jsrestart);
}
function import_database_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("ksrn.php?import-categories-cache=yes","ufdbcattoredis.progress","ufdbcattoredis.log","progress-catcache-restart","LoadAjaxSilent('categories-cache-status','$page?categories-cache-status=yes')");

    $tpl->js_confirm_execute("{import_categories_ask}","import_database",$tpl->javascript_parse_text("{database}"),$jsrestart);
}

function remove_database(){
    admin_tracks("Remove Categories Cache database");
}
function import_database(){
    admin_tracks("Import categories into Categories Cache database");
}
function status(){
    $tpl            = new template_admin();
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?status-categories-cache=yes");
    $bsini = new Bs_IniHandler(PROGRESS_DIR."/categories-cache.status");

    $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
    $refresh="LoadAjaxSilent('categories-cache-status','$page?categories-cache-status=yes');";
    $status=$tpl->SERVICE_STATUS($bsini, "APP_CATEGORIES_CACHE",restart_js(),$APP_REDIS_SERVER_VERSION,$refresh);
    $removedb=$tpl->button_autnonome("{REMOVE_DATABASE}","Loadjs('$page?remove-database-ask=yes')","fas fa-trash","AsSystemAdministrator",335,"btn-danger");


    $import=$tpl->button_autnonome("{launch_importation}","Loadjs('$page?import-database-ask=yes')","fas fa-file-import","AsSystemAdministrator",335,"btn-warning");



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td>$status</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td>$removedb</td>";
    $html[]="</tr>";
    $html[]="<tr><td colspan='2'>&nbsp;</td></tr>";
    $html[]="<tr>";
    $html[]="<td>$import</td>";
    $html[]="</tr>";

    $html[]="</table><script>LoadAjaxSilent('categories-cache-line','$page?categories-cache-line=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);

    return true;

}

function Save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$tpl->SAVE_POSTs();
    admin_tracks("Saving Categories Cache parameters and restarting service");
    return true;
}
