<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["move-item-js"])){move_item_js();exit;}
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
	$ID=intval($_GET["cache-edit-js"]);
	$ligne=caches_api_get($ID);
	$title=htmlspecialchars((string)($ligne["cachename"]??""));
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
		$err="";
		$ligne=caches_api_get($ID,$err);
		if($err<>""){echo $tpl->div_error($err);return false;}
		$cachename=$ligne["cachename"];
		$cache_directory=$ligne["cache_dir"];
		$cache_size=$ligne["cache_size"];
		$cache_dir_level1=$ligne["cache_dir_level1"];
		$cache_dir_level2=$ligne["cache_dir_level2"];
		$cache_type=$ligne["cache_type"];
		$enabled=empty($ligne["enabled"])?0:1;
		$cpu=$ligne["cpu"];
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		$RemoveSize=empty($ligne["RemoveSize"])?0:1;
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
	
	// Types de stockage compiles dans Squid : articarest /proxy/build/features.
	$caches_types=array("aufs"=>"aufs");
	$features=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/build/features"));
	if(is_object($features) && isset($features->Info->storeio) && is_array($features->Info->storeio)){
		foreach($features->Info->storeio as $storeio){
			$storeio=(string)$storeio;
			if($storeio=="aufs"){continue;}
			$caches_types[$storeio]="{squid_$storeio}";
		}
	}
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
	$folder=rtrim(trim((string)$_POST["new-cache-disk"]),"/");
	if($folder==""){echo $tpl->javascript_parse_text("{directory}: {error}");return;}
	// The squid -z run creates a "disk" sub-directory of the chosen folder.
	$row=array(
		"cachename"        =>basename($folder),
		"cache_dir"        =>"$folder/disk",
		"cache_type"       =>IsSNMP()?"rock":"aufs",
		"cache_size"       =>intval($_POST["cache_size"]),
		"min_size"         =>intval($_POST["min_size"]),
		"max_size"         =>intval($_POST["max_size"]),
		"cache_dir_level1" =>16,
		"cache_dir_level2" =>256,
		"cpu"              =>1,
		"enabled"          =>true,
		"RemoveSize"       =>false,
	);
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proxy/caches",$row),true);
	$err=caches_api_error($json,"/proxy/caches");
	if($err<>""){echo $err;return;}
	admin_tracks("Add a new proxy cache {$row["cachename"]} in {$row["cache_dir"]} ({$row["cache_size"]}MB)");
}

function cache_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["ID"]);
	$cache_type=(string)$_POST["cache_type"];
	$size=intval($_POST["squid-cache-size"]);
	$CPU=intval($_POST["CPU"]);
	if($CPU==0){$CPU=1;}
	// The checkbox reads "limits": checked = keep min/max, RemoveSize drops them.
	$RemoveSize=intval($_POST["RemoveSize"])==0;

	if($cache_type=="tmpfs"){
		$users=new usersMenus();
		$memMB=$users->MEM_TOTAL_INSTALLEE/1024;
		$memMB=$memMB-1500;
		if($size>$memMB){$size=intval($memMB-100);}
	}

	$row=array();
	if($ID>0){
		// The directory and the store type are not editable: kept as stored.
		$err="";
		$row=caches_api_get($ID,$err);
		if($err<>""){echo $err;return;}
	}else{
		$cache_directory=trim((string)$_POST["cache_directory"]);
		if(substr($cache_directory,0,1)<>'/'){$cache_directory="/$cache_directory";}
		$row["cache_dir"]=$cache_directory;
		$row["cache_type"]=$cache_type;
		$row["cache_dir_level1"]=intval($_POST["cache_dir_level1"]);
		$row["cache_dir_level2"]=intval($_POST["cache_dir_level2"]);
	}
	$row["cachename"]=trim((string)$_POST["cachename"]);
	$row["cpu"]=$CPU;
	$row["cache_size"]=$size;
	$row["min_size"]=intval($_POST["min_size"]);
	$row["max_size"]=intval($_POST["max_size"]);
	$row["RemoveSize"]=$RemoveSize;
	$row["enabled"]=intval($_POST["enabled"])==1;

	if($ID==0){
		$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proxy/caches",$row),true);
		$err=caches_api_error($json,"/proxy/caches");
	}else{
		$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/proxy/caches/$ID",$row),true);
		$err=caches_api_error($json,"/proxy/caches/$ID");
	}
	if($err<>""){echo $err;return;}
	admin_tracks("Save proxy cache {$row["cachename"]} ({$row["cache_dir"]}, {$size}MB)");
}

function delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		exit();
	}
	$ID=intval($_GET["ID"]);
	$ligne=caches_api_get($ID);
	$title=htmlspecialchars((string)($ligne["cachename"]??""));
	$action_remove_cache_ask="<strong>$title</strong><br>".$tpl->javascript_parse_text("{action_remove_cache_ask}");
	$tpl->js_confirm_delete($action_remove_cache_ask,"delete-item", $ID,"LoadAjax('table-loader-cachecenteer-service','$page?table=yes');");
}

function move_item_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["ID"]);
	$dir=intval($_GET["dir"])==0?"up":"down";
	$endpoint="/proxy/caches/$ID/move?dir=$dir";
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint),true);
	$err=caches_api_error($json,$endpoint);
	if($err<>""){echo "alert('".$tpl->javascript_parse_text($err)."');";return;}
	echo "LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
}
function delete(){
	$ID=intval($_POST["delete-item"]);
	// Marked only: the next "Apply configuration" erases the directory, then the row.
	$endpoint="/proxy/caches/$ID/remove";
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($endpoint,array()),true);
	$err=caches_api_error($json,$endpoint);
	if($err<>""){echo $err;return;}
	admin_tracks("Mark proxy cache #$ID for removal");
}

function delete_empty_js(){
    if(!fw_progress_page_priv("AsSquidAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["ID"]);
	$ligne=caches_api_get($ID);
	$title=htmlspecialchars((string)($ligne["cachename"]??""));
	$action_empty_cache_ask=$tpl->javascript_parse_text("{action_empty_cache_ask}");
	$action_empty_cache_ask=str_replace("%s", $title, $action_empty_cache_ask);
	
	// articarest squid/proxy_caches_jobs.go EmptyCache (port of exec.squid.rebuild.caches.php --empty).
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.caches.progress.log";
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.caches.progress";
	$ARRAY["CMD"]="/proxy/caches/$ID/empty";
	$ARRAY["TITLE"]="{empty} $title";
	$ARRAY["AFTER"]="LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-cachecenter-restart')";
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
	// Saving only records the cache: apply it (caches.conf, squid -z, reload), as the disk wizard does.
	// Without it the folder showed as active while Squid never used it.
	$jsafter="BootstrapDialog1.close();".$tpl->framework_buildjs(
		"/proxy/caches/verify/reload",
		"squid.caches.progress",
		"squid.caches.progress.log",
		"progress-cachecenter-restart",
		"LoadAjax('table-loader-cachecenteer-service','$page?table=yes');"
	);
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
	// The disks are listed by articarest in step 3 (cmd.php?usb-scan-write no longer exists).
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
	echo "<script>document.getElementById('wizard_cache_disk_title').innerHTML=''</script>";

	// Disks without any partition (HDD or SSD, not btrfs members), from articarest
	// (articadisks GetFreeDisks). It replaces ressources/usb.scan.serialize, which nothing writes
	// any more since cmd.php?usb-scan-write was removed: the wizard found no disk at all.
	$NEWARRAY=array();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/harddrives/free"));
	if(!$json || !property_exists($json,"Status") || !$json->Status){
		$err=($json && property_exists($json,"Error"))?$json->Error:"/system/harddrives/free";
		echo $tpl->div_error($err);
		return;
	}
	$disks=(property_exists($json,"Disks") && is_array($json->Disks))?$json->Disks:array();
	foreach ($disks as $disk){
		$name=trim((string)$disk->name);
		$bytes=intval($disk->size);
		if($name=="" || $bytes==0){continue;}
		$DEV="/dev/$name";
		$NEWARRAY[$DEV]["DISK"]=$DEV;
		$NEWARRAY[$DEV]["type"]=(string)$disk->type;
		$NEWARRAY[$DEV]["SIZE"]=FormatBytes($bytes/1024,true);
		$NEWARRAY[$DEV]["PART"]=0;
		$NEWARRAY[$DEV]["OCT"]=$bytes;
	}

	if(count($NEWARRAY)==0){
		
		echo $tpl->FATAL_ERROR_SHOW_128("<H1>{no_free_disk_found}</H1>
				<H2>{no_free_disk_found_explain}</H2>
				
				")
				;
		return;
	}
	
	// L'en-tete declarait quatre colonnes mais {partitions} etait traduit sans
	// jamais etre affiche; la colonne "type" restait vide en permanence (voir
	// caches_disk_row).
	$html[]="<div id='div-disk-list'>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{disk}</th>";
	$html[]="<th class='center'>{size}</th>";
	$html[]="<th class='center'>{partitions}</th>";
	$html[]="<th class='center'>{select}</th>";
	$html[]="</tr></thead><tbody id='cd-rows'>";

	$c=0;
	foreach ($NEWARRAY as $DEV=>$MAIN){
		$c++;
		$DEV_enc=urlencode($DEV);
		$size_enc=urlencode($MAIN["SIZE"]);
		$js="LoadAjax('wizard_cache_disk','$page?new-cache-step4=yes&dev=$DEV_enc&size=$size_enc&oct={$MAIN["OCT"]}');";
		$html[]=caches_disk_row($tpl,$DEV,$MAIN,$js);
	}

	// L'ancienne version placait le pied A L'INTERIEUR du corps du tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c</span>";
	$html[]="<span class='awc-pg' id='cd-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="</div>";

	// Feuille de style, script et pager concatenes APRES l'analyse: la regex
	// #{(.+?)}# de _ENGINE_parse_body prend chaque corps d'accolade CSS ou
	// JavaScript pour un {token}.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
	    .awc_pager_js(50,"cd-rows","cd-pager");
	
}

function new_cache_step4(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	// Pentest 2026-11-04 : dev, size et oct etaient recopies sans encodage dans la page (XSS).
	$dev=preg_replace('#[^A-Za-z0-9/_.:-]#','',(string)$_GET["dev"]);

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.log";
	// articarest squid/proxy_caches_jobs.go CacheWizardDisk (port of exec.squid.cache.wizard.php),
	// reads the disk saved by SaveHD under NewCacheCenterWizard.
	$ARRAY["CMD"]="/proxy/caches/wizard/disk";
	$ARRAY["TITLE"]="{creating_new_cache}";
	$ARRAY["AFTER"]="BootstrapDialog1.close();LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=format-hd-progress')";
	
	$size=preg_replace('#[^A-Za-z0-9.,]#','',(string)$_GET["size"]);
    $form[]=$tpl->field_hidden("CPU", "1");
	$form[]=$tpl->field_hidden("SaveHD", "yes");
	$form[]=$tpl->field_hidden("dev", "$dev");
	$form[]=$tpl->field_hidden("size", "$size");
	$form[]=$tpl->field_hidden("oct", intval($_GET["oct"]));
	$html[]="<div id='format-hd-progress'></div>";
	$html[]=$tpl->form_outside("{confirm}...{create_cache_on} $dev", @implode("\n",$form),"{this_format_data_lost}","{create_cache_on} $dev ($size)",$jsrestart);
	echo @implode("\n", $html);
}

// caches_smp_workers is the number of Squid workers as articarest computes it (squid getWorkers).
function caches_smp_workers():int{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPDisabled"))==1){return 1;}
    $SquidSMPConfig=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $c=is_array($SquidSMPConfig)?count($SquidSMPConfig):0;
    if($c==0){$c=1;}
    $users=new usersMenus();
    $cpus=intval($users->CPU_NUMBER);
    if($cpus>0 && $c>$cpus){$c=$cpus;}
    return $c;
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
	$size=$tpl->javascript_parse_text("{size}");
	$order=$tpl->javascript_parse_text("{order}");
	$used=$tpl->javascript_parse_text("{used}");
	$active=$tpl->_ENGINE_parse_body("{active2}");
	$disabled=$tpl->_ENGINE_parse_body("{disabled}");
	$inactive=$tpl->_ENGINE_parse_body("{inactive}");
	// {directory} et {rebuild} etaient traduits pour deux en-tetes de colonne
	// qui n'existent plus: le repertoire est en sous-titre, la reconstruction
	// est une icone de la colonne d'actions.
    $IsSMP=IsSNMP();
    $SMPWorkers=caches_smp_workers();
	$squid_caches_infos=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_get_cache_infos.db"));

	if(!is_array($squid_caches_infos)){$squid_caches_infos=array();}
	$DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
	if($DisableAnyCache==1){echo $tpl->FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");return;}
	

	$t=time();
	$add="Loadjs('$page?new-cache-js=yes',true);";
	$add2="Loadjs('$page?new-cache-folder-js=yes',true);";

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.caches.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.caches.progress.log";
	$ARRAY["CMD"]="/proxy/caches/verify";
	$ARRAY["TITLE"]="{verify_caches}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-cachecenteer-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$apply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-cachecenter-restart')";



    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_cache} {disk}</label>";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$add2\"><i class='fa fa-plus'></i> {new_cache} {folder}</label>";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$apply\"><i class='fa fa-save'></i> {apply_configuration} </label>";
    $bts[]="</div>";

	// Neuf colonnes dont quatre d'une icone chacune et deux sans libelle. Le nom
	// du cache, son repertoire, la plage de taille des objets et le message
	// d'erreur SMP sont regroupes dans la colonne d'identite; les quatre icones
	// dans une colonne d'actions.
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>$cache</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th class='center'>$used</th>";
	$html[]="<th class='center'>$size</th>";
	$html[]="<th class='center'>$order</th>";
	$html[]="<th class='center'>{actions}</th>";
	$html[]="</tr></thead><tbody id='cc-rows'>";

	$jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	$SquidMaximumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMaximumObjectSize"));
	if($SquidMaximumObjectSize==0){$SquidMaximumObjectSize=32768;}
	$files=$tpl->javascript_parse_text("{files}");
	$to=$tpl->javascript_parse_text("{to}");

	$to_remove=$tpl->_ENGINE_parse_body("{to_remove}");
	$license_error=$tpl->_ENGINE_parse_body("{license_error}");
	$err="";
	$results=caches_api_list($err);
	if($err<>""){echo $tpl->div_error($err);return;}
	$ROWS=0;
	$unsuported_cache_smp="<span class='text-warning'><i class='".ico_emergency."'></i>&nbsp;".$tpl->_ENGINE_parse_body("{unsuported_cache_smp}</span>");

	
	foreach ($results as $index=>$ligne){
		$ID=intval($ligne["ID"]);
		$current=0;$error=null;
		$cachename=$ligne["cachename"];
		if($cachename==null){continue;}
		$ROWS++;
		$cache_dir=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=abs($ligne["cache_size"]);
		// percenttext was never written by anyone: the column always showed 0%.
		$percentcache=0;
		$min_size=intval($ligne["min_size"]);
		$max_size=intval($ligne["max_size"]);
		if($max_size==0){$max_size=$SquidMaximumObjectSize;}
		$cpu=$ligne["cpu"];
		// Les <span class=label> Bootstrap sans color:#fff rendaient un texte
		// gris sur fond sombre.
		$label="<span class='awc-badge' style='background:#865300'>$inactive</span>";

		
		$cache_size=$cache_size*1024;
		$cache_size=FormatBytes($cache_size);
		$reconstruct=$tpl->icon_recycle("Loadjs('$page?delete-empty-js=yes&ID=$ID')","AsProxyMonitor");
		$delete=$tpl->icon_delete("Loadjs('$page?delete-item-js=yes&ID=$ID')","AsProxyMonitor");
		
		$up=$tpl->icon_up("Loadjs('$page?move-item-js=yes&ID=$ID&dir=0');");
		$down=$tpl->icon_down("Loadjs('$page?move-item-js=yes&ID=$ID&dir=1');");
		
		if(isset($squid_caches_infos[$cache_dir])){
			$current=FormatBytes($squid_caches_infos[$cache_dir]["CURRENT"]);
			$percentcache=round(floatval($squid_caches_infos[$cache_dir]["POURC"]??0),1);
			$label="<span class='awc-badge awc-badge-on'>$active</span>";
		}
		
		if(empty($ligne["enabled"])){
			$current=$tpl->icon_nothing();
			$label="<span class='awc-badge awc-badge-off'>$disabled</span>";
		}
		
		if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
			$current=$tpl->icon_nothing();
			$label="<span class='awc-badge' style='background:#ba1a1a'>$license_error</span>";
			
		}
		
		if(!empty($ligne["remove"])){
			$reconstruct=$tpl->icon_nothing();
			$delete=$tpl->icon_nothing();
			$up=$tpl->icon_nothing();
			$down=$tpl->icon_nothing();
			$label="<span class='awc-badge' style='background:#ba1a1a'>$to_remove</span>";
		}

        $percentcache="{$percentcache}%";

		// SMP: articarest (squid renderCacheDirs) gives an aufs/ufs/diskd cache to the worker of its
		// process; only a cache assigned to a process Squid does not run is left out.
		$cpuProcess=max(1,intval($cpu));
		if($IsSMP && $cpuProcess>$SMPWorkers){
            if($cache_type<>"rock"){
                $label="<span class='awc-badge awc-badge-off'>$disabled</span>";
                $up=$tpl->icon_nothing();
                $down=$tpl->icon_nothing();
                $reconstruct=$tpl->icon_nothing();
                $error="<br><strong>$unsuported_cache_smp</strong> ({process} $cpuProcess / $SMPWorkers)";
                $percentcache=$tpl->icon_nothing();
            }
        }

		
		// Le nom du cache et son repertoire viennent de SQLite et partaient
		// BRUTS dans le HTML.
		$html[]=caches_center_row(array(
			"id"      =>"row-parent-$ID",
			"name"    =>$tpl->td_href(htmlspecialchars($cachename),"{click_to_edit}","Loadjs('$page?cache-edit-js=$ID');"),
			"meta"    =>htmlspecialchars($cache_dir)."<br><i><small>$files: ".FormatBytes($min_size)." $to ".FormatBytes($max_size)."</small></i>".$error,
			"status"  =>$label,
			"used"    =>$percentcache,
			"size"    =>"$current/$cache_size",
			"order"   =>intval($ligne["zOrder"]),
			"actions" =>"$up$down$reconstruct$delete"));



	}
	if($ROWS==0){
		$html[]="<tr><td colspan='6' class='awc-empty'>{no_data}</td></tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $ROWS</span>";
	$html[]="<span class='awc-pg' id='cc-pager'></span>";
	$html[]="</div></div></div>";

    $TINY_ARRAY["TITLE"]="{caches_center}";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{caches_center_explain}";
    $TINY_ARRAY["URL"]="proxy-caches-center";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Cette fonction se terminait par un echo BRUT: les {tokens} des libelles
	// et des pastilles ne pouvaient PAS etre traduits et s'affichaient tels
	// quels. Le rendu passe desormais par _ENGINE_parse_body, la feuille de
	// style et les scripts etant concatenes apres.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny</script>"
	    .awc_pager_js(50,"cc-rows","cc-pager");

}

/**
 * Un disque libre proposable comme cache.
 * La colonne "type" restait VIDE en permanence: le tableau etait rempli avec la
 * cle minuscule "type" et relu avec la cle majuscule "TYPE" -- les cles PHP
 * sont sensibles a la casse, l'identifiant du fabricant n'a jamais ete affiche.
 * Il est desormais rendu en sous-titre du peripherique, et le nombre de
 * partitions -- collecte mais jamais montre -- occupe sa propre colonne.
 */
function caches_disk_row($tpl,$DEV,$MAIN,$js):string{
    $type=trim((string)($MAIN["type"] ?? ""));
    $h ="<tr id='row-parent-".htmlspecialchars($DEV)."'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-hard-drive'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars($DEV)."</span>";
    if($type<>""){$h.="<div class='awc-meta'>".htmlspecialchars($type)."</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center awc-mono'>".htmlspecialchars((string)($MAIN["SIZE"] ?? ""))."</td>";
    $h.="<td class='center awc-mono'>".intval($MAIN["PART"] ?? 0)."</td>";
    $h.="<td class='center'>".$tpl->button_autnonome("{select}",$js,"fa-hdd")."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Un cache declare.
 * Les valeurs arrivent deja construites par l'appelant (liens td_href, icones,
 * pastilles): elles ne peuvent pas etre echappees ici.
 */
function caches_center_row($r):string{
    $meta=trim((string)$r["meta"]);
    $h ="<tr id='{$r["id"]}'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-database'></i></span>";
    $h.="<span><span class='awc-name'>{$r["name"]}</span>";
    if($meta<>""){$h.="<div class='awc-meta'>$meta</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center awc-nowrap'>{$r["status"]}</td>";
    $h.="<td class='center awc-mono'>{$r["used"]}</td>";
    $h.="<td class='center awc-mono awc-nowrap'>{$r["size"]}</td>";
    $h.="<td class='center awc-mono'>{$r["order"]}</td>";
    $h.="<td><div class='awc-acts'>{$r["actions"]}</div></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Accès à squid_caches_center par l'API articarest (squid/proxy_caches_crud.go).
 * La page n'ouvre plus caches.db: une seule écriture (le daemon), et une
 * lecture ratée s'affiche comme une erreur au lieu d'une liste vide.
 */
function caches_api_error($json,$endpoint):string{
    if(!is_array($json)){return "$endpoint: no answer from the Artica daemon";}
    if(!empty($json["success"])){return "";}
    if(isset($json["error"]) && $json["error"]<>""){return (string)$json["error"];}
    return "$endpoint: request failed";
}
function caches_api_list(&$err=""):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/caches"),true);
    $err=caches_api_error($json,"/proxy/caches");
    if($err<>""){return array();}
    return (isset($json["data"]) && is_array($json["data"]))?$json["data"]:array();
}
function caches_api_get($ID,&$err=""):array{
    $ID=intval($ID);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/caches/$ID"),true);
    $err=caches_api_error($json,"/proxy/caches/$ID");
    if($err<>""){return array();}
    return (isset($json["data"]) && is_array($json["data"]))?$json["data"]:array();
}
