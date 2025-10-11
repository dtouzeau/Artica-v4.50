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
if(isset($_POST["SaveHD"])){SaveHD();exit;}
if(isset($_GET["delete-empty-js"])){delete_empty_js();exit;}
if(isset($_GET["delete-item-js"])){delete_js();exit;}
if(isset($_POST["empty-item"])){exit;}
if(isset($_POST["delete-item"])){delete();exit;}
if(isset($_GET["cache-edit-js"])){cache_edit_js();exit;}
if(isset($_GET["cache-edit"])){cache_edit();exit;}
if(isset($_POST["cache_directory"])){cache_save();exit;}
if(isset($_POST["new-cache-disk"])){new_cache_folder_save();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{caches_center}",
        ico_hd,
        "{caches_center_explain}","$page?table=yes","proxy-caches-center",
        "progress-cachecenter-restart",false,"table-loader-cachecenteer-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function cache_edit_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=$_GET["cache-edit-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$ligne=$q->mysqli_fetch_array("SELECT cachename FROM squid_caches_center WHERE ID='$ID'");
	$title=$ligne["cachename"];
	$tpl->js_dialog($title, "$page?cache-edit=$ID");	
    return true;
}

function cache_edit():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$users=new usersMenus();
	$max_size_explain=null;
	$ID=intval($_GET["cache-edit"]);
	$DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
	$SquidForceCacheTypes=$sock->GET_INFO("SquidForceCacheTypes");
	if($SquidForceCacheTypes==null){$SquidForceCacheTypes="aufs";}
	$SquidSimpleConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSimpleConfig"));
	
	$cpunumber=$users->CPU_NUMBER-1;
	if($cpunumber<1){$cpunumber=1;}
	for($i=1;$i<$cpunumber+1;$i++){
		$CPUZ[$i]="{process} $i";
	}
	
	$t=time();
	$bt="{add}";
	
	$cpu=1;
	$cachename=time();
	$SquidMaximumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMaximumObjectSize"));
	$SquidMiniMumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMiniMumObjectSize"));
	if($SquidMaximumObjectSize==0){$SquidMaximumObjectSize=32768;}
	$min_size=$SquidMiniMumObjectSize;
	$max_size=$SquidMaximumObjectSize;
	$RemoveSize=0;
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_caches_center WHERE ID='$ID'");
		if(!$q->ok){echo $q->mysql_error_html();}
		$cachename=$ligne["cachename"];
		$cache_directory=$ligne["cache_dir"];
		$cache_size=$ligne["cache_size"];
		$cache_dir_level1=$ligne["cache_dir_level1"];
		$cache_dir_level2=$ligne["cache_dir_level2"];
		$cache_type=$ligne["cache_type"];
		$enabled=$ligne["enabled"];
		$cachename=$ligne["cachename"];
		$cpu=$ligne["cpu"];
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		$RemoveSize=intval($ligne["RemoveSize"]);
		$bt="{apply}";
	}
    if($ID==0){

    }

	if($RemoveSize==0){$RemoveSize=1;}else{$RemoveSize=0;}
	if($max_size==0){
		$max_size=$SquidMaximumObjectSize;
		$max_size_explain=" <small>{default}: $SquidMaximumObjectSize KB</small>";
	}
	if($max_size==$min_size){$max_size=$SquidMaximumObjectSize;}
	if($max_size==$min_size){$max_size=32768;}
	
	//default
	if($cache_directory==null){$cache_directory="/home/squid/caches/cache-".time();}
	if(!is_numeric($cache_size)){$cache_size=5000;}
	if(!is_numeric($cache_dir_level1)){$cache_dir_level1=16;}
	if(!is_numeric($cache_dir_level2)){$cache_dir_level2=256;}
	if(!is_numeric($enabled)){$enabled=1;}
	
	if($cache_size<1){$cache_size=5000;}
	if($cache_dir_level1<16){$cache_dir_level1=16;}
	if($cache_dir_level2<64){$cache_dir_level2=64;}
	if($cache_type==null){$cache_type=$SquidForceCacheTypes;}
	
	$caches_types=unserialize(base64_decode($sock->getFrameWork("squid.php?caches-types=yes")));
	$caches_types[null]='{select}';
	$caches_types["tmpfs"]="{squid_cache_memory}";
	$caches_types["Cachenull"]="{without_cache}";

	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,true);
	$form[]=$tpl->field_text("cachename","{name}",$cachename,true);

	$CPUZ=array();
	$SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
	if(count($SquidSMPConfig)==0){$SquidSMPConfig[1]=1;}
    foreach ($SquidSMPConfig as $num=>$val){
		if($val==null){continue;}
		$val=intval($val);
		if($val==0){continue;}
		$CPUZ[$num]="{process} $num, CPU #$val";
	}
    if(count($CPUZ)>1) {
        $form[] = $tpl->field_array_hash($CPUZ, "CPU", "{process}", $cpu);
    }else{
        $tpl->field_hidden("CPU","1");
    }
	
	$tpl->field_hidden("cache_dir_level1","$cache_dir_level1");
    $tpl->field_hidden("cache_dir_level2","$cache_dir_level2");
	$form[]=$tpl->field_info("cache_directory", "{directory}", $cache_directory);
    if(count($CPUZ)==0) {
        $form[] = $tpl->field_array_hash($caches_types, "cache_type", "{type}", $cache_type);
    }else{
        $tpl->field_hidden("cache_type",$cache_type);
    }
	$form[]=$tpl->field_numeric("squid-cache-size","{cache_size} (MB)",$cache_size,"{cache_size_text}");
	$form[]=$tpl->field_checkbox("RemoveSize","{limits}",$RemoveSize,"min_size,max_size");
	$form[]=$tpl->field_numeric("min_size","{min_size} (KB)",$min_size,"{cache_dir_min_size_text}");
	$form[]=$tpl->field_numeric("max_size","{max_size}$max_size_explain (KB)",$max_size,"{cache_dir_max_size_text}");
	echo $tpl->form_outside("$cachename ($cache_type)", @implode("\n", $form),"{warn_calculate_nothdsize}",$bt,"LoadAjax('table-loader-cachecenteer-service','$page?table=yes');BootstrapDialog1.close();","AsSquidAdministrator");
    
    return true;
}

function new_cache_folder_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $IsSNMP=IsSNMP();
	$cachename=basename($_POST["new-cache-disk"]);
	$CPU=intval($_POST["CPU"]);
	$cache_directory=$_POST["new-cache-disk"]."/disk";
	$size=$_POST["cache_size"];
	$cache_dir_level1=16;
	$cache_dir_level2=256;
	$enabled=1;
	$min_size=$_POST["min_size"];;
	$max_size=$_POST["max_size"];
	$RemoveSize=0;
	$cache_type="aufs";
    if($IsSNMP){$cache_type="rock";}
    $CPU=1;
	
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	
$sql="INSERT INTO squid_caches_center
			(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,RemoveSize)
			VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',$enabled,0,0,1,$min_size,$max_size,$RemoveSize)";
	
	$q->QUERY_SQL("$sql");
	
	if(!$q->ok){echo $q->mysql_error." LINE ".__LINE__." $sql";}

	
}

function cache_save(){
	
	$_POST=mysql_escape_line_query($_POST);
	$cache_directory=$_POST["cache_directory"];
	$cache_type=$_POST["cache_type"];
	$size=$_POST["squid-cache-size"];
	$cache_dir_level1=$_POST["cache_dir_level1"];
	$cache_dir_level2=$_POST["cache_dir_level2"];
	$CPU=intval($_POST["CPU"]);
	$cachename=$_POST["cachename"];
	$enabled=$_POST["enabled"];
	$min_size=$_POST["min_size"];
	$max_size=$_POST["max_size"];
	$RemoveSize=$_POST["RemoveSize"];
	if($RemoveSize==0){$RemoveSize=1;}else{$RemoveSize=0;}
	if($CPU==0){$CPU=1;}
	$ID=$_POST["ID"];
	if($cache_type=="rock"){$CPU=0;}
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	

	
	if($cache_type=="tmpfs"){
		$users=new usersMenus();
		$memMB=$users->MEM_TOTAL_INSTALLEE/1024;
		$memMB=$memMB-1500;
		if($size>$memMB){
			$size=$memMB-100;
		}
	}
	
	
	if(substr($cache_directory, 0,1)<>'/'){$cache_directory="/$cache_directory";}
	
	
	if($ID==0){
	    $sql="INSERT INTO squid_caches_center
				(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,RemoveSize)
				VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',$enabled,0,0,1,$min_size,$max_size,$RemoveSize)";
		$q->QUERY_SQL($sql);
		echo $sql;

	}else{
		$sql="UPDATE squid_caches_center SET
				cachename='$cachename',
				cpu=$CPU,
				cache_size='$size',
				min_size='$min_size',
				max_size='$max_size',
				RemoveSize='$RemoveSize',
				enabled=$enabled
				WHERE ID=$ID";
		$q->QUERY_SQL($sql,"artica_backup");
	
	}
	
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
	
	
}

function delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		exit();
	}
	$ID=$_GET["ID"];
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$ligne=$q->mysqli_fetch_array("SELECT cachename FROM squid_caches_center WHERE ID='$ID'");
	$title=$ligne["cachename"];
	$action_remove_cache_ask="<strong>$title</strong><br>".$tpl->javascript_parse_text("{action_remove_cache_ask}");
	$tpl->js_confirm_delete($action_remove_cache_ask,"delete-item", $ID,"LoadAjax('table-loader-cachecenteer-service','$page?table=yes');");
}

function move_item_js(){
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$ID=$_GET["ID"];
	$dir=$_GET["dir"];
	$ligne=$q->mysqli_fetch_array("SELECT zOrder,cpu FROM squid_caches_center WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}

	$cpu=$ligne["cpu"];
	$CurrentOrder=$ligne["zOrder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE squid_caches_center SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder' AND `cpu`='$cpu'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$q->mysql_error."');";}


	$sql="UPDATE squid_caches_center SET zOrder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$q->mysql_error."');";}

	$results=$q->QUERY_SQL("SELECT ID FROM squid_caches_center WHERE `cpu`='$cpu' ORDER by zOrder","artica_backup");
	if(!$q->ok){echo "alert('".$q->mysql_error;}
	$c=1;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$sql="UPDATE squid_caches_center SET zOrder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "alert('".$q->mysql_error."');";}
		$c++;
	}
}
function delete(){
	$ID=$_POST["delete-item"];
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$sql="UPDATE squid_caches_center SET `remove`=1 WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
}

function delete_empty_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$ligne=$q->mysqli_fetch_array("SELECT cachename FROM squid_caches_center WHERE ID='$ID'");
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
	$jsafter="BootstrapDialog1.close();LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$form[]=$tpl->field_browse_directory("new-cache-disk", "{folder}", null,null,true);
	$form[]=$tpl->field_numeric("cache_size","{cache_size} (MB)",1024);
	$form[]=$tpl->field_hidden("CPU", 1);
	$form[]=$tpl->field_numeric("min_size","{min_size} (KB)",0,"{cache_dir_min_size_text}");
	$form[]=$tpl->field_numeric("max_size","{max_size} (KB)",3145728,"{cache_dir_max_size_text}");
	echo $tpl->form_outside("{wizard_cache_folder}", @implode("\n", $form),null,"{add}",$jsafter,"AsSquidAdministrator",true);
	
}

function new_cache_step1(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){echo $tpl->FATAL_ERROR_SHOW_128("{this_feature_is_disabled_corp_license}");return;}
	if(!$users->AsSquidAdministrator){echo $tpl->FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS2");return;}
	
	
	$html="<H1>{wizard_cache_disk}</H1>
	<div class='alert alert-info'>{wizard_cache_disk_explain}</div>
	<H2 id='wizard_cache_disk_title'>{scanning_your_hardware}....</H2>
	<div id='wizard_cache_disk'></div>		
	<script>
		LoadAjax('wizard_cache_disk','$page?new-cache-step2=yes');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function new_cache_step2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=$_GET["t"];
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes&tenir=yes");
	echo "<script>LoadAjax('wizard_cache_disk','$page?new-cache-step3=yes');</script>";
}
function SaveHD(){
	$sock=new sockets();
	foreach ($_POST as $num=>$val){
		$_POST[$num]=url_decode_special_tool($val);
	}
	
	
	$sock->SaveConfigFile(serialize($_POST),"NewCacheCenterWizard");
}
function new_cache_step3(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$t=$_GET["t"];
	$DISKS=array();
	$datas=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/usb.scan.serialize"));
	
	echo "<script>document.getElementById('wizard_cache_disk_title').innerHTML=''</script>";
	
	$NEWARRAY=array();
    foreach ($datas as $DEV=>$MAIN_HD){
		if($DEV=="UUID"){continue;}
		$SIZEZ=intval($datas[$DEV]["SIZE"]);
		if($SIZEZ==0){continue;}
	
		$OCT=$datas["$DEV"]["OCT"];
		$ID_VENDOR=$datas[$DEV]["ID_VENDOR"];
	
		$PARTITIONS=$MAIN_HD["PARTITIONS"];
		$_COUNTPARTITIONS=count($PARTITIONS);
		if(count($PARTITIONS)>0){continue;}
		$DEV_enc=urlencode($DEV);
		$size_enc=urlencode($datas[$DEV]["SIZE"]);
		
		$NEWARRAY[$DEV]["DISK"]=$DEV;
		$NEWARRAY[$DEV]["type"]=$ID_VENDOR;
		$NEWARRAY[$DEV]["SIZE"]=$datas[$DEV]["SIZE"];
		$NEWARRAY[$DEV]["PART"]=$_COUNTPARTITIONS;
		$NEWARRAY[$DEV]["OCT"]=$datas[$DEV]["OCT"];
		
	}
	
	
	if(count($NEWARRAY)==0){
		
		echo $tpl->FATAL_ERROR_SHOW_128("<H1>{no_free_disk_found}</H1>
				<H2>{no_free_disk_found_explain}</H2>
				
				")
				;
		return;
	}
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div id='div-disk-list'>
			<table id='table-disk-list' class=\"footable table white-bg table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
			$html[]="<thead>";
			$html[]="<tr>";
	
			$disk=$tpl->_ENGINE_parse_body("{disk}");
			$type=$tpl->_ENGINE_parse_body("{type}");
			$size=$tpl->_ENGINE_parse_body("{size}");
			$partitions=$tpl->_ENGINE_parse_body("{partitions}");
	
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$disk</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$size</th>";
			$html[]="<th data-sortable=false></th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";
			$TRCLASS=null;
            foreach ($NEWARRAY as $DEV=>$MAIN){
				if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
				if($MAIN["TYPE"]==null){$MAIN["TYPE"]=$tpl->icon_nothing();}
				$DEV_enc=urlencode($DEV);
				$size_enc=urlencode($MAIN["SIZE"]);
				$js="LoadAjax('wizard_cache_disk','$page?new-cache-step4=yes&dev=$DEV_enc&size=$size_enc&oct={$MAIN["OCT"]}');";
					
				
				
				$html[]="<tr class='$TRCLASS' id='row-parent-$DEV'>";
				$html[]="<td><strong>$DEV</strong></td>";
				$html[]="<td>{$MAIN["TYPE"]}</td>";
				$html[]="<td><strong>{$MAIN["SIZE"]}</strong></td>";
				$html[]="<td class=center>". $tpl->button_autnonome("{select}", $js, "fa-hdd")."</td>";
				$html[]="</tr>";
		}	

			$html[]="</tbody>";
			$html[]="<tfoot>";
			
			$html[]="<tr>";
			$html[]="<td colspan='4'>";
			$html[]="<ul class='pagination pull-right'></ul>";
			$html[]="</td>";
			$html[]="</tr>";
			$html[]="</tfoot>";
			$html[]="</table></div>";
			$html[]="<script>";
			$html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-disk-list').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	";
			$html[]="</script>";
			
			echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function new_cache_step4(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$dev=$_GET["dev"];

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.log";
	$ARRAY["CMD"]="squid2.php?create-cache-wizard=yes";
	$ARRAY["TITLE"]="{creating_new_cache}";
	$ARRAY["AFTER"]="BootstrapDialog1.close();LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=format-hd-progress')";
	
	$size=$_GET["size"];
    $form[]=$tpl->field_hidden("CPU", "1");
	$form[]=$tpl->field_hidden("SaveHD", "yes");
	$form[]=$tpl->field_hidden("dev", "$dev");
	$form[]=$tpl->field_hidden("size", "$size");
	$form[]=$tpl->field_hidden("oct", "{$_GET["oct"]}");
	$html[]="<div id='format-hd-progress'></div>";
	$html[]=$tpl->form_outside("{confirm}...{create_cache_on} $dev", @implode("\n",$form),"{this_format_data_lost}","{create_cache_on} $dev ($size)",$jsrestart);
	echo @implode("\n", $html);
}

function IsSNMP():bool{
    $CPUZ=array();
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if(!$SquidSMPConfig){
        $SquidSMPConfig=array();
    }
    if(count($SquidSMPConfig)==0){$SquidSMPConfig[1]=1;}
    foreach ($SquidSMPConfig as $num=>$val){
        if($val==null){continue;}
        $val=intval($val);
        if($val==0){continue;}
        $CPUZ[$num]=true;
    }
    if(count($CPUZ)>1){return true;}
    return false;
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $_GET["ruleid"]=0;
    if(isset($_GET["ID"])) {
        $_GET["ruleid"] = intval($_GET["ID"]);
    }
	$cache=$tpl->javascript_parse_text("{cache}");
	$directory=$tpl->_ENGINE_parse_body("{directory}");
	$size=$tpl->javascript_parse_text("{size}");
	$order=$tpl->javascript_parse_text("{order}");
	$used=$tpl->javascript_parse_text("{used}");
	$active=$tpl->_ENGINE_parse_body("{active2}");
	$disabled=$tpl->_ENGINE_parse_body("{disabled}");
	$inactive=$tpl->_ENGINE_parse_body("{inactive}");
	$rebuild=$tpl->_ENGINE_parse_body("{rebuild}");
    $IsSMP=IsSNMP();
	$squid_caches_infos=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_get_cache_infos.db"));

	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
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



    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_cache} {disk}</label>";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$add2\"><i class='fa fa-plus'></i> {new_cache} {folder}</label>";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$apply\"><i class='fa fa-save'></i> {apply_configuration} </label>";
    $bts[]="</div>";

	$html[]="<table id='table-cachecenter-list' class=\"table white-bg table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	

	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>$used</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%;text-align:center'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$cache</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$directory</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$size</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>$rebuild</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	$SquidMaximumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMaximumObjectSize"));
	if($SquidMaximumObjectSize==0){$SquidMaximumObjectSize=32768;}
	$files=$tpl->javascript_parse_text("{files}");
	$to=$tpl->javascript_parse_text("{to}");

	$to_remove=$tpl->_ENGINE_parse_body("{to_remove}");
	$license_error=$tpl->_ENGINE_parse_body("{license_error}");
	$sql="SELECT *  FROM squid_caches_center ORDER BY zOrder";
	$results=$q->QUERY_SQL($sql);
	$TRCLASS=null;
	$unsuported_cache_smp="<span class='text-warning'><i class='".ico_emergency."'></i>&nbsp;".$tpl->_ENGINE_parse_body("{unsuported_cache_smp}</span>");

	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$current=0;$error=null;
		$cachename=$ligne["cachename"];
		if($cachename==null){continue;}
		$cache_dir=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=abs($ligne["cache_size"]);
		$percentcache=floatval($ligne["percenttext"]);
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		if($max_size==0){$max_size=$SquidMaximumObjectSize;}
		$cpu=$ligne["cpu"];
		$label="<span class='label label-warning'>$inactive</span>";

		
		$cache_size=$cache_size*1024;
		$cache_size=FormatBytes($cache_size);
		$reconstruct=$tpl->icon_recycle("Loadjs('$page?delete-empty-js=yes&ID={$ligne["ID"]}')","AsProxyMonitor");
		$delete=$tpl->icon_delete("Loadjs('$page?delete-item-js=yes&ID={$ligne["ID"]}')","AsProxyMonitor");
		
		$up=$tpl->icon_up("Loadjs('$page?move-item-js=yes&ID={$ligne["ID"]}&dir=0');");
		$down=$tpl->icon_down("Loadjs('$page?move-item-js=yes&ID={$ligne["ID"]}&dir=1');");
		
		if(isset($squid_caches_infos[$cache_dir])){
			$current=FormatBytes($squid_caches_infos[$cache_dir]["CURRENT"]);
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
			$reconstruct=$tpl->icon_nothing();
			$delete=$tpl->icon_nothing();
			$up=$tpl->icon_nothing();
			$down=$tpl->icon_nothing();
			$label="<span class='label label-danger'>$to_remove</span>";
		}

        $percentcache="{$percentcache}%";

		if($IsSMP){
            if($cache_type<>"rock"){
                $label="<span class='label'>$disabled</span>";
                $up=$tpl->icon_nothing();
                $down=$tpl->icon_nothing();
                $reconstruct=$tpl->icon_nothing();
                $error="<br><strong>$unsuported_cache_smp</strong>";
                $percentcache=$tpl->icon_nothing();
            }
        }

		
		$html[]="<tr class='$TRCLASS' id='row-parent-$ID'>";
		$html[]="<td>$label</td>";
		$html[]="<td class=\"center\"><H3>{$percentcache}</H3></td>";
		$html[]="<td class=\"center\">{$ligne["zOrder"]}</td>";
		$html[]="<td>". $tpl->td_href($cachename,"{click_to_edit}","Loadjs('$page?cache-edit-js=$ID');")."<br><i><small>$files: ".FormatBytes($min_size)." $to ".FormatBytes($max_size)."</small></i></td>";
		$html[]="<td>$cache_dir$error</td>";
		$html[]="<td>$current/$cache_size</td>";
		$html[]="<td class=center nowrap>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td class=center>$reconstruct</td>";
		$html[]="<td class=center>$delete</td>";
		$html[]="</tr>";
		
		

	}
	$html[]="</tbody>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{caches_center}";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{caches_center_explain}";
    $TINY_ARRAY["URL"]="proxy-caches-center";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
	$html[]="$jstiny";
    $html[]="</script>";

	echo @implode("\n", $html);

}