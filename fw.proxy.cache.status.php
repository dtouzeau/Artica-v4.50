<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["dynacls"])){dynacls();exit;}
if(isset($_GET['app-status'])){echo app_status();exit;}
if(isset($_GET["flot1"])){flot1();exit;}
if(isset($_GET["flot2"])){flot2();exit;}
if(isset($_GET["flot3"])){flot3();exit;}
if(isset($_GET["flot4"])){flot4();exit;}
if(isset($_GET["flot5"])){flot5();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status-caches"])){echo app_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["statistics-table"])){statistics_table();exit;}
if(isset($_GET["statistics-domain"])){statistics_domain();exit;}
if(isset($_GET["statistics-domain-table"])){statistics_domain_table();exit;}
if(isset($_GET["statistics-urls"])){statistics_urls();exit;}
if(isset($_GET["statistics-urls-table"])){statistics_urls_table();exit;}
if(isset($_GET["statistics-popup"])){statistics_settings();exit;}
if(isset($_GET["statistics-table-disabled"])){statistics_table_disabled();exit;}
if(isset($_POST["EnableSquidPurgeStoredObjects"])){statistics_save();exit;}
if(isset($_GET["hypercache-status"])){hypercache_status();exit;}
if(isset($_POST["disable-hypercache"])){hypercache_disable_perform();exit;}
if(isset($_POST["enable-hypercache"])){hypercache_enable_perform();exit;}
if(isset($_GET["disable-hypercache"])){hypercache_disable();exit;}
if(isset($_GET["enable-hypercache"])){hypercache_enable();exit;}

xgen();



function xgen(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{caching}",ico_caching,
        "{PROXY_CACHE_FEATURE_EXPLAIN}",
        "$page?tabs=yes",
        "proxy-caches",
        "progress-squidcaching-restart",false,"table-loader-caching-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{caching}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{your_caches_stored_on_disks}"]="$page?status=yes";
	echo $tpl->tabs_default($array);
    return true;
}
function hypercache_disable():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $text=$tpl->_ENGINE_parse_body("{remove_this_section}:{HYPERCACHE_STOREID}");

    $jsCompile=$tpl->framework_buildjs(
        "squid.php?verify-caches-progress-reload=yes",
        "squid.caches.progress",
        "squid.caches.progress.log",
        "progress-squidcaching-restart"
        );

    $tpl->js_dialog_confirm_action($text,"disable-hypercache",$text,$jsCompile);
 return true;
}
function hypercache_enable():bool{
    $tpl=new template_admin();
    $text=$tpl->_ENGINE_parse_body("{enable_feature}:{HYPERCACHE_STOREID}");

    $jsCompile=$tpl->framework_buildjs(
        "squid.php?verify-caches-progress-reload=yes",
        "squid.caches.progress",
        "squid.caches.progress.log",
        "progress-squidcaching-restart"
    );

    $tpl->js_dialog_confirm_action($text,"enable-hypercache",$text,$jsCompile);
    return true;
}
function hypercache_disable_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO('HyperCacheStoreID',0);
    admin_tracks($_POST["disable-hypercache"]);
    return true;
}
function hypercache_enable_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO('HyperCacheStoreID',1);
    admin_tracks($_POST["enable-hypercache"]);
    return true;
}
function status():bool{
    $page           = CurrentPageName();

    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='width:340px;padding-right:5px;vertical-align:top'><div id='hypercache-status' style='width:340px'></div></td>";
    $html[]="<td style='width:99%;padding-left:15px;vertical-align:top'><div id='caches-status'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('hypercache-status','$page?hypercache-status=yes');";
    $html[]="LoadAjax('caches-status','$page?status-caches=yes');";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}
function hypercache_status():bool{
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $tfile=PROGRESS_DIR."/HyperCacheStoreID.status";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?hypercache-status=yes");
    $ini=new Bs_IniHandler($tfile);
    $html[]=$tpl->SERVICE_STATUS($ini, "HYPERCACHE_STOREID");
    $HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));

    $disbale="Loadjs('$page?disable-hypercache=yes');";
    $enable="Loadjs('$page?enable-hypercache=yes');";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    if($HyperCacheStoreID==1) {
        $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"$disbale\"><i class='" . ico_play . "'></i> {disable} </label>";
    }else{
        $html[] = "<label class=\"btn btn btn-default\" OnClick=\"$enable\"><i class='" . ico_disabled . "'></i> {enable_feature} </label>";
    }
    $html[]="</div>";
    $html[]="<script>";
    $html[]="function RefreshHyperCacheStatus(){";
    $html[]="if( !document.getElementById('hypercache-status') ){return;}";
    $html[]="LoadAjaxSilent('hypercache-status','$page?hypercache-status=yes');";
    $html[]="}";
    $html[]="setTimeout('RefreshHyperCacheStatus()',3000);";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function app_status():string{
	$tpl            = new template_admin();
    $PROXY          = true;
	$users          = new usersMenus();
	$SQUIDEnable    = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $date_info      = null;
    $capacity       = null;
    $caches         = array();
    $SubTitle       =array();
	if(!$users->SQUID_INSTALLED){$PROXY=false;}
	if($SQUIDEnable==0){$PROXY=false;}
	if($PROXY){

        $SQUID_STORE_DIR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_STORE_DIR");
        $store_lines=explode("\n",$SQUID_STORE_DIR);
        foreach ($store_lines as $storeline){
            if(preg_match("#Last-Modified.*?:\s+(.+)#",$storeline,$re)){
                $date_info=$re[1];
            }
            if(preg_match("#Current Capacity.*?:\s+(.+)#",$storeline,$re)){
                $capacity=$re[1];
            }
        }



        if($date_info<>null OR $capacity<>null){
            if($date_info<>null){
                $SubTitle[]="{updated}: $date_info";
            }
            if($capacity<>null){
                $SubTitle[]=" ($capacity)";
            }

        }

        $html[]="";

	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$sql="SELECT * FROM squid_caches_center";
	$results = $q->QUERY_SQL($sql);
	$cachefile="/etc/artica-postfix/settings/Daemons/squid_get_cache_infos.db";
	$MAIN_CACHES=unserialize(@file_get_contents($cachefile));

	$SquidRockPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
	$EnableRockCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRockCache"));
	if($EnableRockCache==1){
		$MAIN=$MAIN_CACHES["$SquidRockPath/rock"];
		$cache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSize"));
		$cachename=$tpl->_ENGINE_parse_body("{squid_rock}");
		$MAX=$MAIN["MAX"];
		$PARTITION=$MAIN["PARTITION"];
		$CURRENT=$MAIN["CURRENT"];
		$FULL_SIZE=$MAIN["FULL_SIZE"];
		$FULL_SIZE=FormatBytes($FULL_SIZE/1024);
		$cache_size=FormatBytes($cache_size*1024);
		$CURRENT_TEXT=FormatBytes($CURRENT);
		$MAX_TEXT=FormatBytes($MAX);
		if(!is_numeric($CURRENT)){$CURRENT=0;}
		if(!is_numeric($MAX)){$MAX=0;}
		$PIE_MAX=$MAIN["MAX"]-$MAIN["CURRENT"];
		$id=md5("$SquidRockPath/rock");
		$POURC=round($MAIN["POURC"],2);

		$color="green";

        if($POURC>94){
            $color="yellow";
        }

		$caches[]=$tpl->widget_h($color,"fad fa-hdd","$POURC%","$cachename ($CURRENT_TEXT / {$MAX_TEXT})");

	}

    $IsSNMP=IsSNMP();
	$Err=array();
	$ALREADY_SET=array();
	foreach ($results as $index=>$ligne){
		if($ligne["enabled"]==0){continue; }
		$ID=$ligne["ID"];
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$cachename=$ligne["cachename"];
        $cache_dir=$ligne["cache_dir"];

        if(isset($ALREADY_SET[$cache_dir])){continue;}
        $ALREADY_SET[$cache_dir]=true;

		$remove=intval($ligne["remove"]);
		if($remove==1){continue;}
		if($cache_type=="Cachenull"){continue;}
		if($cache_type=="tmpfs"){$ligne["cache_dir"]="/home/squid/cache/MemBooster$ID";}
		$cachedir=$ligne["cache_dir"];
		if($cachedir==null){continue;}
        $color="grey";



		if(!isset($MAIN_CACHES[$cachedir])){
            if($cache_type=="aufs"){
                if($IsSNMP){
                    $caches[]=$tpl->widget_h("grey",ico_disabled,"{not_supported}","$cachename");
                    continue;
                }
            }



            $caches[]=$tpl->widget_h("grey","fad fa-hdd","{waiting}","$cachename");
		    $Err[]="{missing} $cachename ($cache_dir)";
		    continue;
        }

		$MAIN=$MAIN_CACHES[$cachedir];
        $id=md5(serialize($ligne));
		$MAX=$MAIN["MAX"];
		$PARTITION=$MAIN["PARTITION"];
		$CURRENT=$MAIN["CURRENT"];
		$FULL_SIZE=$MAIN["FULL_SIZE"];
		$FULL_SIZE=FormatBytes($FULL_SIZE/1024);
		$cache_size=FormatBytes($cache_size*1024);
		$CURRENT_TEXT=FormatBytes($CURRENT);
		$MAX_TEXT=FormatBytes($MAX);
		if(!is_numeric($CURRENT)){$CURRENT=0;}
		if(!is_numeric($MAX)){$MAX=0;}
		$PIE_MAX=$MAIN["MAX"]-$MAIN["CURRENT"];
		$POURC=$MAIN["POURC"];
        $cachespath_array=explode("/",$cache_dir);
        $cachespath_count=count($cachespath_array);
        $cache_dir_text=$cache_dir;
        if(count($cachespath_count)>4){
            $start=$cachespath_count-4;
            $zcaches=array();
            for($i=$start;$i<$cachespath_count;$i++){
                $zcaches[]=$cachespath_array[$i];
            }
            $cache_dir_text=@implode("/",$zcaches);
        }

        if($POURC>1){
            $color="green";
        }
        if($POURC>94){
            $color="yellow";
        }

        $caches[]=$tpl->widget_h($color,"fad fa-hdd","$POURC%","$cachename ($CURRENT_TEXT / $MAX_TEXT)");
		}


	    $html[]="<table style='width:100%'>";
	    $html[]="<tr>";
	    $i=0;
	    foreach ($caches as $widget){
            $i++;
            if($i>2){
                $i=0;
                $html[]="</tr><tr>";
            }
            $html[]="<td style='padding-left:5px;width:33%'>$widget</td>";
	    }
    }

		$html[]="</tr></table></div>";
    
    if(count($caches)==0){

        $html[]=$tpl->div_warning("{NoCache}||{proxy_no_cache_explain}");
    }



    $TINY_ARRAY["TITLE"]="{caching}";
    $TINY_ARRAY["ICO"]=ico_caching;
    $TINY_ARRAY["EXPL"]="{PROXY_CACHE_FEATURE_EXPLAIN}<br><strong>".@implode(" ",$SubTitle)."</strong>";
    $TINY_ARRAY["BUTTONS"]=null;
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$headsjs</script>";

    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($html);


}
function IsSNMP():bool{
    $CPUZ=array();
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if(!$SquidSMPConfig){$SquidSMPConfig=array();}
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

function statistics(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $tpl->js_dialog1("{schedule}:{analyze_stored_objects}","$page?statistics-table-disabled=yes");
}

function statistics_table_disabled(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$security="AsSquidAdministrator";
	$EnableSquidPurgeStoredObjects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjects"));
	$EnableSquidPurgeStoredObjectsMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsMaxTime"));
	$EnableSquidPurgeStoredObjectsTime=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsTime"));
	
	if($EnableSquidPurgeStoredObjectsTime==null){$EnableSquidPurgeStoredObjectsTime="03:35";}
	if($EnableSquidPurgeStoredObjectsMaxTime==0){$EnableSquidPurgeStoredObjectsMaxTime=120;}
	$form[]=$tpl->field_checkbox("EnableSquidPurgeStoredObjects","{analyze_stored_objects}",$EnableSquidPurgeStoredObjects,true,"{analyze_stored_objects_explain}");
	$form[]=$tpl->field_clock("EnableSquidPurgeStoredObjectsTime", "{each_day}", $EnableSquidPurgeStoredObjectsTime);

	
	$jsafter="dialogInstance1.close();";
	
	$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),"{squid_purge_disabled_explain}","{apply}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}

function statistics_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	foreach ($_POST as $key=>$val){$sock->SET_INFO($key, $val);}
	$sock->getFrameWork("squid2.php?schedule-purge=yes");
}

function statistics_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$EnableSquidPurgeStoredObjects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjects"));
	if($EnableSquidPurgeStoredObjects==0){statistics_table_disabled();return;}

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.purge.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.purge.progress.log";
	$ARRAY["CMD"]="squid2.php?stored-objects=yes";
	$ARRAY["TITLE"]="{analyze_caches}";
	$ARRAY["AFTER"]="LoadAjaxSilent('squid-purge','$page?statistics-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=squid-purge-progress')";
	
	
	
	$html[]=$tpl->_ENGINE_parse_body("
	
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fa fa-bolt'></i> {analyze_caches} </label>
			<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?statistics-popup=yes')\"><i class='fas fa-cogs'></i> {settings} </label>
			</div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			</div>");
	
	$html[]="<table id='table-persocats-items' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $TRCLASS=null;
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT SUM(size) as size,COUNT(familysite) as hits,familysite FROM squidpurge GROUP BY familysite order by size desc");
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$familysite=$ligne["familysite"];
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$familysiteenc=urlencode($familysite);
	
		$familysite=$tpl->td_href($familysite,"{stored_objects}","Loadjs('$page?statistics-domain=$familysiteenc')");
		
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td nowrap><i class=\"fas fa-globe\"></i>&nbsp;$familysite</td>";
		$html[]="<td width=1% nowrap>$size</td>";
		$html[]="<td width=1% nowrap>$hits</td>";
		$html[]="</tr>";
	
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-persocats-items').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function statistics_domain(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$domain=$_GET["statistics-domain"];
	$domainenc=urlencode($domain);
	$tpl->js_dialog1("{stored_objects}: $domain", "$page?statistics-domain-table=$domainenc");
}
function statistics_settings(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog1("{parameters}", "$page?statistics-table-disabled=yes");
	
}
function statistics_urls(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$domain=$_GET["statistics-urls"];
	$domainenc=urlencode($domain);
	$tpl->js_dialog2("{stored_objects}: $domain", "$page?statistics-urls-table=$domainenc");
	
}

function statistics_urls_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$domain=$_GET["statistics-urls-table"];
	$sql="SELECT size,path FROM squidpurge WHERE sitename='$domain' order by size desc LIMIT 250";
	$idtable=md5($domain."urls");
	$html[]="<table id='table-$idtable-items' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{urls}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$path=$ligne["path"];
		$size=$ligne["size"];
		$size=FormatBytes($ligne["size"]/1024);
		
	
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td nowrap>{$domain}$path</td>";
		$html[]="<td width=1% nowrap>$size</td>";
		$html[]="</tr>";
	
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$idtable-items').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
	
}

function statistics_domain_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$domain=$_GET["statistics-domain-table"];
	$sql="SELECT SUM(size) as size,COUNT(sitename) as hits,sitename FROM squidpurge WHERE familysite='$domain' GROUP BY sitename order by size desc ";
	$idtable=md5($domain);
	$html[]="<table id='table-$idtable-items' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$sitename=$ligne["sitename"];
		$size=$ligne["size"];
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$sitenameenc=urlencode($sitename);
	
		$familysite=$tpl->td_href($sitename,"{stored_objects}","Loadjs('$page?statistics-urls=$sitename')");
	
	
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td nowrap>$familysite</td>";
		$html[]="<td width=1% nowrap>$size</td>";
		$html[]="<td width=1% nowrap>$hits</td>";
		$html[]="</tr>";
	
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$idtable-items').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}