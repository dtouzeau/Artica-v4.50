<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["move-item-js"])){move_item_js();}
if(isset($_GET["new-cache-js"])){new_cache_js();exit;}
if(isset($_GET["new-cache-folder-js"])){new_cache_folder_js();exit;}
if(isset($_GET["new-cache-step1"])){new_cache_step1();exit;}
if(isset($_GET["new-cache-folder-step1"])){new_cache_folder_step1();exit;}
if(isset($_GET["new-cache-step2"])){new_cache_step2();exit;}
if(isset($_GET["new-cache-step3"])){new_cache_step3();exit;}
if(isset($_GET["new-cache-step4"])){new_cache_step4();exit;}
if(isset($_GET["delete-empty-js"])){delete_empty_js();exit;}
if(isset($_GET["delete-item-js"])){delete_js();exit;}
if(isset($_POST["empty-item"])){exit;}
if(isset($_POST["delete-item"])){delete();exit;}
if(isset($_GET["cache-edit-js"])){cache_edit_js();exit;}
if(isset($_GET["cache-edit"])){cache_edit();exit;}
if(isset($_POST["cache_directory"])){cache_save();exit;}
if(isset($_POST["new-cache-disk"])){new_cache_folder_save();exit;}
if(isset($_GET["enabled-js"])){enable_js();exit;}
if(isset($_GET["table"])){table_start();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $title="{APP_NGINX} {sites_caching}";

    $html=$tpl->page_header($title,ico_hd,"{nginx_caching_explain}","$page?table=yes",
        "reverse-caches-center",
        "progress-nginx-cache-center",false,
        "table-nginx-cache-center"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_NGINX} {nginx_caches_center_text}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}





function enable_js(){
    $ID=intval($_GET["enabled-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,cachename FROM caches_center WHERE ID='$ID'");
    $title=$ligne["cachename"];
    $enabled=$ligne["enabled"];
    if($enabled==1){
        $q->QUERY_SQL("UPDATE caches_center SET enabled=0 WHERE ID='$ID'");
        admin_tracks("Disable reverse-Proxy cache $ID $title");
        return true;
    }
    $q->QUERY_SQL("UPDATE caches_center SET enabled=1 WHERE ID='$ID'");
    admin_tracks("Enable reverse-Proxy cache $ID $title");
    return true;
}

function cache_edit_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=$_GET["cache-edit-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$ligne=$q->mysqli_fetch_array("SELECT cachename FROM caches_center WHERE ID='$ID'");
	$title=$ligne["cachename"];
	$tpl->js_dialog($title, "$page?cache-edit=$ID");	
}

function cache_edit(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["cache-edit"]);


    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM caches_center WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error_html();}

        // cachename,cache_dir,cache_type,cache_size

	$cachename=$ligne["cachename"];
	$cache_directory=$ligne["cache_dir"];
	$cache_size=$ligne["cache_size"];
	$cache_type=$ligne["cache_type"];
	$enabled=$ligne["enabled"];
	$bt="{apply}";

    $form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,true);
	$form[]=$tpl->field_text("cachename","{name}",$cachename,true);
    $form[]=$tpl->field_info("cache_directory", "{directory}", $cache_directory);
	$form[]=$tpl->field_numeric("squid-cache-size","{cache_size} (MB)",$cache_size,"{cache_size_text}");

	echo $tpl->form_outside("$cachename ($cache_type)", @implode("\n", $form),null,$bt,"LoadAjax('table-loader-cachecenteer-service','$page?table=yes');BootstrapDialog1.close();","AsSquidAdministrator");
}

function new_cache_folder_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$cachename=basename($_POST["new-cache-disk"]);
	$cache_directory=$_POST["new-cache-disk"];
	$size=$_POST["cache_size"];
	$enabled=1;
	$RemoveSize=0;
    $cache_type="disk";

	
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	
$sql="INSERT INTO caches_center
			(cachename,cache_dir,cache_type,cache_size,enabled,percentcache,usedcache,zOrder,RemoveSize)
			VALUES('$cachename','$cache_directory','$cache_type','$size',$enabled,0,0,1,$RemoveSize)";
	
	$q->QUERY_SQL("$sql");
	
	if(!$q->ok){echo $q->mysql_error." LINE ".__LINE__." $sql";}

	
}

function cache_save(){
	
	$_POST=mysql_escape_line_query($_POST);
	$cache_directory=$_POST["cache_directory"];
	$cache_type=$_POST["cache_type"];
	$size=$_POST["squid-cache-size"];
	$cachename=$_POST["cachename"];
	$enabled=$_POST["enabled"];
	$ID=$_POST["ID"];
	if($cache_type=="rock"){$CPU=0;}
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	

	

	

	
	if($ID==0){
	    $sql="INSERT INTO caches_center
				(cachename,cache_dir,cache_type,cache_size,enabled,percentcache,usedcache,zOrder)
				VALUES('$cachename','$cache_directory','$cache_type','$size',$enabled,0,0,1)";
		$q->QUERY_SQL($sql);
		echo $sql;

	}else{
		$sql="UPDATE caches_center SET
				cachename='$cachename',
				cache_size='$size',
				enabled=$enabled
				WHERE ID=$ID";
		$q->QUERY_SQL($sql,"artica_backup");
	
	}
	
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
	
	
}

function delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$ligne=$q->mysqli_fetch_array("SELECT cachename FROM caches_center WHERE ID='$ID'");
	$title=$ligne["cachename"];
	$action_remove_cache_ask="<strong>$title</strong><br>".$tpl->javascript_parse_text("{action_remove_cache_ask}");
	$tpl->js_confirm_delete($action_remove_cache_ask,"delete-item", $ID,"LoadAjax('table-loader-cachecenteer-service','$page?table=yes');");
}

function move_item_js(){
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$ID=$_GET["ID"];
	$dir=$_GET["dir"];
	$ligne=$q->mysqli_fetch_array("SELECT zOrder,cpu FROM caches_center WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}

	$cpu=$ligne["cpu"];
	$CurrentOrder=$ligne["zOrder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE caches_center SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder' AND `cpu`='$cpu'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$q->mysql_error."');";}


	$sql="UPDATE caches_center SET zOrder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$q->mysql_error."');";}

	$results=$q->QUERY_SQL("SELECT ID FROM caches_center WHERE `cpu`='$cpu' ORDER by zOrder");
	if(!$q->ok){echo "alert('".$q->mysql_error;}
	$c=1;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$sql="UPDATE caches_center SET zOrder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$q->mysql_error."');";}
		$c++;
	}
}
function delete():bool{
	$ID=$_POST["delete-item"];
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT cachename FROM caches_center WHERE ID='$ID'");
    $cachename=$ligne["cachename"];
    $sql="UPDATE caches_center SET `remove`=1 WHERE ID=$ID";

	$q->QUERY_SQL($sql);
	if(!$q->ok){
        echo $q->mysql_error."\n$sql";
        return false;
    }

    admin_tracks("Remove reverse-porxy cache $cachename");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?synchronize-caches");
    return true;
}

function delete_empty_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$ligne=$q->mysqli_fetch_array("SELECT cachename FROM caches_center WHERE ID='$ID'");
	$title=$ligne["cachename"];
	$action_empty_cache_ask=$tpl->javascript_parse_text("{action_empty_cache_ask}");
	$action_empty_cache_ask=str_replace("%s", $title, $action_empty_cache_ask);
	
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.txt";
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.progress";
	
	
	$ARRAY["CMD"]="squid.php?cache-center-empty=$ID";
	$ARRAY["TITLE"]="{empty} $title";
	$ARRAY["AFTER"]="LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-cachecenter-restart')";
	$tpl->js_confirm_empty($action_empty_cache_ask, "empty-item", $ID,$jsrestart);
	
	
}


function new_cache_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{new_cache} {disk}", "$page?new-cache-step1=yes");
}

function new_cache_folder_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{new_cache} {folder}", "$page?new-cache-folder-step1=yes");	
}

function new_cache_folder_step1(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$jsafter="BootstrapDialog1.close();LoadAjax('nginx-caches-center','$page?table=yes');";
	$form[]=$tpl->field_browse_directory("new-cache-disk", "{folder}", null,null,true);
	$form[]=$tpl->field_numeric("cache_size","{cache_size} (MB)",1024);
	$form[]=$tpl->field_hidden("CPU", 1);
    $form[]=$tpl->field_hidden("min_size", 0);
    $form[]=$tpl->field_hidden("max_size", 0);
	echo $tpl->form_outside("{wizard_cache_folder}", @implode("\n", $form),null,"{add}",$jsafter,"AsSquidAdministrator",true);
	
}




function SaveHD(){
	$sock=new sockets();
	foreach ($_POST as $num=>$val){
		$_POST[$num]=url_decode_special_tool($val);
	}
	
	
	$sock->SaveConfigFile(serialize($_POST),"NewCacheCenterWizard");
}

function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $nginxCachesDir=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxCachesDir"));
    $nginxCachesPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxCachesPath");



    if($nginxCachesDir==0){

        echo $tpl->div_warning("{feature_disabled}");
        return true;
    }

    $html[]="<div id='nginx-caches-center'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('nginx-caches-center','$page?table=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tt=time();
	$t=$_GET["t"];
	$_GET["ruleid"]=$_GET["ID"];
	$cache=$tpl->javascript_parse_text("{cache}");
	$size=$tpl->javascript_parse_text("{size}");
	$used=$tpl->javascript_parse_text("{used}");
	$active=$tpl->_ENGINE_parse_body("{active2}");
	$disabled=$tpl->_ENGINE_parse_body("{disabled}");
	$inactive=$tpl->_ENGINE_parse_body("{inactive}");

	$tt=time();
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
	if($DisableAnyCache==1){echo $tpl->FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");return;}
	

	$t=time();
	$add="Loadjs('$page?new-cache-js=yes',true);";
	$add2="Loadjs('$page?new-cache-folder-js=yes',true);";

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.caches.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.caches.progress.log";
	$ARRAY["CMD"]="squid.php?verify-caches-progress=yes";
	$ARRAY["TITLE"]="{verify_caches}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$apply="Loadjs('fw.progress.php?content=$prgress&mainid=progress-cachecenter-restart')";

    $prefix="data-sortable=true class='text-capitalize' data-type='text'";

	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$add2\"><i class='fa fa-plus'></i> {new_cache} {folder}</label>";
    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$apply\"><i class='fa fa-save'></i> {apply_configuration} </label>";
    $btn[]="</div>";

    $html[]="<table id='table-cachecenter-list' class=\"footable table white-bg table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th $prefix style='width:1%;text-align:center'></th>";
	$html[]="<th $prefix style='width:1%;text-align:center'>$used</th>";
	$html[]="<th $prefix>$cache</th>";
	$html[]="<th $prefix>$size</th>";
	$html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$to_remove=$tpl->_ENGINE_parse_body("{to_remove}");
	$license_error=$tpl->_ENGINE_parse_body("{license_error}");
	$sql="SELECT *  FROM caches_center ORDER BY zOrder";
	$results=$q->QUERY_SQL($sql);
	$TRCLASS=null;

    $NGINX_CACHES_DIR=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_CACHES_DIR"));
    $NginxPHPCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxPHPCacheSize"));
    if($NginxPHPCacheSize==0){$NginxPHPCacheSize=2;}

    $current=0;
    $percentcache=0;
    $label="<span class='label label-primary'>{active2}</span>";


    if(isset($NGINX_CACHES_DIR["PHPCACHE"])) {
        $cache_size_bytesK=$NginxPHPCacheSize*1024;
        $cache_size_bytesM=$cache_size_bytesK*1024;
        $cache_size_bytesG=$cache_size_bytesM*1024;
        $current = FormatBytes($NGINX_CACHES_DIR["PHPCACHE"] / 1024);
        $percentcache = $current / $cache_size_bytesG;
        $percentcache = round($percentcache * 100, 2);
        $percentcache = $percentcache . "%";
    }

    $html[]="<tr class='$TRCLASS' id='row-parent-0'>";
    $html[]="<td width='1%'>$label</td>";
    $html[]="<td class=\"center\" width='1%' nowrap><H3>{$percentcache}</H3></td>";
    $html[]="<td width='1%' nowrap><strong>{default} PHP</strong></td>";
    $html[]="<td width='90%' nowrap>PHP</td>";
    $html[]="<td  width='1%' nowrap>$current/{$NginxPHPCacheSize}G</td>";
    $html[]="<td class=center width='1%'></td>";
    $html[]="<td class=center width='1%'></td>";
    $html[]="</tr>";


	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$current=0;$error=null;
		$cachename=$ligne["cachename"];
		$cache_dir=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=abs($ligne["cache_size"]);
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		$label="<span class='label label-warning'>$inactive</span>";
		$cache_size=$cache_size*1024;
        $cache_size_bytes=$cache_size*1024;
		$cache_size=FormatBytes($cache_size);
		$reconstruct=$tpl->icon_recycle("Loadjs('$page?delete-empty-js=yes&ID={$ligne["ID"]}')","AsProxyMonitor");
		$delete=$tpl->icon_delete("Loadjs('$page?delete-item-js=yes&ID={$ligne["ID"]}')","AsProxyMonitor");
        $cache_row=$tpl->td_href($cachename,"{click_to_edit}","Loadjs('$page?cache-edit-js=$ID');");

		if(isset($NGINX_CACHES_DIR[$ID])){
			$current=FormatBytes($NGINX_CACHES_DIR[$ID]/1024);
            $percentcache=intval($NGINX_CACHES_DIR["PHPCACHE"]) /$cache_size_bytes;
            $percentcache=round($percentcache*100,2);
            $percentcache=$percentcache."%";
			$label="<span class='label label-primary'>$active</span>";
		}
		
		if($ligne["enabled"]==0){
			$current=$tpl->icon_nothing();
			$label="<span class='label'>$disabled</span>";
		}
		
		if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
			$current=$tpl->icon_nothing();
			$label="<span class='label label-danger'>$license_error</span>";
			
		}
		
		if($ligne["remove"]==1){
			$delete=$tpl->icon_nothing();
			$label="<span class='label label-danger'>$to_remove</span>";
		}

        if($ID==1){
            $label="<span class='label label-primary'>{active2}</span>";
            $delete=$tpl->icon_nothing();
            $cache_row=$cachename;
        }


        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$ID')");
		$html[]="<tr class='$TRCLASS' id='row-parent-$ID'>";
		$html[]="<td width='1%'>$label</td>";
		$html[]="<td class=\"center\" width='1%' nowrap><H3>{$percentcache}</H3></td>";
		$html[]="<td width='1%' nowrap><strong>$cache_row</strong></td>";
		$html[]="<td width='90%' nowrap>$cache_dir$error</td>";
		$html[]="<td  width='1%' nowrap>$current/$cache_size</td>";
        $html[]="<td class=center width='1%'>$enable</td>";
        $html[]="<td class=center width='1%'>$delete</td>";
		$html[]="</tr>";

	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{sites_caching}";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{nginx_caching_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	$jstiny;
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-cachecenter-list').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	";

	echo $tpl->_ENGINE_parse_body($html);

}